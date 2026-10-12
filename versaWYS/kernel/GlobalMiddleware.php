<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models\Dashboard;
use app\models\Users;
use Exception;
use versaWYS\kernel\helpers\Functions;

class GlobalMiddleware
{
    // Propiedad estática para almacenar los datos del usuario durante una única petición.
    // Esto evita tener que pasar el objeto de usuario entre funciones.
    private static ?array $currentUserData = null;

    /**
     * Obtiene los datos del usuario cargados por el middleware.
     * Esto permite a los controladores acceder a los datos sin volver a cargarlos.
     */
    public static function getLoadedUserData(): ?array
    {
        return self::$currentUserData;
    }

    /**
     * Check session, authentication, user data loading, and password expiration.
     * Versión mejorada con mejor manejo de errores y respuestas consistentes.
     * This is the main entry point for all authenticated routes.
     */
    public function checkSession(): bool|array
    {
        global $session, $request, $config, $debug;

        try {
            // 1. Validar la sesión y el token JWT (con caché por request para performance)
            if (!$session->checkUserSessionCached()) {
                // Limpiar cualquier dato de sesión corrupto
                $session->clear();

                if ($debug) {
                    Loggin::error('[GlobalMiddleware::checkSession] Sesión inválida o expirada', 'GlobalMiddleware');
                }

                // Para peticiones no-API, redirigir al login
                if (!$request->isApiCall()) {
                    Response::redirect('/' . $config['admin']['custom_url'] . '/login');
                    exit();
                }

                // Para peticiones API, devolver error estructurado
                return [
                    'success' => 0,
                    'valid' => false,
                    'message' => 'Token de autenticación inválido o expirado',
                    'code' => 401,
                ];
            }

            // 2. Cargar los datos del usuario usando el sistema de caché
            $userId = (int) $session->get('id_user');
            $empresaId = (int) $session->get('empresa_id');

            if ($userId <= 0) {
                if ($debug) {
                    Loggin::error(
                        '[GlobalMiddleware::checkSession] ID de usuario inválido: ' . $userId,
                        'GlobalMiddleware',
                    );
                }

                $session->destroy();

                if (!$request->isApiCall()) {
                    Response::redirect('/' . $config['admin']['custom_url'] . '/login');
                    exit();
                }

                return [
                    'success' => 0,
                    'valid' => false,
                    'message' => 'Datos de sesión corruptos',
                    'code' => 401,
                ];
            }

            // 3. Obtener datos del usuario desde caché
            $cache = new CacheUsers();
            self::$currentUserData = $cache->getUserData($userId, $empresaId);

            // 4. Validar que se pudieron cargar los datos del usuario
            if (self::$currentUserData === [] || !isset(self::$currentUserData['user'])) {
                if ($debug) {
                    Loggin::error(
                        '[GlobalMiddleware::checkSession] No se pudieron cargar datos del usuario ID: ' . $userId,
                        'GlobalMiddleware',
                    );
                }

                // Limpiar sesión corrupta
                $session->destroy();

                if (!$request->isApiCall()) {
                    Response::redirect('/' . $config['admin']['custom_url'] . '/login');
                    exit();
                }

                return [
                    'success' => 0,
                    'valid' => false,
                    'message' => 'Usuario no encontrado o inactivo',
                    'code' => 401,
                ];
            }

            // 5. Validar estado del usuario
            $userData = self::$currentUserData['user'];
            if (!isset($userData['status']) || (int) $userData['status'] !== 1) {
                if ($debug) {
                    Loggin::error(
                        '[GlobalMiddleware::checkSession] Usuario inactivo ID: ' . $userId,
                        'GlobalMiddleware',
                    );
                }

                $session->destroy();

                if (!$request->isApiCall()) {
                    Response::redirect('/' . $config['admin']['custom_url'] . '/login');
                    exit();
                }

                return [
                    'success' => 0,
                    'valid' => false,
                    'message' => 'Cuenta de usuario desactivada',
                    'code' => 403,
                ];
            }

            // 6. Comprobar la expiración de la contraseña
            $passwordExpired = $this->isPasswordExpired(
                dateExpiration: self::$currentUserData['user']['expiration_pass'] ?? null,
            );

            // 7. Manejar redirección por contraseña expirada (solo para navegador)
            $adminUrl = '/' . $config['admin']['custom_url'];
            if (
                !$request->isApiCall()
                && (
                    $passwordExpired
                    && $request->getUrl() !== $adminUrl . '/perfiluser'
                    && $request->getUrl() !== $adminUrl . '/login/resetPassByUser'
                )
            ) {
                Response::redirect($adminUrl . '/perfiluser');
                exit();
            }

            // 8. Guardar el estado de expiración para que esté disponible en el controlador y la vista
            self::$currentUserData['password_expired'] = $passwordExpired;

            if ($debug) {
                Loggin::info(
                    '[GlobalMiddleware::checkSession] Sesión válida para usuario: ' . $userId,
                    'GlobalMiddleware',
                );
            }

            return true;
        } catch (Exception $exception) {
            Loggin::error(
                '[GlobalMiddleware::checkSession] Error inesperado: ' . $exception->getMessage(),
                'GlobalMiddleware',
            );

            // En caso de error inesperado, limpiar sesión por seguridad
            $session->destroy();

            if (!$request->isApiCall()) {
                Response::redirect('/' . $config['admin']['custom_url'] . '/login');
                exit();
            }

            return [
                'success' => 0,
                'valid' => false,
                'message' => 'Error interno de autenticación',
                'code' => 500,
            ];
        }
    }

