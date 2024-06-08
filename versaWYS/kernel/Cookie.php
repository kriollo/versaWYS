<?php

declare(strict_types=1);

namespace versaWYS\kernel;

class Cookie
{
    public static function set(string $key, $value, int $time = 3600, string $domain = '', bool $secure = false, bool $httpOnly = true): void
    {
        setcookie($key, $value, $time, '/', $domain, $secure, $httpOnly);
    }

    public static function has($key): bool
    {
        return isset($_COOKIE[$key]);
    }

    public static function get(string $key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
    }

    public static function delete(string $key): void
    {
        global $config;
        if (self::has($key)) {
            unset($_COOKIE[$key]);
            setcookie($key, '', -1, '/', $config['session']['user_cookie']['domain']);
        }
    }

    /**
     * Obtiene todas las cookies disponibles.
     *
     * @return array Un array con todas las cookies disponibles.
     */
    public static function getAll(): array
    {
        return $_COOKIE;
    }

    /**
     * Clears all cookies.
     */
    public static function clear(): void
    {
        foreach ($_COOKIE as $key => $value) {
            self::delete($key);
        }
    }
}
