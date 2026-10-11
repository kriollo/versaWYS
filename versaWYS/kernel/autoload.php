<?php

declare(strict_types=1);

use versaWYS\kernel\ErrorHandler;

spl_autoload_register('register');

function register(string $class): void
{
    $class = str_replace('\\', '/', $class);
    if (file_exists($class . '.php')) {
        try {
            require_once $class . '.php';
        } catch (Throwable $e) {
            $error_message = sprintf("Autoload error loading class '%s': ", $class) . $e->getMessage();
            error_log($error_message);

            // Handle error according to debug mode if config is available
            global $config;
            if (isset($config['build']['debug']) && $config['build']['debug']) {
                // In debug mode, show detailed error
                if (class_exists('versaWYS\\kernel\\ErrorHandler')) {
                    $errorHandler = new ErrorHandler();
                    $errorHandler->handleException(new RuntimeException($error_message));
                } else {
                    // Fallback if ErrorHandler is not available yet
                    throw new RuntimeException($error_message, $e->getCode(), $e);
                }
            } else {
                // In production mode, log and continue
                error_log('AUTOLOAD ERROR in production: ' . $error_message);

                // Don't break the application, just log the error
            }
        }
    }
}

function registerRoutes(string $path): void
{
    global $config;

    $useCache = false;
    $cacheFile = '';

    if ($config !== null) {
        $useCache = $config['build']['cacheRoute'] ?? false;
        $cacheFile = $config['build']['cacheRouteFile'] ?? './versaWYS/cache/routes.compiled.php';
    }

    // Si el caché está habilitado y el archivo existe, usarlo
    if ($useCache && !empty($cacheFile)) {
        // Buscar primero el archivo minificado, luego el original
        $minifiedFile = str_replace('.compiled.php', '.compiled.min.php', $cacheFile);

        if (file_exists($minifiedFile)) {
            try {
                require_once $minifiedFile;

                return;
            } catch (Throwable $e) {
                $error_message =
                    sprintf("Error loading minified routes cache '%s': ", $minifiedFile) . $e->getMessage();
                error_log($error_message);

                // If debug mode, show detailed error; otherwise fallback to original cache
                if (
                    isset($config['build']['debug'])
                    && $config['build']['debug']
                    && class_exists('versaWYS\\kernel\\ErrorHandler')
                ) {
                    $errorHandler = new ErrorHandler();
                    $errorHandler->handleException(new RuntimeException($error_message));

                    return;
                }

                // Continue to try original cache file
            }
        }

        if (file_exists($cacheFile)) {
            try {
                require_once $cacheFile;

                return;
            } catch (Throwable $e) {
                $error_message = sprintf("Error loading routes cache '%s': ", $cacheFile) . $e->getMessage();
                error_log($error_message);

                // If debug mode, show detailed error; otherwise fallback to traditional loading
                if (
                    isset($config['build']['debug'])
                    && $config['build']['debug']
                    && class_exists('versaWYS\\kernel\\ErrorHandler')
                ) {
                    $errorHandler = new ErrorHandler();
                    $errorHandler->handleException(new RuntimeException($error_message));

                    return;
                }

                // Continue to traditional loading
            }
        }
    }

    // Cargar rutas de forma tradicional (sin caché)
    if (!is_dir($path)) {
        error_log('Routes directory not found: ' . $path);
        if (
            isset($config['build']['debug'])
            && $config['build']['debug']
            && class_exists('versaWYS\\kernel\\ErrorHandler')
        ) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleException(new RuntimeException('Routes directory not found: ' . $path));
        }

        return;
    }

    $files = scandir($path);
    if ($files === false) {
        error_log('Cannot scan routes directory: ' . $path);
        if (
            isset($config['build']['debug'])
            && $config['build']['debug']
            && class_exists('versaWYS\\kernel\\ErrorHandler')
        ) {
            $errorHandler = new ErrorHandler();
            $errorHandler->handleException(new RuntimeException('Cannot scan routes directory: ' . $path));
        }

        return;
    }

    foreach ($files as $file) {
        if (!($file !== '.' && $file !== '..')) {
            continue;
        }

        $fullPath = $path . '/' . $file;
        if (is_dir($fullPath)) {
            registerRoutes($fullPath);
        } else {
            try {
                require_once $fullPath;
            } catch (Throwable $e) {
                $error_message = sprintf("Error loading route file '%s': ", $fullPath) . $e->getMessage();
                error_log($error_message);

                // If debug mode, show detailed error
                if (
                    isset($config['build']['debug'])
                    && $config['build']['debug']
                    && class_exists('versaWYS\\kernel\\ErrorHandler')
                ) {
                    $errorHandler = new ErrorHandler();
                    $errorHandler->handleException(new RuntimeException($error_message));
                }

                // In production, just log and continue with other files
            }
        }
    }
}
