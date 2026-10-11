<?php

declare(strict_types=1);

namespace versaWYS\kernel;

/**
 * Rate Limiting Middleware - Funcionalidad global del framework.
 *
 * Proporciona métodos para aplicar limitación de tasa en diferentes contextos:
 * - API endpoints
 * - Intentos de login
 * - Subida de archivos
 * - Endpoints sensibles
 */
class RateLimitMiddleware
{
    /**
     * Rate limiting para endpoints de API.
     */
    public static function checkApiLimit(): array|true
    {
        global $request;

        $ip = $request->getIp();
        $endpoint = $request->getUrl();
        $identifier = $ip . ':' . parse_url($endpoint, PHP_URL_PATH);

        return SecurityUtils::checkRateLimit($identifier, 'api');
    }

    /**
     * Rate limiting para intentos de login.
     */
    public static function checkLoginLimit(): array|true
    {
        global $request;

        $ip = $request->getIp();

        return SecurityUtils::checkRateLimit($ip, 'login');
    }

    /**
     * Rate limiting para subida de archivos.
     */
    public static function checkUploadLimit(): array|true
    {
        global $request;

        $ip = $request->getIp();
        $endpoint = $request->getUrl();
        $identifier = $ip . ':' . parse_url($endpoint, PHP_URL_PATH);

        return SecurityUtils::checkRateLimit($identifier, 'upload');
    }

    /**
     * Rate limiting para endpoints sensibles.
     */
    public static function checkSensitiveLimit(): array|true
    {
        global $request;

        $ip = $request->getIp();
        $endpoint = $request->getUrl();
        $identifier = $ip . ':' . parse_url($endpoint, PHP_URL_PATH);

        return SecurityUtils::checkRateLimit($identifier, 'sensitive');
    }

    /**
     * Rate limiting global (aplicado automáticamente por el Router).
     */
    public static function checkGlobalLimit(): array|true
    {
        global $request;

        return SecurityUtils::checkGlobalRateLimit($request);
    }
}
