<?php

declare(strict_types=1);

namespace versaWYS\kernel;

class Response
{
    /**
     * Sends a JSON response.
     *
     * @param array $data The data to be encoded as JSON.
     * @param int $code The HTTP response code (default: 200).
     * @return mixed
     */
    public static function json(array $data, int $code = 200): bool|string
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
        if (is_array($data) || is_object($data)) {
            header('Content-Type: application/json');
            http_response_code($code);
            print json_encode($data);
        } else {
            self::jsonError(['message' => 'Error en la petición', 'code' => 500], 500);
            return false;
        }
        return true;
    }

    public static function jsonError(array $data, int $code = 500): bool
    {
        global $twig, $config;

        // Protección contra loops infinitos de errores
        static $errorCount = 0;
        $errorCount++;

        if ($errorCount > 2) {
            // Si ya hemos tenido demasiados errores, mostrar error simple sin Twig
            http_response_code($code);
            echo self::renderSimpleError($data);
            exit();
        }

        http_response_code($code);

        // Mejorar la información de error para debugging
        if (isset($config['build']['debug']) && $config['build']['debug']) {
            // Agregar información adicional de contexto si está disponible
            if (!isset($data['context'])) {
                $data['context'] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ];
            }

            // Log del error para debugging
            self::logError($data);
        }

        try {
            // Verificar si este es un error relacionado con las plantillas de error o Twig
            $message = $data['message'] ?? '';
            $file = $data['file'] ?? '';
            $type = $data['type'] ?? '';

            // Detectar errores de Twig específicamente relacionados con el renderizado de plantillas
            $isTwigTemplateError = (
                strpos($message, 'debugError.twig') !== false ||
                strpos($message, 'simpleError.twig') !== false ||
                strpos($message, 'emergencyError.twig') !== false ||
                (strpos($file, 'Environment.php') !== false && strpos($file, 'eval()') !== false) ||
                (strpos($message, 'Key ') !== false && strpos($message, 'does not exist') !== false && strpos($message, '.twig') !== false) ||
                (strpos($message, 'sequence/mapping') !== false && strpos($message, 'does not exist') !== false && strpos($message, '.twig') !== false)
            );

            // Si es un error específico de plantilla de Twig, usar directamente renderSimpleError
            if ($isTwigTemplateError) {
                echo self::renderSimpleError($data);
                exit();
            }

            echo $twig->render('versaWYS/debugError.twig', ['data' => $data]);
        } catch (\Twig\Error\RuntimeError $e) {
            // Error específico de Twig Runtime (como variables no definidas en strict mode)
            if (
                strpos($e->getMessage(), 'does not exist') !== false ||
                strpos($e->getMessage(), 'Key ') !== false
            ) {
                // Es un error de variable no definida en la plantilla, usar renderSimpleError
                echo self::renderSimpleError($data);
                exit();
            }
            // Si es otro tipo de error de Twig, continuar con el fallback normal
            throw $e;
        } catch (\Exception $e) {
            // Si falla la plantilla completa, intentar con la simple
            try {
                echo $twig->render('versaWYS/simpleError.twig', ['data' => $data]);
            } catch (\Exception $e2) {
                // Si falla la simple, intentar con la de emergencia
                try {
                    echo $twig->render('versaWYS/emergencyError.twig', ['data' => $data]);
                } catch (\Exception $e3) {
                    // Si todo falla, mostrar error HTML básico
                    echo self::renderSimpleError($data);
                }
            }
        }

        exit();
    }

    /**
     * Registra errores en un archivo de log para debugging
     */
    private static function logError(array $errorData): void
    {
        global $config;

        if (!isset($config['build']['debug']) || !$config['build']['debug']) {
            return;
        }

        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/errors_' . date('Y-m-d') . '.log';
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $errorData
        ];

        file_put_contents(
            $logFile,
            json_encode($logEntry, JSON_PRETTY_PRINT) . "\n" . str_repeat('-', 80) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * Renderiza un error simple sin usar Twig (para evitar loops infinitos)
     */
    private static function renderSimpleError(array $data): string
    {
        $message = htmlspecialchars((string)($data['message'] ?? 'Unknown error'));
        $type = htmlspecialchars((string)($data['type'] ?? 'Error'));

        // Determinar archivo y línea de forma robusta
        $file = 'Unknown file';
        $line = 'Unknown line';

        if (isset($data['real_file'])) {
            $file = htmlspecialchars((string)$data['real_file']);
        } elseif (isset($data['real_origin']['file'])) {
            $file = htmlspecialchars((string)$data['real_origin']['file']);
        } elseif (isset($data['template_file'])) {
            $file = htmlspecialchars((string)$data['template_file']) . ' (Template)';
        } elseif (isset($data['file'])) {
            $file = htmlspecialchars((string)$data['file']);
        }

        if (isset($data['real_line'])) {
            $line = htmlspecialchars((string)$data['real_line']);
        } elseif (isset($data['real_origin']['line'])) {
            $line = htmlspecialchars((string)$data['real_origin']['line']);
        } elseif (isset($data['template_line'])) {
            $line = htmlspecialchars((string)$data['template_line']);
        } elseif (isset($data['line'])) {
            $line = htmlspecialchars((string)$data['line']);
        }

        $function = '';
        if (isset($data['real_function'])) {
            $function = '<br><strong>Function:</strong> ' . htmlspecialchars((string)$data['real_function']);
        } elseif (isset($data['real_origin']['function'])) {
            $function = '<br><strong>Function:</strong> ' . htmlspecialchars((string)$data['real_origin']['function']);
        }

        $timestamp = isset($data['timestamp']) ? htmlspecialchars((string)$data['timestamp']) : date('Y-m-d H:i:s');

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
                    <p>$type</p>
                    <small>$timestamp</small>
                </div>
                <div class='error-content'>
                    <div class='section'>
                        <h3>Error Message:</h3>
                        <div class='code'>$message</div>
                    </div>

                    <div class='section'>
                        <h3>Location:</h3>
                        <div class='location'>
                            <strong>File:</strong> $file<br>
                            <strong>Line:</strong> $line
                            $function
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
        print $data;
        return true;
    }

    /**
     * Redirects the user to the specified route.
     *
     * @param string $route The route to redirect to.
     * @return void
     */
    public static function redirect(string $route): void
    {
        header('Location: ' . $route);
    }
}