    /**
     * Validates the CSRF token in the request.
     *
     * @return array|null returns an array with error details if the CSRF token is invalid or missing, or null if the token is valid
     */
    public function validateCSRFToken($slug = null): true|array
    {
        global $session, $request, $debug;

        // Buscar token en orden de prioridad
        $token = $request->get('_csrf_token') ?: $request->get('csrf_token') ?: $this->getCSRFTokenWithPrefix(
            $request,
        ) ?: $request->getHeader('X-CSRF-Token');

        if (in_array($token, [null, '', false], true)) {
            return [
                'success' => 0,
                'message' => 'los datos enviados no son correctos',
                'errors' => [
                    'error' => 'El formulario no es valido, refresque y vuelva a intentarlo nuevamente...',
                ],
                'code' => 403,
            ];
        }

        if (!Functions::validateCSRFToken($session->get('csrf_token'), $token)) {
            return [
                'success' => 0,
                'message' => 'los datos enviados no son correctos',
                'errors' => [
                    'error' => 'Algo no salió bien, Refresque y vuelva a intentarlo nuevamente...',
                ],
                'code' => 403,
            ];
        }

        return true;
    }

    public function onlyAdmin(): ?bool
    {
        global $session, $debug, $config;

        $iduser = (int) $session->get('id_user');
        $user = (new Users())->findWithCompany($iduser);
        if ($user === []) {
            Response::redirect('/' . $config['admin']['custom_url'] . '/login');
            exit();
        }

        if ($user['role'] !== 'admin' && $user['role'] !== 'superadmin') {
            Response::redirect('/' . $config['admin']['custom_url'] . '/dashboard');
            exit();
        }

        return null;
    }

    public function onlySuperAdmin(): ?bool
    {
        global $session, $debug, $config;

        $iduser = (int) $session->get('id_user');
        $user = (new Users())->findWithCompany($iduser);
        if ($user === []) {
            Response::redirect('/' . $config['admin']['custom_url'] . '/login');
            exit();
        }

        if ($user['role'] !== 'superadmin') {
            Response::redirect('/' . $config['admin']['custom_url'] . '/dashboard');
            exit();
        }

        return null;
    }

    public function onlyUserWithAccess(): void
    {
        global $session, $request, $config;

        $iduser = (int) $session->get('id_user');
        $user = (new Users())->findWithCompany($iduser);
        if ($user === []) {
            Response::redirect('/' . $config['admin']['custom_url'] . '/login');
            exit();
        }

        if ($user['role'] === 'admin' || $user['role'] === 'superadmin') {
            return;
        }

        $flat = true;
        $url = $request->getUrl();
        $menuUser = (new Dashboard())->getMenuUser($iduser);
        foreach ($menuUser as $value) {
            if (!($value['url_menu'] === $url || $value['url_submenu'] === $url)) {
                continue;
            }

            return;
        }

        Response::redirect('/e404');
    }

