<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Exception;
use Twig\Error\RuntimeError;
use versaWYS\kernel\cli\CLIErrorHandler;

class Response
{
    /**
     * Sends a JSON response.
     *
     * @param array $data the data to be encoded as JSON
     * @param int $code the HTTP response code (default: 200)
     *
     * @return mixed
     */
    public static function json(array $data, int $code = 200): bool|string
    {
        // Un warning previo no invalida la respuesta. Solo en debug se muestra, para detectarlo al desarrollar;
        // en producción queda en el log y la respuesta sigue su curso sin exponer rutas internas.
        $last = error_get_last();
        if ($last !== null) {
            error_clear_last();
            if (($GLOBALS['config']['build']['debug'] ?? false) === true) {
                self::jsonError(['success' => 0, 'message' => 'Error en la petición', 'errors' => $last, 'code' => 500], 500);

                return false;
            }
            Loggin::warning('Warning previo a la respuesta: ' . $last['message'], 'response', $last);
        }

        if (is_array($data) || is_object($data)) {
            // En producción una respuesta de error no lleva el detalle técnico: es habitual que el
            // controlador devuelva 'error' => $exception->getMessage(), que expone SQL y rutas.
            if (in_array($data['success'] ?? 1, [0, false, '0'], true) && ($GLOBALS['config']['build']['debug'] ?? false) !== true) {
                unset($data['error'], $data['exception'], $data['trace'], $data['file'], $data['line']);
            }

            header('Content-Type: application/json');
            http_response_code($code);

            // Para peticiones HEAD, solo enviamos headers, no el cuerpo
            if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                echo json_encode($data);
            }
        } else {
            self::jsonError(['message' => 'Error en la petición', 'code' => 500], 500);

            return false;
        }

