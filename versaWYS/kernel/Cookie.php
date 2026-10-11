<?php

declare(strict_types=1);

namespace versaWYS\kernel;

class Cookie
{
    public function set(
        string $key,
        $value,
        int $time = 3600,
        string $domain = '',
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Strict', // Opciones: 'Lax', 'Strict', 'None'
        bool $strict_mode = true, // Habilitar modo estricto para prevenir ataques de fijación de sesión
    ): void {
        // Si $time es menor que time(), se considera un timestamp absoluto
        // Si no, se considera un tiempo relativo y se suma a time()
        $expires = $time > time() || $time === 0 ? $time : time() + $time;

        setcookie($key, $value, [
            'expires' => $expires,
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
        ]);
    }

    public function has($key): bool
    {
        return isset($_COOKIE[$key]);
    }

    public function get(string $key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
    }

    public function delete(string $key): void
    {
        global $config;
        if ($this->has($key)) {
            unset($_COOKIE[$key]);
            setcookie($key, '', [
                'expires' => -1,
                'path' => '/',
                'domain' => $config['session']['user_cookie']['domain'],
            ]);
        }
    }

    /**
     * Obtiene todas las cookies disponibles.
     *
     * @return array un array con todas las cookies disponibles
     */
    public function getAll(): array
    {
        return $_COOKIE;
    }

    /**
     * Clears all cookies.
     */
    public function clear(): void
    {
        foreach (array_keys($_COOKIE) as $key) {
            $this->delete($key);
        }
    }
}