    public function validatePolicyPassword(#[\SensitiveParameter] $password): bool
    {
        global $config;

        // politicas de contraseñas por defecto
        $large = $config['auth']['password_policy']['large'] ?? 8;
        $uppercase = $config['auth']['password_policy']['uppercase'] ?? false;
        $lowercase = $config['auth']['password_policy']['lowercase'] ?? false;
        $number = $config['auth']['password_policy']['number'] ?? false;
        $specialChars = $config['auth']['password_policy']['special_chars'] ?? false;

        if (strlen($password) < $large) {
            return false;
        }

        if ($uppercase && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if ($lowercase && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        if ($number && !preg_match('/\d/', $password)) {
            return false;
        }

        return !($specialChars && !preg_match('/[^a-zA-Z\d]/', $password));
    }

    public function getMessagePolicyPassword(): string
    {
        global $config;

        // politicas de contraseñas por defecto
        $large = $config['auth']['password_policy']['large'] ?? 8;
        $uppercase = $config['auth']['password_policy']['uppercase'] ?? false;
        $lowercase = $config['auth']['password_policy']['lowercase'] ?? false;
        $number = $config['auth']['password_policy']['number'] ?? false;
        $specialChars = $config['auth']['password_policy']['special_chars'] ?? false;

        $message = 'La contraseña debe tener al menos ' . $large . ' caracteres';

        if ($uppercase) {
            $message .= ', una letra mayúscula';
        }

        if ($lowercase) {
            $message .= ', una letra minúscula';
        }

        if ($number) {
            $message .= ', un número';
        }

        if ($specialChars) {
            $message .= ', un carácter especial';
        }

        return $message;
    }

    public function onlyDebug(): void
    {
        global $debug, $config;
        if (!$debug) {
            Response::redirect('/' . $config['admin']['custom_url'] . '/dashboard');
            exit();
        }
    }

    // =====================================================
    // RATE LIMITING METHODS - Funcionalidad global
    // =====================================================

    /**
     * Rate limiting para endpoints de API.
     */
    public function checkApiRateLimit(): array|true
    {
        return RateLimitMiddleware::checkApiLimit();
    }

    /**
     * Rate limiting para intentos de login.
     */
    public function checkLoginRateLimit(): array|true
    {
        return RateLimitMiddleware::checkLoginLimit();
    }

    /**
     * Rate limiting para subida de archivos.
     */
    public function checkUploadRateLimit(): array|true
    {
        return RateLimitMiddleware::checkUploadLimit();
    }

    /**
     * Rate limiting para endpoints sensibles.
     */
    public function checkSensitiveRateLimit(): array|true
    {
        return RateLimitMiddleware::checkSensitiveLimit();
    }

    /**
     * Password expiration check.
     */
    public function checkPasswordExpiration($redirect = true): bool
    {
        global $request, $config;

        $userData = self::getLoadedUserData();

        // Si los datos no están cargados, algo falló antes. Retornar false por seguridad.
        if ($userData === null || !isset($userData['user']['expiration_pass'])) {
            return false;
        }

        $expired = $this->isPasswordExpired($userData['user']['expiration_pass']);
        if ($request->getUrl() !== '/' . $config['admin']['custom_url'] . '/perfiluser' && $expired && $redirect) {
            Response::redirect('/' . $config['admin']['custom_url'] . '/perfiluser');
            exit();
        }

        return $expired;
    }

    /**
     * Password expiration validation.
     */
    public function isPasswordExpired($dateExpiration): bool
    {
        if ($dateExpiration === null || $dateExpiration === '') {
            return true;
        }

        $expiration = strtotime($dateExpiration);
        $today = strtotime(date('Y-m-d H:i:s'));

        return $expiration < $today;
    }

    /**
     * Busca campos que inicien con '_csrf_token'.
     */
    private function getCSRFTokenWithPrefix($request): ?string
    {
        $allParams = $request->getAllParams();

        foreach ($allParams as $key => $value) {
            if (!(str_starts_with($key, '_csrf_token') && $value !== null && $value !== '')) {
                continue;
            }

            return $value;
        }

        return null;
    }
}