        return true;
    }

    public static function jsonError(array $data, int $code = 500): bool
    {
        global $twig, $config;

        // Si estamos en CLI, usar el manejador específico de CLI
        if (PHP_SAPI === 'cli') {
            self::handleCLIError($data, $code);

            return false; // Nunca debería llegar aquí
        }

        $data = self::withoutInternals($data);

        // === SPA-AWARE: Si es una petición SPA, devolver error como JSON SPA renderizable ===
        if (Request::isSPARequestStatic()) {
            return self::jsonErrorForSPA($data, $code);
        }

        // Protección contra loops infinitos de errores
        static $errorCount = 0;
        $errorCount++;

        if ($errorCount > 2) {
            // Si ya hemos tenido demasiados errores, mostrar error simple sin Twig
            http_response_code($code);

            // Para peticiones HEAD, solo enviamos headers, no el cuerpo
            if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                echo self::renderSimpleError($data);
            }

            exit();
        }

        http_response_code($code);

        // Mejorar la información de error para debugging
        if (isset($config['build']['debug']) && $config['build']['debug']) {
            Loggin::error(
                'Error manejado en Response::jsonError: ' . ($data['message'] ?? 'Sin mensaje'),
                'ErrorHandler',
                $data,
            );
        }

        try {
            // Verificar si este es un error relacionado con las plantillas de error o Twig
            $message = $data['message'] ?? '';
            $file = $data['file'] ?? '';
            $type = $data['type'] ?? '';

            // Detectar errores de Twig específicamente relacionados con el renderizado de plantillas
            $isTwigTemplateError =
                str_contains($message, 'debugError.twig')
                || str_contains($message, 'simpleError.twig')
                || str_contains($message, 'emergencyError.twig')
                || str_contains($file, 'Environment.php') && str_contains($file, 'eval()')
                || str_contains($message, 'Key ')
                && str_contains($message, 'does not exist')
                && str_contains($message, '.twig')
                || str_contains($message, 'sequence/mapping')
                && str_contains($message, 'does not exist')
                && str_contains($message, '.twig');

            // Si es un error específico de plantilla de Twig, usar directamente renderSimpleError
            if ($isTwigTemplateError) {
                // Para peticiones HEAD, solo enviamos headers, no el cuerpo
                if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                    echo self::renderSimpleError($data);
                }

                exit();
            }

            // ✅ Verificar que Twig esté disponible antes de usarlo
            if ($twig === null || !is_object($twig)) {
                // Para peticiones HEAD, solo enviamos headers, no el cuerpo
                if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                    echo self::renderSimpleError($data);
                }

                exit();
            }

            // Para peticiones HEAD, solo enviamos headers, no el cuerpo
            if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                // Usar renderDirect para evitar la lógica SPA que envuelve en JSON
                echo $twig->renderDirect('versaWYS/debugError.twig', ['data' => $data]);
            }
        } catch (RuntimeError $e) {
            // Error específico de Twig Runtime (como variables no definidas en strict mode)
            if (str_contains($e->getMessage(), 'does not exist') || str_contains($e->getMessage(), 'Key ')) {
                // Es un error de variable no definida en la plantilla, usar renderSimpleError
                // Para peticiones HEAD, solo enviamos headers, no el cuerpo
                if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                    echo self::renderSimpleError($data);
                }

                exit();
            }

            // Si es otro tipo de error de Twig, continuar con el fallback normal
            throw $e;
        } catch (Exception) {
            // Si falla la plantilla completa, intentar con la simple
            try {
                // Para peticiones HEAD, solo enviamos headers, no el cuerpo
                if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                    // Usar renderDirect para evitar la lógica SPA
                    echo $twig->renderDirect('versaWYS/simpleError.twig', ['data' => $data]);
                }
            } catch (Exception) {
                // Si falla la simple, intentar con la de emergencia
                try {
                    // Para peticiones HEAD, solo enviamos headers, no el cuerpo
                    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                        // Usar renderDirect para evitar la lógica SPA
                        echo $twig->renderDirect('versaWYS/emergencyError.twig', ['data' => $data]);
                    }
                } catch (Exception) {
                    // Si todo falla, mostrar error HTML básico
                    // Para peticiones HEAD, solo enviamos headers, no el cuerpo
                    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                        echo self::renderSimpleError($data);
                    }
                }
            }
        }

        exit();
    }

    /**
     * Maneja errores para peticiones SPA devolviendo JSON en formato SPA renderizable.
     * Esto permite que el frontend SPA muestre el error dentro del layout existente
     * en lugar de recibir HTML crudo que no puede procesar.
     *
     * Inspirado en Inertia.js: los errores se devuelven como contenido renderizable,
     * manteniendo la experiencia SPA intacta.
     */
    private static function jsonErrorForSPA(array $data, int $code): bool
    {
        global $config;

        $isDebug = isset($config['build']['debug']) && $config['build']['debug'];
        $message = htmlspecialchars((string) ($data['message'] ?? 'Error interno'), ENT_QUOTES, 'UTF-8');

        if ($isDebug) {
            // En debug: mostrar detalles del error dentro del SPA
            $file = htmlspecialchars((string) ($data['real_file'] ?? $data['file'] ?? ''), ENT_QUOTES, 'UTF-8');
            $line = htmlspecialchars((string) ($data['real_line'] ?? $data['line'] ?? ''), ENT_QUOTES, 'UTF-8');
            $type = htmlspecialchars((string) ($data['type'] ?? 'Error'), ENT_QUOTES, 'UTF-8');

            $errorHtml =
                '<div class="flex flex-col justify-center items-center px-6 mx-auto min-h-[60vh] xl:px-0">'
                . '<div class="text-center xl:max-w-4xl w-full">'
                . '<h1 class="mb-3 text-2xl font-bold leading-tight text-red-600 sm:text-4xl lg:text-5xl">'
                . 'Error '
                . $code
                . ' - Debug Mode</h1>'
                . '<p class="mb-3 text-lg text-gray-500 dark:text-gray-400">'
                . $type
                . '</p>'
                . '<pre class="mb-5 text-left bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-auto text-sm max-h-96">'
                . $message
                . '</pre>';

            if ($file !== '') {
                $errorHtml .=
                    '<div class="mb-5 text-left bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg text-sm">'
                    . '<strong>Archivo:</strong> '
                    . $file
                    . ($line !== '' ? '<br><strong>Línea:</strong> ' . $line : '')
                    . '</div>';
            }

            $errorHtml .=
                '<div class="flex justify-center mt-8">'
                . '<a onclick="history.go(-1)" data-spa class="px-8 py-3 bg-gray-600 text-white rounded-xl font-bold hover:bg-gray-700 transition-all cursor-pointer">'
                . 'Volver</a></div></div></div>';
        } else {
            // En producción: error genérico
            $errorHtml =
                '<div class="flex flex-col justify-center items-center px-6 mx-auto min-h-[60vh] xl:px-0">'
                . '<div class="text-center xl:max-w-4xl">'
                . '<h1 class="mb-3 text-2xl font-bold leading-tight text-gray-900 sm:text-4xl lg:text-5xl dark:text-white">'
                . 'Error interno</h1>'
                . '<p class="mb-5 text-base font-normal text-gray-500 md:text-lg dark:text-gray-400">'
                . 'Ha ocurrido un error inesperado. Por favor, inténtelo de nuevo más tarde.</p>'
                . '<div class="flex justify-center mt-8">'
                . '<a onclick="history.go(-1)" data-spa class="px-8 py-3 bg-gray-600 text-white rounded-xl font-bold hover:bg-gray-700 transition-all cursor-pointer">'
                . 'Volver</a></div></div></div>';
        }

        // Log del error
        Loggin::error('Error SPA manejado: ' . ($data['message'] ?? 'Sin mensaje'), 'Response::jsonErrorForSPA', $data);

        self::json([
            'type' => 'html',
            'content' => $errorHtml,
            'scripts' => [],
            'styles' => [],
            'urlActual' => $_SERVER['REQUEST_URI'] ?? '',
        ], $code);

        exit();
    }

    public static function textPlain(string $data, int $code = 200): bool|string
    {
        if (error_get_last() !== null) {
            $data = [
                'success' => 0,
                'message' => 'Error en la petición',
                'errors' => error_get_last(),
                'code' => 500,
            ];
            $code = 500;
            self::jsonError($data, $code);

            return false;
        }

        header('Content-Type: text/plain');
        http_response_code($code);

        // Para peticiones HEAD, solo enviamos headers, no el cuerpo
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            echo $data;
        }

        return true;
    }

    /**
     * Redirects the user to the specified route.
     *
     * @param string $route the route to redirect to
     */
    public static function redirect(string $route): void
    {
        // Detectar si es una petición SPA para devolver JSON redirección en lugar de 302
        // Esto previene problemas de protocolo (http/https) en el navegador al seguir redirects nativos
        if (Request::isSPARequestStatic()) {
            header('Content-Type: application/json');
            echo
                json_encode([
                    'type' => 'redirect',
                    'url' => $route,
                ])
            ;
            exit();
        }

        header('Location: ' . $route);
    }

    /**
     * Registra errores en un archivo de log para debugging.
     */
    private static function logError(array $errorData): void
    {
        Loggin::error('Ha ocurrido un error: ' . $errorData['message'], 'ErrorHandler', $errorData);
    }

    /**
     * Renderiza un error simple sin usar Twig (para evitar loops infinitos).
     */
    private static function renderSimpleError(array $data): string
    {
        $message = htmlspecialchars((string) ($data['message'] ?? 'Unknown error'));
        $type = htmlspecialchars((string) ($data['type'] ?? 'Error'));

        // Determinar archivo y línea de forma robusta
        $file = 'Unknown file';
        $line = 'Unknown line';

        if (isset($data['real_file'])) {
            $file = htmlspecialchars((string) $data['real_file']);
        } elseif (isset($data['real_origin']['file'])) {
            $file = htmlspecialchars((string) $data['real_origin']['file']);
        } elseif (isset($data['template_file'])) {
            $file = htmlspecialchars((string) $data['template_file']) . ' (Template)';
        } elseif (isset($data['file'])) {
            $file = htmlspecialchars((string) $data['file']);
        }

        if (isset($data['real_line'])) {
            $line = htmlspecialchars((string) $data['real_line']);
        } elseif (isset($data['real_origin']['line'])) {
            $line = htmlspecialchars((string) $data['real_origin']['line']);
        } elseif (isset($data['template_line'])) {
            $line = htmlspecialchars((string) $data['template_line']);
        } elseif (isset($data['line'])) {
            $line = htmlspecialchars((string) $data['line']);
        }

        $function = '';
        if (isset($data['real_function'])) {
            $function = '<br><strong>Function:</strong> ' . htmlspecialchars((string) $data['real_function']);
        } elseif (isset($data['real_origin']['function'])) {
            $function = '<br><strong>Function:</strong> ' . htmlspecialchars((string) $data['real_origin']['function']);
        }

        $timestamp = isset($data['timestamp']) ? htmlspecialchars((string) $data['timestamp']) : date('Y-m-d H:i:s');

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>VersaWYS Error</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
                .error-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
                .error-header { background: #dc2626; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
                .error-content { line-height: 1.6; }
                .code { background: #f8f9fa; padding: 10px; border-left: 4px solid #dc2626; margin: 10px 0; font-family: monospace; }
                .location { background: #fef3c7; padding: 10px; border-radius: 4px; margin: 10px 0; }
                .section { margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 4px; }
            </style>
        </head>
        <body>
            <div class='error-container'>
                <div class='error-header'>
                    <h1>🚨 VersaWYS Framework Error</h1>
                    <p>{$type}</p>
                    <small>{$timestamp}</small>
                </div>
                <div class='error-content'>
                    <div class='section'>
                        <h3>Error Message:</h3>
                        <div class='code'>{$message}</div>
                    </div>

                    <div class='section'>
                        <h3>Location:</h3>
                        <div class='location'>
                            <strong>File:</strong> {$file}<br>
                            <strong>Line:</strong> {$line}
                            {$function}
                        </div>
                    </div>

                    <div class='section'>
                        <p><em>Note: Simplified error view to prevent infinite loops. Check logs for detailed information.</em></p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Maneja errores específicamente para CLI.
     */
    private static function handleCLIError(array $data, int $code): void
    {
        // Importar CLIErrorHandler si existe
        if (class_exists('\versaWYS\kernel\cli\CLIErrorHandler')) {
            // Determinar el tipo de error y usar el manejador apropiado
            $type = $data['type'] ?? '';
            $message = $data['message'] ?? '';

            if (str_contains($type, 'Database') || str_contains($message, 'SQLSTATE')) {
                $context = [
                    'file' => $data['real_file'] ?? $data['file'] ?? 'Desconocido',
                    'line' => $data['real_line'] ?? $data['line'] ?? 'Desconocida',
                    'operation' => $data['real_function'] ?? 'Desconocida',
                    'query' => $data['debug_info']['query'] ?? null,
                ];

                $exception = new Exception($message);
                CLIErrorHandler::handleDatabaseError($exception, $context);
            } else {
                $exception = new Exception($message);
                CLIErrorHandler::handleGeneralError($exception, $type);
            }
        } else {
            // Fallback si CLIErrorHandler no está disponible
            echo "\n=== ERROR EN CLI ===\n";
            echo 'Tipo: ' . ($data['type'] ?? 'Desconocido') . "\n";
            echo 'Mensaje: ' . ($data['message'] ?? 'Sin mensaje') . "\n";
            echo 'Archivo: ' . ($data['real_file'] ?? $data['file'] ?? 'Desconocido') . "\n";
            echo 'Línea: ' . ($data['real_line'] ?? $data['line'] ?? 'Desconocida') . "\n";
            echo "==================\n\n";
            exit($code === 200 ? 0 : 1);
        }
    }

    /**
     * En producción una respuesta de error solo lleva lo que el usuario necesita. Rutas de archivos,
     * trazas, SQL y mensajes del driver quedan en el log, nunca en la respuesta.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function withoutInternals(array $data): array
    {
        if (($GLOBALS['config']['build']['debug'] ?? false) === true) {
            return $data;
        }

        return array_intersect_key($data, array_flip(['success', 'message', 'errors', 'code', 'type', 'timestamp']));
    }
}
