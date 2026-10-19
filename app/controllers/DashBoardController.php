<?php

declare(strict_types=1);

namespace app\controllers;

use app\jobs\EnviarCorreo;
use app\models as Models;
use Exception;
use InvalidArgumentException;
use Throwable;
use versaWYS\kernel\CacheUsers;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\GlobalMiddleware;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Loggin;
use versaWYS\kernel\NotFound;
use versaWYS\kernel\Queue;
use versaWYS\kernel\Response;

class DashBoardController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    public function index(): void
    {
        global $config;
        Response::redirect('/' . $config['admin']['custom_url']);
    }

    public function renderPrivacyPolicy(): string
    {
        return $this->template->render('dashboard/webPolicy/privacy');
    }

    public function renderDataDeletion(): string
    {
        return $this->template->render('dashboard/webPolicy/deletion');
    }

    /**
     * Renders the login view for the dashboard.
     * SPA navigation is handled automatically by versaTwig::render().
     *
     * @return string the rendered login view
     */
    /** Por qué se cerró la sesión (GlobalMiddleware la cierra con ?motivo=) */
    private const string LOST_PASSWORD_SENT = 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña.';

    private const array MOTIVOS = [
        'cuenta_desactivada' => 'Tu cuenta está desactivada. Contacta al administrador.',
        'empresa_suspendida' => 'La empresa está suspendida. Contacta al administrador.',
    ];

    public function login(): string
    {
        global $request;

        return $this->template->render('dashboard/login/login', [
            'motivo' => self::MOTIVOS[$request->string('motivo')] ?? null,
        ]);
    }

    /**
     * Authenticates the user based on the provided email and password.
     *
     * @throws Exception if the provided data is incorrect
     */
    public function autentication(): void
    {
        global $request, $session, $config;

        try {
            $params = $request->getAllParams();

            $userModel = new Models\Users();

            // auth.inactive_account_days: las cuentas sin ingresar en ese plazo quedan desactivadas
            $inactiveDays = (int) ($config['auth']['inactive_account_days'] ?? 0);
            if ($inactiveDays > 0) {
                $userModel->updateStatusInactiveAccount($inactiveDays);
            }

            $user = $userModel->findUserByEmail($params['email']);

            if (
                $user === []
                || !Functions::verifyHash($params['password'], $user['password'])
                || $user['status'] === false
            ) {
                // Mantener 401 solo en login para no revelar detalles a posibles atacantes
                throw new InvalidArgumentException('Los datos enviados no son correctos', 401);
            }

            // Con la clave correcta ya se puede decir el motivo: la empresa está suspendida
            $empresa = (new Models\Empresas())->getEmpresaById((int) $user['id_empresa']);
            if ($user['role'] !== 'superadmin' && $empresa !== null && !(bool) ($empresa['estado'] ?? true)) {
                throw new InvalidArgumentException(self::MOTIVOS['empresa_suspendida'], 403);
            }

            if (($params['remember'] ?? null) === 'on') {
                $session->set('remember', true);
            }

            $remember = $params['remember'] ?? 'off';

            $session->setUserSession($user, $remember);

            $attemps = $session->getAllAttempts();
            $session->restoreAttempts($attemps, $params['email']);

            $userModel->updateLastLogin($user['id']);

            // Hash de un algoritmo o costo anterior: único momento en que se tiene la contraseña para renovarlo
            if (Functions::needsRehash($user['password'])) {
                $userModel->rehashPassword((int) $user['id'], $params['password']);
            }

            $session->set('id_user', $user['id']);
            $session->set('empresa_id', $user['id_empresa']);

            Response::json([
                'success' => 1,
                'message' => 'Autenticación correcta',
                'redirect' => $user['pagina_inicio'],
            ], 200);
        } catch (Throwable $throwable) {
            Response::json([
                'success' => 0,
                'message' => $throwable->getMessage(),
                'errors' => [
                    'error' => 'Vuelva a intentarlo nuevamente...',
                ],
            ], $throwable->getCode());
        }
    }

    /**
     * Logout the user and redirect to the login page.
     */
    public function logout(): void
    {
        global $session, $cookie, $config, $request;

        // Sin token CSRF válido no se cierra la sesión (evita logout forzado desde otro sitio)
        $token = $request->string('_csrf_token');
        if (!hash_equals((string) $session->get('csrf_token'), $token)) {
            Response::redirect('/' . $config['admin']['custom_url']);

            return;
        }

        // El JWT deja de servir aunque alguien conserve una copia de la cookie
        $session->revokeToken($cookie->get('tknHash'));

        foreach ($session->getAll() as $key => $value) {
            $session->delete($key);
        }

        foreach ($cookie->getAll() as $key => $value) {
            $cookie->delete($key);
        }

        $session->destroy();
        $cookie->clear();

        Response::redirect('/' . $config['admin']['custom_url'] . '/login');
    }

    /**
     * Renders the dashboard view.
     * SPA navigation is handled automatically by versaTwig::render().
     *
     * @return string the rendered dashboard view
     */
    public function dashboard(): string
    {
        return $this->template->render('dashboard/loader', [
            'm' => '/dashboard/js/dashboard/dashboard',
        ]);
    }

    /**
     * Renders the lost password view.
     * SPA navigation is handled automatically by versaTwig::render().
     *
     * @return string the rendered lost password view
     */
    public function lostPassword(): string
    {
        return $this->template->render('dashboard/login/lost-password');
    }

    /**
     * Envía el correo de recuperación de contraseña de forma asíncrona.
     * El token ya queda guardado en DB antes de despachar el background process,
     * por lo que siempre se responde success inmediatamente (fire-and-forget).
     */
    public function sendLostPassword(): void
    {
        global $request, $config;

        try {
            $params = $request->getAllParams();

            $userModel = new Models\Users();
            $user = $userModel->findUserByEmail($request->string('email'));

            // La misma respuesta exista o no el correo: la pantalla no debe revelar quién está registrado
            if ($user === []) {
                Response::json(['success' => 1, 'message' => self::LOST_PASSWORD_SENT]);

                return;
            }

            $token = Models\Users::newResetToken();
            $userModel->saveTokenResetPass($user['email'], $token);

            $baseUrl = $request->getBaseUrl();
            $adminUrl = $config['admin']['custom_url'];

            // Construir images_embed para el favicon (logo del correo)
            $imagesEmbed = [];
            $faviconPath = Functions::getAssets('dashboard', 'img', '/favicon.png', false);

            if (file_exists($faviconPath)) {
                $imagesEmbed[] = ['path' => $faviconPath, 'id' => 'logo', 'mime' => 'image/png'];
            }

            // Por la cola: la respuesta no espera al servidor de correo y un fallo se reintenta
            Queue::push(EnviarCorreo::class, [
                'to' => ['email' => $user['email'], 'name' => $user['name']],
                'subject' => 'Recuperar contraseña',
                'type' => 'twig',
                'template' => 'mail/layout',
                'data' => [
                    'url_logo' => $baseUrl . '/' . $adminUrl,
                    'content' => 'Para recuperar su contraseña, haga clic en el siguiente enlace:',
                    'btnhref' => sprintf('%s/%s/reset-password?token=%s', $baseUrl, $adminUrl, $token),
                    'copyright' =>
                        '&copy; '
                            . date('Y')
                            . ' <a href="'
                            . $baseUrl
                            . '">'
                            . $config['build']['name']
                            . '</a> - Todos los derechos reservados.',
                    'title' => 'Recuperar contraseña - ' . $baseUrl,
                    'btnname' => 'Recuperar contraseña',
                ],
                'images_embed' => $imagesEmbed,
            ]);

            Response::json(['success' => 1, 'message' => self::LOST_PASSWORD_SENT]);
        } catch (Throwable $throwable) {
            // Un fallo interno (base de datos, lanzar el envío): al log, y al usuario un mensaje sin detalle
            Loggin::exception($throwable, 'lost-password');
            Response::json(['success' => 0, 'message' => 'No se pudo enviar el correo. Inténtelo más tarde.'], 500);
        }
    }

    /**
     * Renders Reset the password for a user page.
     *
     * @return string the rendered view for resetting the lost password
     */
    public function resetPassword(): string
    {
        global $request;

        $token = $request->string('token');

        $userModel = new Models\Users();
        $user = $userModel->findUserByToken($token);

        if ($user === []) {
            throw new NotFound();
        }

        return $this->template->render('dashboard/login/ResetLostPassword', [
            'token_reset' => $token,
        ]);
    }

    /**
     * Apply change password.
     *
     * This method is responsible for applying a change password request.
     * It retrieves the necessary parameters from the request, finds the user by the provided token,
     * updates the user's password, and returns a JSON response indicating the success or failure of the operation.
     *
     * @throws Exception if the provided token is invalid
     */
    public function applyChangePassword(): array
    {
        global $request;

        try {
            $params = $request->getAllParams();

            $userModel = new Models\Users();
            $user = $userModel->findUserByToken(Functions::text($params['tokenReset'] ?? null));

            if ($user === []) {
                // Cambio a 422: token o datos inválidos en reset password
                throw new InvalidArgumentException('Los datos enviados no son correctos', 422);
            }

            $userModel->updatePassword($user['email'], $params['new_password']);

            return [
                'success' => 1,
                'message' => 'Se ha cambiado la contraseña correctamente, se redirigirá al inicio de sesión...',
            ];
        } catch (InvalidArgumentException $exception) {
            return [
                'success' => 0,
                'message' => $exception->getMessage(),
                'errors' => ['error' => 'Vuelva a intentarlo nuevamente...'],
                'code' => $exception->getCode(),
            ];
        }
    }

    public function perfilUser(): string
    {
        // dump($this->user);
        return $this->template->render('dashboard/usuarios/perfilUser', [
            'owner_user' => $this->user,
        ]);
    }

    public function selectEmpresa(): void
    {
        global $request, $config, $session;
        $params = $request->getAllParams();

        if (($params['selectEmpresa'] ?? null) === null) {
            throw new InvalidArgumentException('Debe seleccionar una empresa válida', 400);
        }

        $empresaId = $params['selectEmpresa'];
        $empresaModel = new Models\Empresas();
        $empresa = $empresaModel->getEmpresaByToken($empresaId);
        if ($empresa === null) {
            throw new InvalidArgumentException('Empresa no encontrada', 404);
        }

        $session->set('empresa_id', $empresa['id']);
        $session->refreshUserSession([
            'id_user' => (int) $this->user['id'],
            'empresa_id' => (int) $empresa['id'],
        ]);
        $cacheUsers = new CacheUsers();
        $cacheUsers->invalidateUserCache((int) $this->user['id']);

        Response::redirect($params['urlActual'] ?? '/' . $config['admin']['custom_url'] . '/dashboard');
    }

    /**
     * Endpoint para verificar el estado de la sesión (heartbeat).
     * Versión mejorada: usa checkUserSession() como fuente de verdad para sincronización
     * PHP Session ↔ JWT, y renueva proactivamente ambos para evitar desincronización.
     */
    public function sessionHeartbeat(): void
    {
        global $session, $cookie, $config, $debug;

        try {
            // 1. Validación centralizada: checkUserSession sincroniza PHP ↔ JWT
            // Si PHP session expiró pero JWT sigue vivo, checkUserSession restaura automáticamente
            if (!$session->checkUserSession()) {
                $this->handleSessionExpired('Sesión inválida o expirada');

                return;
            }

            // 2. Esta ruta no pasa por checkSession: el estado de la cuenta se revisa aquí con la misma regla,
            // para no renovarle el token a una cuenta desactivada o de una empresa suspendida
            $heartbeatUserId = (int) ($session->get('id_user') ?? 0);
            $heartbeatEmpresaId = (int) ($session->get('empresa_id') ?? 0);
            $usuario = (new CacheUsers())->getUserData($heartbeatUserId, $heartbeatEmpresaId)['user'] ?? [];
            if ($usuario === [] || GlobalMiddleware::blockedReason($usuario) !== null) {
                $session->revokeToken($cookie->get('tknHash'));
                $cookie->delete('tknHash');
                $session->destroy();
                $this->handleSessionExpired('Cuenta desactivada o empresa suspendida');

                return;
            }

            // 3. Mantener la sesión PHP viva (touch)
            $session->touchSessionActivity();

            // 3. Obtener datos del JWT para calcular tiempos
            $token = $cookie->get('tknHash');
            if (!$token) {
                $this->handleSessionExpired('Token de autenticación no encontrado post-validación');

                return;
            }

            $tokenData = $session->decodeJWT($token);
            $expiresAt = $tokenData['exp'] ?? 0;
            $currentTime = time();
            $timeRemaining = $expiresAt - $currentTime;

            if ($timeRemaining <= 0) {
                $this->handleSessionExpired('Token JWT expirado');

                return;
            }

            // 4. Renovación proactiva del JWT
            $lifetime = $config['session']['user_cookie']['lifetime'];
            $renewThreshold = $lifetime * 0.75;
            $warningThreshold = 600;
            $sessionRenewed = false;

            if ($timeRemaining < $renewThreshold && $heartbeatUserId > 0 && $heartbeatEmpresaId > 0) {
                try {
                    $newToken = $session->refreshUserSession([
                        'id_user' => $heartbeatUserId,
                        'empresa_id' => $heartbeatEmpresaId,
                    ]);
                    if ($newToken) {
                        $newTokenData = $session->decodeJWT($newToken);
                        $expiresAt = $newTokenData['exp'];
                        $timeRemaining = $expiresAt - $currentTime;
                        $sessionRenewed = true;

                        if ($debug) {
                            Loggin::info(
                                sprintf('[SessionHeartbeat] Sesión renovada. Nuevo tiempo: %s s', $timeRemaining),
                                'sessionHeartbeat',
                            );
                        }
                    }
                } catch (Throwable $e) {
                    if ($debug) {
                        Loggin::error(
                            '[SessionHeartbeat] Error renovando sesión: ' . $e->getMessage(),
                            'sessionHeartbeat',
                        );
                    }
                }
            }

            if ($timeRemaining < $renewThreshold && $heartbeatUserId <= 0 && $debug) {
                Loggin::info(
                    '[SessionHeartbeat] No se renovó JWT: id_user no disponible en sesión',
                    'sessionHeartbeat',
                );
            }

            // 5. Respuesta
            $nextHeartbeatInSeconds = $this->calculateNextHeartbeat($timeRemaining, $warningThreshold);
            $isNearExpiry = $timeRemaining <= $warningThreshold && $timeRemaining > 0;

            Response::json([
                'success' => 1,
                'valid' => true,
                'message' => $sessionRenewed ? 'Sesión renovada automáticamente' : 'Sesión válida',
                'timeRemaining' => max(0, $timeRemaining),
                'expiresAt' => $expiresAt,
                'isNearExpiry' => $isNearExpiry,
                'sessionRenewed' => $sessionRenewed,
                'csrf_token' => $session->get('csrf_token') ?? '',
                'nextHeartbeatInSeconds' => $nextHeartbeatInSeconds,
                'timestamp' => $currentTime,
            ], 200);
        } catch (Throwable $throwable) {
            Loggin::error('[SessionHeartbeat] Excepción inesperada: ' . $throwable->getMessage(), 'sessionHeartbeat');

            Response::json([
                'success' => 0,
                'valid' => false,
                'message' => 'Error interno verificando sesión',
                'timeRemaining' => 0,
                'error' => $debug ? $throwable->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Maneja la respuesta cuando la sesión ha expirado.
     */
    private function handleSessionExpired(string $reason): void
    {
        global $debug;

        if ($debug) {
            Loggin::info('[SessionHeartbeat] Manejo de sesión expirada: ' . $reason, 'sessionHeartbeat');
        }

        Response::json([
            'success' => 0,
            'valid' => false,
            'message' => 'Sesión expirada',
            'reason' => $debug ? $reason : null,
            'timeRemaining' => 0,
            'timestamp' => time(),
        ], 401);
    }

    /**
     * Calcula el intervalo óptimo para el próximo heartbeat.
     */
    private function calculateNextHeartbeat(int $timeRemaining, int $warningThreshold): int
    {
        // Intervalos base en segundos
        $intervals = [
            'immediate' => 30, // Para situaciones críticas
            'short' => 60, // Cuando queda poco tiempo
            'medium' => 300, // Intervalo normal (5 minutos)
            'long' => 900, // Cuando queda mucho tiempo (15 minutos)
        ];

        if ($timeRemaining <= 120) {
            // Menos de 2 minutos
            return $intervals['immediate'];
        }

        if ($timeRemaining <= $warningThreshold) {
            // Menos de 10 minutos
            return $intervals['short'];
        }

        if ($timeRemaining <= 1800) {
            // Menos de 30 minutos
            return $intervals['medium'];
        }

        return $intervals['long'];
    }
}
