<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use versaWYS\kernel\helpers as helpers;

class ConfigManager
{
    protected static string $pathConfig = 'versaWYS/kernel/config/config.json';

    /**
     * Ruta del archivo de configuración. VERSAWYS_CONFIG permite usar otro (tests, staging).
     */
    public static function path(): string
    {
        $path = getenv('VERSAWYS_CONFIG');

        return is_string($path) && $path !== '' ? $path : self::$pathConfig;
    }

    public static function getConfig(): array
    {
        if (!file_exists(self::path())) {
            return [];
        }
        $config = file_get_contents(self::path());

        return json_decode($config, true) ?? [];
    }

    public static function saveConfig(array $config): void
    {
        $config = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents(self::path(), $config);
        chmod(self::path(), 0o660);
    }

    public static function setDebug($params): void
    {
        if (!isset($params)) {
            CLIErrorHandler::warning('Error: Debe especificar un valor para debug');
            exit(1);
        }

        if (!in_array($params, ['true', 'false'], strict: true)) {
            CLIErrorHandler::warning('Error: El valor de debug debe ser true o false');
            exit(1);
        }

        $config = self::getConfig();
        $debugValue = $params === 'true';
        $config['build']['debug'] = $debugValue;
        self::saveConfig($config);

        $status = $debugValue ? 'activado' : 'desactivado';
        CLIErrorHandler::success("Modo debug {$status} exitosamente.");
    }

    public static function setTemplateCache($params): void
    {
        if (!isset($params)) {
            CLIErrorHandler::warning('Error: Debe especificar un valor para templateCache');
            exit(1);
        }

        if (!in_array($params, ['true', 'false'], strict: true)) {
            CLIErrorHandler::warning('Error: El valor de templateCache debe ser true o false');
            exit(1);
        }

        $config = self::getConfig();
        $cacheValue = $params === 'true';
        $config['twig']['cache'] = $cacheValue;
        self::saveConfig($config);

        $status = $cacheValue ? 'activado' : 'desactivado';
        CLIErrorHandler::success("Cache de templates {$status} exitosamente.");
    }

    public static function clearCache(): void
    {
        $config = self::getConfig();

        $pathCahe = $config['twig']['compiled_dir'] ?? 'app/templates/.cache';

        if (str_starts_with($pathCahe, './')) {
            $pathCahe = substr($pathCahe, 2);
        }

        helpers\FilesFunction::destroyAllDir($pathCahe);

        CLIHelper::ensureDirectory($pathCahe, 0o775);

        CLIErrorHandler::success('Cache eliminado');
    }

    public static function initConfig(): void
    {
        if (file_exists(self::path())) {
            CLIErrorHandler::info('El archivo de configuración ya existe en: ' . self::path());

            return;
        }

        // Misma fuente que usa el runtime para los valores por defecto: no pueden divergir
        $defaults = require __DIR__ . '/../config/config.default.php';
        $defaults['session']['key'] = bin2hex(random_bytes(32));
        $defaults['session']['user_cookie']['key_encript'] = bin2hex(random_bytes(32));
        $template = json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Asegurarse de que el directorio de configuración exista
        $configPath = self::path();
        /** @var string $configDir */
        $configDir = dirname($configPath);

        try {
            CLIHelper::ensureDirectory($configDir, 0o775);

            if (file_put_contents(self::path(), $template) !== false) {
                chmod(self::path(), 0o660);
                CLIErrorHandler::success('Archivo de configuración creado exitosamente en: ' . self::path());

                // Token de un solo uso que exige el instalador web (/install)
                $token = bin2hex(random_bytes(16));
                file_put_contents($configDir . '/install.token', $token);
                chmod($configDir . '/install.token', 0o660);
                CLIErrorHandler::info('Token para el instalador web (/install): ' . $token);
            } else {
                throw new \RuntimeException('No se pudo escribir el archivo');
            }
        } catch (\Throwable $e) {
            CLIErrorHandler::warning('Error: No se pudo crear el archivo de configuración: ' . $e->getMessage());
        }
    }
}
