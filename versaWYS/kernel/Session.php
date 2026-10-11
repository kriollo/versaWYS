<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/kernel/session.php.
 *
 * This class represents a session in the application.
 * It provides methods for managing session data, generating and decoding JWT tokens,
 * and handling login attempts.
 */

declare(strict_types=1);

namespace versaWYS\kernel;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use RuntimeException;
use versaWYS\kernel\helpers\Functions;

/**
 * Class Session.
 *
 * This class handles session management in the application.
 * It provides methods to start, regenerate, check, and destroy sessions.
 * It also provides methods to set, get, and delete session variables.
 * Additionally, it includes methods for managing login attempts and user sessions.
 */
class Session
{
    /**
     * Session constructor.
     *
     * @param int $lifetime The lifetime of the session cookie in seconds (default: 3600)
     */
    public function __construct(int $lifetime = 3600)
    {
        global $config, $debug;
        // Set the session cookie lifetime
        ini_set('session.cookie_lifetime', $lifetime);

        // Set the session garbage collector lifetime
        ini_set('session.gc_maxlifetime', $lifetime);

        // 🔒 AGREGAR flags de seguridad ANTES de session_start()
        ini_set('session.cookie_httponly', $config['session']['user_cookie']['http_only'] ? '1' : '0');
        ini_set('session.cookie_secure', $config['session']['user_cookie']['secure'] ? '1' : '0');
        ini_set('session.cookie_samesite', $config['session']['user_cookie']['same_site']);
        ini_set('session.use_strict_mode', $config['session']['user_cookie']['strict_mode'] ? '1' : '0');

        if (session_status() === PHP_SESSION_NONE) {
            if (!session_start()) {
                Loggin::error('Failed to start session', 'Session', error_get_last());

                // Handle error according to debug mode
                if ($debug) {
                    // In debug mode, use ErrorHandler to show detailed error
                    $errorHandler = new ErrorHandler();
                    $errorHandler->handleException(new RuntimeException('Failed to start session'));
                    exit();
                }

                // Verifica si esta es una solicitud API utilizando nuestra detección mejorada
                $isApi = $this->isStaticApiCall();
                if ($isApi) {
                    Response::jsonError([
                        'message' => 'Error interno del servidor',
                        'code' => 500,
                    ], 500);
                    exit();
                }

                // For non-API requests, we could continue without sessions
                // or show an error page depending on requirements
                http_response_code(500);
                echo '<h1>Error del sistema</h1><p>Error de inicialización. Por favor, contacte al administrador.</p>';
                exit();
            }

            if (empty($_SESSION['csrf_token'])) {
                $this->setCSRFToken();
            }
        }
    }

