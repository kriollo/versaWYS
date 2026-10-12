<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;

class CacheManager
{
    public static function handleCacheCommands(array $params, bool $verbose = false): void
    {
        if ($params === []) {
            self::showError('Se requiere especificar una acción para cache.');
            echo CLIErrorHandler::colorize('Ejemplo: ', 'cyan') . "php versaCLI cache:clear\n";
            HelpView::printHelp();

            return;
        }

        $allowedActions = ['clear', 'clearall', 'status', 'routes', 'twig'];
        $action = self::validateAction($params[0], $allowedActions, 'cache');

        if ($verbose) {
            CLIErrorHandler::info('Iniciando operación de cache: ' . $action);
        }

        try {
            match ($action) {
                'clear', 'clearall' => self::clearAllCache(),
                'status' => self::cacheStatus(),
                'routes' => self::clearRoutesCache(),
                'twig' => self::clearTwigCache(),
                default => self::showError('Acción de cache no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Gestionando cache: ' . $action);
        }
    }

    public static function clearAllCache(): void
    {
        echo CLIErrorHandler::colorize('🧹 Limpiando todo el cache...', 'cyan') . "\n\n";

        $cacheCleared = 0;

        // Limpiar cache de rutas
        if (self::clearRoutesCache(false)) {
            $cacheCleared++;
        }

        // Limpiar cache de Twig
        if (self::clearTwigCache(false)) {
            $cacheCleared++;
        }

        // Limpiar cache de rate limits
        if (self::clearRateLimitsCache(false)) {
            $cacheCleared++;
        }

        echo "\n" . str_repeat('═', 40) . "\n";

        if ($cacheCleared > 0) {
            CLIErrorHandler::success(sprintf('Cache limpiado exitosamente (%s tipos de cache).', $cacheCleared));
        } else {
            CLIErrorHandler::info('No se encontraron archivos de cache para limpiar.');
        }
    }

    private static function cacheStatus(): void
    {
        echo CLIErrorHandler::colorize('📊 Estado del Cache', 'cyan') . "\n";
        echo str_repeat('═', 40) . "\n\n";

        // Leer configuración para obtener rutas correctas
        $configPath = 'versaWYS/kernel/config/config.json';
        $config = [];
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true) ?? [];
        }

        // Cache de rutas
        $routesCache = 'versaWYS/cache/routes.compiled.php';
        $routesStatus = file_exists($routesCache)
            ? '🟢 Activo (' . self::formatBytes(filesize($routesCache)) . ')'
            : '🔴 Vacío';
        echo CLIErrorHandler::colorize('Rutas: ', 'yellow') . ($routesStatus . PHP_EOL);

        // Cache de Twig (usar ruta de configuración)
        $twigCacheDir = $config['twig']['compiled_dir'] ?? 'app/templates/.cache';
        // Convertir ruta relativa a absoluta si es necesario
        if (str_starts_with($twigCacheDir, './')) {
            $twigCacheDir = substr($twigCacheDir, 2);
        }

        if (is_dir($twigCacheDir)) {
            $twigFiles = glob($twigCacheDir . '/*');
            $twigStatus = count($twigFiles) > 0 ? '🟢 Activo (' . count($twigFiles) . ' archivos)' : '🔴 Vacío';
        } else {
            $twigStatus = '🔴 Directorio no existe (' . $twigCacheDir . ')';
        }

        echo CLIErrorHandler::colorize('Twig: ', 'yellow') . ($twigStatus . PHP_EOL);

        // Cache de rate limits
        $rateLimitsDir = 'versaWYS/cache/rate_limits';
        if (is_dir($rateLimitsDir)) {
            $rateLimitFiles = glob($rateLimitsDir . '/*');
            $rateLimitStatus = count($rateLimitFiles) > 0
                ? '🟢 Activo (' . count($rateLimitFiles) . ' archivos)'
                : '🔴 Vacío';
        } else {
            $rateLimitStatus = '🔴 Directorio no existe';
        }

        echo CLIErrorHandler::colorize('Rate Limits: ', 'yellow') . ($rateLimitStatus . PHP_EOL);

        echo "\n";
    }

    private static function clearRoutesCache(bool $showMessage = true): bool
    {
        // Una sola implementación: borra el archivo compilado y el minificado, y desactiva
        // build.cacheRoute para que las rutas vuelvan a leerse desde app/routes
        RouteManager::clearCache();

        return true;
    }

    private static function clearTwigCache(bool $showMessage = true): bool
    {
        // Leer configuración para obtener la ruta correcta del cache de Twig
        $configPath = 'versaWYS/kernel/config/config.json';
        $config = [];
        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true) ?? [];
        }

        $twigCacheDir = $config['twig']['compiled_dir'] ?? 'app/templates/.cache';

        // Convertir ruta relativa a absoluta si es necesario
        if (str_starts_with($twigCacheDir, './')) {
            $twigCacheDir = substr($twigCacheDir, 2);
        }

        if (!is_dir($twigCacheDir)) {
            if ($showMessage) {
                CLIErrorHandler::info('Directorio de cache de Twig no existe: ' . $twigCacheDir);
            }

            return false;
        }

        $files = glob($twigCacheDir . '/*');
        $cleared = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $cleared++;
                }
            } elseif (is_dir($file)) {
                // Para directorios, eliminamos recursivamente el contenido
                self::deleteDirectory($file);
                $cleared++;
            }
        }

        if ($showMessage) {
            if ($cleared > 0) {
                CLIErrorHandler::success(sprintf(
                    'Cache de Twig limpiado (%d archivos) desde: %s',
                    $cleared,
                    $twigCacheDir,
                ));
            } else {
                CLIErrorHandler::info('No hay archivos de cache de Twig para limpiar en: ' . $twigCacheDir);
            }
        }

        return $cleared > 0;
    }

    private static function clearRateLimitsCache(bool $showMessage = true): bool
    {
        $rateLimitsDir = 'versaWYS/cache/rate_limits';

        if (!is_dir($rateLimitsDir)) {
            if ($showMessage) {
                CLIErrorHandler::info('Directorio de cache de rate limits no existe.');
            }

            return false;
        }

        $files = glob($rateLimitsDir . '/*');
        $cleared = 0;

        foreach ($files as $file) {
            if (!(is_file($file) && unlink($file))) {
                continue;
            }

            $cleared++;
        }

        if ($showMessage) {
            if ($cleared > 0) {
                CLIErrorHandler::success(sprintf('Cache de rate limits limpiado (%d archivos).', $cleared));
            } else {
                CLIErrorHandler::info('No hay archivos de cache de rate limits para limpiar.');
            }
        }

        return $cleared > 0;
    }

    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < (count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private static function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                self::deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        return rmdir($dir);
    }

    private static function validateAction(string $action, array $allowedActions, string $command): string
    {
        if (!in_array($action, $allowedActions, strict: true)) {
            self::showError(sprintf("Acción '%s' no válida para %s.", $action, $command));
            echo CLIErrorHandler::colorize('Acciones permitidas: ', 'cyan') . implode(', ', $allowedActions) . "\n";
            exit(1);
        }

        return $action;
    }

    private static function showError(string $message): void
    {
        // No termina el proceso: quien llama puede mostrar a continuación el uso correcto
        CLIErrorHandler::warning($message);
        CLIErrorHandler::$failed = true;
    }
}
