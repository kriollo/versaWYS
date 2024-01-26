<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/kernel/session.php
 *
 * This class represents a session in the application.
 * It provides methods for managing session data, generating and decoding JWT tokens,
 * and handling login attempts.
 */
declare(strict_types=1);

namespace versaWYS\kernel;

use versaWYS\kernel\helpers\Functions;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;

/**
 * Class Session
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
    public function __construct($lifetime = 3600)
    {
        // Set the session cookie lifetime
        ini_set('session.cookie_lifetime', $lifetime);

        // Set the session garbage collector lifetime
        ini_set('session.gc_maxlifetime', $lifetime);

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
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
        session_regenerate_id();
    }

    /**
     * Checks if a session exists.
     *
     * @return bool True if a session exists, false otherwise
     */
    public function checkExistSession(): bool
    {
        return session_status() == PHP_SESSION_ACTIVE;
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
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Checks if a session variable exists.
     *
     * @param string $key The key of the session variable
     * @return bool True if the session variable exists, false otherwise
     */
    public function has($key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Gets the value of a session variable.
     *
     * @param string $key The key of the session variable
     * @param mixed $default The default value to return if the session variable does not exist (default: null)
     * @return mixed The value of the session variable or the default value
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Obtiene todos los datos de la sesión.
     *
     * @return array Los datos de la sesión.
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
    public function clear()
    {
        session_unset();
    }

    /**
     * Gets the login attempts for a specific email address.
     *
     * @param string $email The email address
     * @return array The login attempts for the email address
     */
    public function getAttempts(string $email): array
    {
        $attempts = $this->get('login_user_attempts');
        if (null === $attempts)
            return [];
        else {
            if (array_key_exists($email, $attempts))
                return $attempts[$email];
            else
                return [];
        }
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
        if (null !== $this->get('login_user_attempts'))
            return $this->get('login_user_attempts');
        else
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
     * @return array The updated login attempts
     */
    public function setAttemptsSession(array $attempts, string $email): array
    {
        if (!array_key_exists($email, $attempts)) {
            $attempts[$email] = [
                'attempts' => 1,
                'time' => null
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
     * @return bool True if the maximum attempts have been reached, false otherwise
     */
    public function maximumAttempts(array $attempts, string $email): bool
    {
        global $config;
        if ($attempts[$email]['attempts'] >= $config['login_attempt']['max']) {

            if (null === $attempts[$email]['time'])
                $attempts[$email]['time'] = time() + $config['login_attempt']['time'];

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
     * @return string The generated JWT
     */
    public function generateJWT(array $user, string $remember): string
    {
        global $config;

        $lifetime = ($remember === 'off' ? $config['session']['user_cookie']['lifetime'] : $config['session']['user_cookie']['lifetime_remember']);

        $payload = [
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $lifetime,
            'id_user' => $user['id']
        ];

        $jwt = \Firebase\JWT\JWT::encode($payload, $config['session']['key'], 'HS256');

        return $jwt;
    }

    /**
     * Decodes a JSON Web Token (JWT).
     *
     * @param string $jwt The JWT to decode
     * @return array The decoded JWT
     */
    public function decodeJWT(string $jwt): array
    {
        global $config;

        $decoded = \Firebase\JWT\JWT::decode($jwt, new Key($config['session']['key'], 'HS256'));

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

        $jwt = $this->generateJWT($user, $remember);

        $lifetime = time() + ($remember === 'off' ? $config['session']['user_cookie']['lifetime'] : $config['session']['user_cookie']['lifetime_remember']);
        if ($config['session']['user_cookie']['enable']) {
            $cookie->set(
                'tknHash',
                $jwt,
                $lifetime,
                $config['session']['user_cookie']['domain'],
                $config['session']['user_cookie']['secure'],
                $config['session']['user_cookie']['http_only']
            );
        }
    }

    /**
     * Checks if a user session exists.
     *
     * @return bool True if a user session exists, false otherwise
     */
    public function checkUserSession(): bool
    {
        global $config, $cookie;

        if ($config['session']['user_cookie']['enable']) {

            if ($cookie->has('tknHash')) {
                $jwt = $cookie->get('tknHash');
                $decoded = $this->decodeJWT($jwt);
                if ($decoded['exp'] > time()) {

                    if (!$this->checkExistSession() || !$this->has('id_user')) {
                        $this->regenerate();
                        self::set('id_user', $decoded['id_user']);
                    }

                    return true;
                }
            }
        }

        // if (!$this->checkExistSession() || !$this->has('id_user')) {
        //     echo ('no existe');
        //     $this->regenerate();
        //     self::set('id_user', $decoded['id_user']);

        //     return true;
        // }

        return false;
    }

    /**
     * Destroys the session.
     */
    public function destroy(): void
    {
        session_unset();
        session_destroy();
    }
}