    /**
     * Regenerates the session ID.
     */
    public function regenerate(): void
    {
        global $debug;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id();
        } else {
            $error_message = 'Cannot regenerate session ID: No active session';
            Loggin::error($error_message, 'Session');

            // Handle error according to debug mode
            if ($debug) {
                // In debug mode, use ErrorHandler to show detailed error
                $errorHandler = new ErrorHandler();
                $errorHandler->handleException(new RuntimeException($error_message));
            }
        }
    }

    /**
     * Checks if a session exists.
     *
     * @return bool True if a session exists, false otherwise
     */
    public function checkExistSession(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Sets a CSRF token in the session.
     */
    public function setCSRFToken(): void
    {
        $_SESSION['csrf_token'] = Functions::generateCSRFToken();
    }

    /**
     * Gets the CSRF token from the session.
     *
     * @return string The CSRF token
     */
    public function getCSRFToken(): string
    {
        return $_SESSION['csrf_token'];
    }

    /**
     * Sets a session variable.
     *
     * @param string $key The key of the session variable
     * @param mixed $value The value of the session variable
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Checks if a session variable exists.
     *
     * @param string $key The key of the session variable
     *
     * @return bool True if the session variable exists, false otherwise
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Gets the value of a session variable.
     *
     * @param string $key The key of the session variable
     * @param mixed|null $default The default value to return if the session variable does not exist (default: null)
     *
     * @return mixed The value of the session variable or the default value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Obtiene todos los datos de la sesión.
     *
     * @return array los datos de la sesión
     */
    public function getAll(): array
    {
        return $_SESSION;
    }

    /**
     * Deletes a session variable.
     *
     * @param string $key The key of the session variable to delete
     */
    public function delete(string $key): void
    {
        if ($this->has($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clears all session variables.
     */
    public function clear(): void
    {
        session_unset();
    }

    /**
     * Gets the login attempts for a specific email address.
     *
     * @param string $email The email address
     *
     * @return array The login attempts for the email address
     */
    public function getAttempts(string $email): array
    {
        $attempts = $this->get('login_user_attempts');
        if ($attempts === null) {
            return [];
        }

        if (array_key_exists($email, $attempts)) {
            return $attempts[$email];
        }

        return [];
    }

    /**
     * Gets all login attempts.
     *
     * @return array All login attempts
     */
    public function getAllAttempts(): array
    {
        return $this->get('login_user_attempts');
    }

    /**
     * Sets the login attempts.
     *
     * @return array The login attempts
     */
    public function setAttempts(): array
    {
        if ($this->get('login_user_attempts') !== null) {
            return $this->get('login_user_attempts');
        }

        return [];
    }

    /**
     * Restores the login attempts for a specific email address.
     *
     * @param array $attempts The login attempts
     * @param string $email The email address
     */
    public function restoreAttempts(array $attempts, string $email): void
    {
        $attempts[$email]['attempts'] = 0;
        $attempts[$email]['time'] = null;

        $this->set('login_user_attempts', $attempts);
    }

    /**
     * Sets the login attempts for a specific email address.
     *
     * @param array $attempts The login attempts
     * @param string $email The email address
     *
     * @return array The updated login attempts
     */
    public function setAttemptsSession(array $attempts, string $email): array
    {
        if (!array_key_exists($email, $attempts)) {
            $attempts[$email] = [
                'attempts' => 1,
                'time' => null,
            ];
        } else {
            $attempts[$email]['attempts']++;
        }

        $this->set('login_user_attempts', $attempts);

        return $attempts;
    }

    /**
     * Checks if the maximum login attempts for a specific email address have been reached.
     *
     * @param array $attempts The login attempts
     * @param string $email The email address
     *
     * @return bool True if the maximum attempts have been reached, false otherwise
     */
    public function maximumAttempts(array $attempts, string $email): bool
    {
        global $config;
        if ($attempts[$email]['attempts'] >= $config['login_attempt']['max']) {
            if ($attempts[$email]['time'] === null) {
                $attempts[$email]['time'] = time() + $config['login_attempt']['time'];
            }

            $this->set('login_user_attempts', $attempts);

            if (time() < $attempts[$email]['time']) {
                return false;
            }

            $this->restoreAttempts($attempts, $email);

            return true;
        }

        return true;
    }

    /**
     * Generates a JSON Web Token (JWT) for a user.
     *
     * @param array $user The user data
     * @param string $remember The remember option ('on' or 'off')
     *
     * @return string The generated JWT
     */
    public function generateJWT(array $user, string $remember): string
    {
        global $config;

        // Validación defensiva de estructura de usuario
        // Aceptamos alias comunes y normalizamos a id_user
        $userId = $user['id'] ?? $user['id_user'] ?? $user['user_id'] ?? null;
        // Intentar castear si viene string numérica
        if (!is_int($userId) && (is_string($userId) && ctype_digit($userId))) {
            $userId = (int) $userId;
        }

        if ($userId === null || $userId <= 0) {
            // Log seguro sin exponer datos sensibles completos
            $excerpt = array_intersect_key($user, array_flip(['email', 'username', 'id', 'id_user', 'user_id']));
            Loggin::error('Usuario sin identificador válido. Datos parciales', 'Session', $excerpt);

            // En lugar de generar un token inválido, lanzamos excepción controlada
            throw new InvalidArgumentException('No se puede generar JWT: ID de usuario ausente o inválido');
        }

        $empresaId = $user['empresa_id'] ?? $user['id_empresa'] ?? $user['company_id'] ?? null;
        if (!is_int($empresaId) && (is_string((string) $empresaId) && ctype_digit((string) $empresaId))) {
            $empresaId = (int) $empresaId;
        }

        if ($empresaId === null || $empresaId <= 0) {
            $excerpt = array_intersect_key($user, array_flip([
                'email',
                'username',
                'id',
                'id_user',
                'user_id',
                'empresa_id',
                'id_empresa',
            ]));
            Loggin::error('Usuario sin empresa_id válido para JWT. Datos parciales', 'Session', $excerpt);
            throw new InvalidArgumentException('No se puede generar JWT: empresa_id ausente o inválido');
        }

        $lifetime = $remember === 'off'
            ? $config['session']['user_cookie']['lifetime']
            : $config['session']['user_cookie']['lifetime_remember'];

        $payload = [
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $lifetime,
            'id_user' => $userId,
            'empresa_id' => $empresaId,
        ];

        return JWT::encode($payload, $config['session']['key'], 'HS256');
    }

    /**
     * Decodes a JSON Web Token (JWT).
     *
     * @param string $jwt The JWT to decode
     *
     * @return array The decoded JWT
     */
    public function decodeJWT(string $jwt): array
    {
        global $config, $debug;

        try {
            $decoded = JWT::decode($jwt, new Key($config['session']['key'], 'HS256'));
        } catch (Exception $exception) {
            $error_message = 'Failed to decode JWT: ' . $exception->getMessage();
            Loggin::error($error_message, 'Session');

            // Handle error according to debug mode
            if ($debug) {
                // In debug mode, use ErrorHandler to show detailed error
                $errorHandler = new ErrorHandler();
                $errorHandler->handleException(new RuntimeException($error_message));
                exit();
            }

            // Check if this is an API request using our improved detection
            $isApi = $this->isStaticApiCall();

            if ($isApi) {
                Response::jsonError(['message' => 'Token inválido', 'code' => 401], 401);
                exit();
            }

            // For non-API requests, redirect to login or show error
            header('Location: /login');
            exit();
        }

        if ($decoded === false) {
            $error_message = 'JWT decode returned false';
            Loggin::error($error_message, 'Session');

            // Handle error according to debug mode
            if ($debug) {
                // In debug mode, use ErrorHandler to show detailed error
                $errorHandler = new ErrorHandler();
                $errorHandler->handleException(new RuntimeException($error_message));
                exit();
            }

            // Check if this is an API request using our improved detection
            $isApi = $this->isStaticApiCall();
            if ($isApi) {
                Response::jsonError(['message' => 'Token inválido', 'code' => 401], 401);
                exit();
            }

            // For non-API requests, redirect to login
            header('Location: /login');
            exit();
        }

        // transformar objeto a array
        $decoded = json_decode(json_encode($decoded), true);

        return (array) $decoded;
    }

    /**
     * Sets the user session.
     *
     * @param array $user The user data
     * @param string $remember The remember option ('on' or 'off')
     */
    public function setUserSession(array $user, string $remember): void
    {
        global $config, $cookie;

        try {
            $jwt = $this->generateJWT($user, $remember);
        } catch (InvalidArgumentException $invalidArgumentException) {
            Loggin::error(
                '[Session::setUserSession] Error generando JWT: ' . $invalidArgumentException->getMessage(),
                'Session',
            );
            // Respuesta limpia según contexto (API / no API)
            $isApi = $this->isStaticApiCall();
            if ($isApi) {
                Response::jsonError(['message' => 'Error de autenticación', 'code' => 401], 401);
            }

            return; // No establecer sesión si el JWT es inválido
        }

        $lifetime =
            time()
            + (
                $remember === 'off'
                    ? $config['session']['user_cookie']['lifetime']
                    : $config['session']['user_cookie']['lifetime_remember']
            );
        if ($config['session']['user_cookie']['enable']) {
            $cookie->set(
                'tknHash',
                $jwt,
                $lifetime,
                $config['session']['user_cookie']['domain'],
                $config['session']['user_cookie']['secure'],
                $config['session']['user_cookie']['http_only'],
            );
        }
    }

    /**
     * Refreshes the user session by generating a new JWT and setting it in the cookie.
     * Versión mejorada con mejor manejo de errores y logging.
     *
     * @param array $user the user data
     *
     * @return string the new JWT
     */
    public function refreshUserSession(array $user): string
    {
        global $config, $cookie, $debug;

        try {
            $userId = $user['id_user'] ?? $user['id'] ?? null;

            if (!is_int($userId) && is_string($userId) && ctype_digit($userId)) {
                $userId = (int) $userId;
            }

            if (!is_int($userId) || $userId <= 0) {
                $sessionUserId = $this->get('id_user');
                if (
                    !is_int($sessionUserId)
                    && is_string((string) $sessionUserId)
                    && ctype_digit((string) $sessionUserId)
                ) {
                    $sessionUserId = (int) $sessionUserId;
                }

                if (is_int($sessionUserId) && $sessionUserId > 0) {
                    $userId = $sessionUserId;
                }
            }

            $empresaId = $user['empresa_id'] ?? $user['id_empresa'] ?? null;
            if (!is_int($empresaId) && is_string((string) $empresaId) && ctype_digit((string) $empresaId)) {
                $empresaId = (int) $empresaId;
            }

            if (!is_int($empresaId) || $empresaId <= 0) {
                $sessionEmpresaId = $this->get('empresa_id');
                if (
                    !is_int($sessionEmpresaId)
                    && is_string((string) $sessionEmpresaId)
                    && ctype_digit((string) $sessionEmpresaId)
                ) {
                    $sessionEmpresaId = (int) $sessionEmpresaId;
                }

                if (is_int($sessionEmpresaId) && $sessionEmpresaId > 0) {
                    $empresaId = $sessionEmpresaId;
                }
            }

            if (!is_int($userId) || $userId <= 0 || !is_int($empresaId) || $empresaId <= 0) {
                $currentToken = $cookie->get('tknHash');
                if (is_string($currentToken) && $currentToken !== '' && $currentToken !== 'undefined') {
                    try {
                        $decodedToken = $this->decodeJWT($currentToken);
                        $tokenUserId = $decodedToken['id_user'] ?? null;
                        $tokenEmpresaId = $decodedToken['empresa_id'] ?? null;
                        if (
                            !is_int($tokenUserId)
                            && is_string((string) $tokenUserId)
                            && ctype_digit((string) $tokenUserId)
                        ) {
                            $tokenUserId = (int) $tokenUserId;
                        }

                        if (
                            !is_int($tokenEmpresaId)
                            && is_string((string) $tokenEmpresaId)
                            && ctype_digit((string) $tokenEmpresaId)
                        ) {
                            $tokenEmpresaId = (int) $tokenEmpresaId;
                        }

                        if (is_int($tokenUserId) && $tokenUserId > 0) {
                            $userId = $tokenUserId;
                            $this->set('id_user', $tokenUserId);
                        }

                        if (is_int($tokenEmpresaId) && $tokenEmpresaId > 0) {
                            $empresaId = $tokenEmpresaId;
                            $this->set('empresa_id', $tokenEmpresaId);
                        }
                    } catch (Exception $exception) {
                        if ($debug) {
                            Loggin::error(
                                '[Session::refreshUserSession] No se pudo recuperar id_user desde JWT: '
                                    . $exception->getMessage(),
                                'Session',
                            );
                        }
                    }
                }
            }

            // Validar que tenemos datos mínimos del usuario
            if (!is_int($userId) || $userId <= 0 || !is_int($empresaId) || $empresaId <= 0) {
                Loggin::error('Datos de usuario inválidos para refresh', 'Session', [
                    'user_data' => $user,
                    'session_id_user' => $this->get('id_user'),
                    'session_empresa_id' => $this->get('empresa_id'),
                ]);

                return '';
            }

            $user = [
                'id_user' => $userId,
                'empresa_id' => $empresaId,
            ];

            // Para session refresh, mantener el tipo de sesión actual (con o sin "remember me")
            // Verificar si la sesión actual tenía "remember me" activado
            $currentToken = $cookie->get('tknHash');
            $isRememberSession = false;

            if ($currentToken) {
                try {
                    $currentDecoded = $this->decodeJWT($currentToken);
                    $currentLifetime = $currentDecoded['exp'] - $currentDecoded['iat'];
                    $standardLifetime = $config['session']['user_cookie']['lifetime'];

                    // Si el lifetime actual es mayor al estándar, probablemente es "remember me"
                    $isRememberSession = $currentLifetime > ($standardLifetime * 1.5);
                } catch (Exception $e) {
                    Loggin::error(
                        '[Session::refreshUserSession] Error verificando sesión actual: ' . $e->getMessage(),
                        'Session',
                    );
                }
            }

            // Generar nuevo token manteniendo el tipo de sesión
            $rememberFlag = $isRememberSession ? 'on' : 'off';
            $jwt = $this->generateJWT($user, $rememberFlag);

            if ($jwt === '' || $jwt === '0') {
                Loggin::error('[Session::refreshUserSession] No se pudo generar nuevo JWT', 'Session');

                return '';
            }

            // Calcular nueva fecha de expiración
            $lifetime = $isRememberSession
                ? $config['session']['user_cookie']['lifetime_remember']
                : $config['session']['user_cookie']['lifetime'];

            $expirationTime = time() + $lifetime;

            // Establecer nueva cookie
            if ($config['session']['user_cookie']['enable']) {
                $cookie->set(
                    'tknHash',
                    $jwt,
                    $expirationTime,
                    $config['session']['user_cookie']['domain'],
                    $config['session']['user_cookie']['secure'],
                    $config['session']['user_cookie']['http_only'],
                );

                if ($debug) {
                    $userId = $user['id'] ?? $user['id_user'] ?? 'unknown';
                    Loggin::debug('Sesión renovada para usuario', 'Session', [
                        'userId' => $userId,
                        'empresaId' => $empresaId,
                        'remember' => $isRememberSession,
                        'lifetime' => $lifetime,
                    ]);
                }
            }

            return $jwt;
        } catch (InvalidArgumentException $invalidArgumentException) {
            Loggin::error(
                '[Session::refreshUserSession] Error generando JWT: ' . $invalidArgumentException->getMessage(),
                'Session',
            );

            // Manejar respuesta según contexto
            $isApi = $this->isStaticApiCall();
            if ($isApi) {
                Response::jsonError(['message' => 'Error de autenticación', 'code' => 401], 401);
            }

            return '';
        } catch (Exception $e) {
            Loggin::error('[Session::refreshUserSession] Error inesperado: ' . $e->getMessage(), 'Session');

            return '';
        }
    }

    /**
     * Checks if a user session exists and is valid.
     * Versión mejorada con mejor sincronización entre sesión PHP y JWT.
     *
     * @return bool True if a user session exists and is valid, false otherwise
     */
    public function checkUserSession(): bool
    {
        global $config, $cookie, $debug;

        try {
            // 1. Verificar que las cookies están habilitadas
            if (!$config['session']['user_cookie']['enable']) {
                if ($debug) {
                    Loggin::debug('[Session::checkUserSession] Cookies de sesión deshabilitadas', 'Session');
                }

                return false;
            }

            // 2. Verificar que existe el token JWT en las cookies
            if (!$cookie->has('tknHash')) {
                if ($debug) {
                    Loggin::debug('[Session::checkUserSession] Token JWT no encontrado en cookies', 'Session');
                }

                return false;
            }

            // 3. Obtener y validar el token JWT
            $jwt = $cookie->get('tknHash');
            if (empty($jwt) || $jwt === 'undefined' || $jwt === 'null') {
                if ($debug) {
                    Loggin::debug('[Session::checkUserSession] Token JWT vacío o inválido: ' . $jwt, 'Session');
                }

                return false;
            }

            // 4. Decodificar el token JWT
            try {
                $decoded = $this->decodeJWT($jwt);
            } catch (Exception $e) {
                Loggin::error('[Session::checkUserSession] Error decodificando JWT: ' . $e->getMessage(), 'Session');

                return false;
            }

            // 5. Validar la estructura del token
            if (!isset($decoded['id_user']) || !isset($decoded['exp']) || !isset($decoded['empresa_id'])) {
                Loggin::error('[Session::checkUserSession] Token JWT con estructura inválida', 'Session');

                return false;
            }

            // 6. Verificar que el token no ha expirado
            $currentTime = time();
            if ($decoded['exp'] <= $currentTime) {
                Loggin::error('[Session::checkUserSession] Token JWT expirado. Exp: ', 'Session', [
                    'token_data' => $decoded,
                    'current_time' => $currentTime,
                ]);

                return false;
            }

            // 7. Verificar integridad del ID de usuario
            $tokenUserId = $decoded['id_user'];
            if (!is_numeric($tokenUserId) || (int) $tokenUserId <= 0) {
                if ($debug) {
                    Loggin::debug(
                        '[Session::checkUserSession] ID de usuario inválido en token: ' . $tokenUserId,
                        'Session',
                    );
                }

                return false;
            }

            $tokenEmpresaId = $decoded['empresa_id'];
            if (!is_numeric((string) $tokenEmpresaId) || (int) $tokenEmpresaId <= 0) {
                if ($debug) {
                    Loggin::debug(
                        '[Session::checkUserSession] empresa_id inválido en token: ' . (string) $tokenEmpresaId,
                        'Session',
                    );
                }

                return false;
            }

            // 8. Sincronizar sesión PHP con datos del JWT
            // Cuando PHP GC destruye la sesión pero el JWT sigue vigente,
            // debemos restaurar la sesión PHP completamente desde el JWT.
            $sessionNeedsSync = false;

            if (!$this->checkExistSession()) {
                // No hay sesión PHP activa, crearla
                $this->regenerate();
                $sessionNeedsSync = true;
                if ($debug) {
                    Loggin::debug('[Session::checkUserSession] Sesión PHP regenerada desde JWT', 'Session');
                }
            }

            // 9. Verificar y sincronizar ID de usuario en sesión PHP
            $sessionUserId = $this->get('id_user');
            if ($sessionUserId === null || (string) $sessionUserId !== (string) $tokenUserId) {
                // Sincronizar datos de sesión PHP con JWT
                $this->set('id_user', $tokenUserId);
                $sessionNeedsSync = true;
                if ($debug) {
                    Loggin::debug(
                        '[Session::checkUserSession] ID de usuario sincronizado. Session: '
                        . $sessionUserId
                        . ' -> JWT: '
                        . $tokenUserId,
                        'Session',
                    );
                }
            }

            $sessionEmpresaId = $this->get('empresa_id');
            if ($sessionEmpresaId === null || (string) $sessionEmpresaId !== (string) $tokenEmpresaId) {
                $this->set('empresa_id', (int) $tokenEmpresaId);
                $sessionNeedsSync = true;
                if ($debug) {
                    Loggin::debug(
                        '[Session::checkUserSession] empresa_id sincronizado. Session: '
                        . (string) $sessionEmpresaId
                        . ' -> JWT: '
                        . (string) $tokenEmpresaId,
                        'Session',
                    );
                }
            }

            // 10. Si la sesión PHP fue restaurada, renovar también el token JWT
            // para extender su vida útil y mantener ambos sincronizados.
            if ($sessionNeedsSync) {
                $this->touchSessionActivity();
            }

            if ($debug) {
                Loggin::debug('[Session::checkUserSession] Sesión válida para usuario: ' . $tokenUserId, 'Session');
            }

            return true;
        } catch (Exception $exception) {
            Loggin::error('[Session::checkUserSession] Error inesperado: ' . $exception->getMessage(), 'Session');

            return false;
        }
    }

    /**
     * Versión optimizada de checkUserSession que usa caché en sesión para evitar
     * decodificar el JWT en cada petición dentro de la misma request.
     * Reduce el overhead criptográfico manteniendo la seguridad.
     *
     * @param bool $forceValidation Forzar validación completa (útil para heartbeat)
     */
    public function checkUserSessionCached(bool $forceValidation = false): bool
    {
        // Si ya validamos en esta request, usar resultado cacheado
        // Esto evita múltiples decodificaciones JWT por request
        $cacheKey = '_session_validated_at';
        $lastValidation = $this->get($cacheKey);

        if (!$forceValidation && $lastValidation !== null) {
            $secondsSinceValidation = time() - (int) $lastValidation;
            // Aceptar caché de hasta 5 segundos (misma request HTTP)
            if ($secondsSinceValidation < 5 && $this->get('id_user') !== null && $this->get('empresa_id') !== null) {
                return true;
            }
        }

        $result = $this->checkUserSession();

        if ($result) {
            $this->set($cacheKey, time());
        }

        return $result;
    }

    /**
     * Marca actividad de sesión y actualiza el timestamp de último acceso.
     * Cuando la sesión PHP es restaurada desde JWT, mantiene la sesión viva.
     */
    public function touchSessionActivity(): void
    {
        $this->set('_last_activity', time());

        // Regenerar CSRF token si no existe (sesión fue restaurada)
        if (empty($_SESSION['csrf_token'])) {
            $this->setCSRFToken();
        }
    }

    public function checkTokenHash(#[\SensitiveParameter] $token): bool
    {
        if (empty($token) || !is_string($token) || $token === 'undefined') {
            return false;
        }

        $tks = explode('.', $token);
        if (count($tks) !== 3) {
            return false;
        }

        $decoded = $this->decodeJWT($token);

        return isset($decoded['id_user'], $decoded['empresa_id']) && $decoded['exp'] > time();
    }

    /**
     * Destroys the session.
     */
    public function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    /**
     * Método estático para determinar si es una llamada API sin necesidad de instancia de Request.
     * Útil para usar en constructores y contextos donde Request no está disponible.
     */
    private function isStaticApiCall(): bool
    {
        $url = strtolower($_SERVER['REQUEST_URI'] ?? '');
        $acceptHeader = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // Comprobación prioritaria por URL
        if (str_contains($url, '/api/')) {
            return true;
        }

        // Comprobación por encabezado Accept, común para APIs RESTful
        if (str_contains($acceptHeader, 'application/json')) {
            return true;
        }

        // Comprobación por Content-Type para solicitudes con cuerpo (POST, PUT, PATCH)
        if ($contentType !== '') {
            $apiContentTypes = [
                'application/json',
                'application/xml',
                'multipart/form-data',
                'application/x-www-form-urlencoded',
            ];

            foreach ($apiContentTypes as $type) {
                if (!str_starts_with(strtolower($contentType), $type)) {
                    continue;
                }

                return true;
            }
        }

        // Si un cliente XHR (como fetch o XMLHttpRequest) establece X-Requested-With
        $headers = getallheaders();

        return isset($headers['X-Requested-With']) && strtolower($headers['X-Requested-With']) === 'xmlhttprequest';
    }
}
