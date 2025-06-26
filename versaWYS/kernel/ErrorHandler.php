<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Throwable;
use RedBeanPHP\RedException\SQL;

/**
 * Manejador global de errores mejorado para versaWYS
 * Proporciona mejor contexto y debugging para errores de RedBeanPHP y otros
 */
class ErrorHandler
{
    private static bool $initialized = false;

    /**
     * Inicializa el manejador global de errores
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        // Handler para excepciones no capturadas
        set_exception_handler([self::class, 'handleException']);

        // Handler para errores fatales
        register_shutdown_function([self::class, 'handleShutdown']);

        // Handler para errores de PHP
        set_error_handler([self::class, 'handleError']);

        self::$initialized = true;
    }

    /**
     * Maneja excepciones no capturadas
     */
    public static function handleException(Throwable $e): void
    {
        global $config, $versawys_error_handling_in_progress;

        // Si RedBeanCnn ya está manejando un error, no interferir
        if ($versawys_error_handling_in_progress) {
            return;
        }

        // Protección contra ejecución múltiple
        static $errorHandled = false;
        if ($errorHandled) {
            return;
        }
        $errorHandled = true;

        $trace = $e->getTrace();
        $realOrigin = self::findRealOrigin($trace);

        $errorData = [
            'type' => self::getErrorType($e),
            'message' => $e->getMessage(),
            'real_file' => $realOrigin['file'] ?? $e->getFile(),
            'real_line' => $realOrigin['line'] ?? $e->getLine(),
            'real_function' => $realOrigin['function'] ?? 'Unknown',
            'real_class' => $realOrigin['class'] ?? 'Unknown',
            'user_trace' => self::getUserStackTrace($trace),
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => [
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            ]
        ];

        // Información específica para errores de base de datos
        if ($e instanceof SQL) {
            $errorData['sql_error'] = $e->getSQLState() ?? 'Unknown';
            $errorData['type'] = 'Database Error (RedBeanPHP)';
        }

        if (isset($config['build']['debug']) && $config['build']['debug']) {
            $errorData['full_trace'] = $e->getTraceAsString();
            $errorData['raw_exception'] = [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }

        self::logError($errorData);
        Response::jsonError($errorData, 500);
    }

    /**
     * Maneja errores fatales en shutdown
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $errorData = [
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => str_replace(getcwd(), '', $error['file']),
                'line' => $error['line'],
                'timestamp' => date('Y-m-d H:i:s')
            ];

            self::logError($errorData);

            // Solo mostrar error detallado en modo debug
            global $config;
            if (!isset($config['build']['debug']) || !$config['build']['debug']) {
                $errorData = ['type' => 'Fatal Error', 'message' => 'Internal Server Error'];
            }

            Response::jsonError($errorData, 500);
        }
    }

    /**
     * Maneja errores de PHP
     */
    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        // No procesar errores suprimidos con @
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $errorData = [
            'type' => self::getSeverityName($severity),
            'message' => $message,
            'file' => str_replace(getcwd(), '', $file),
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        // Solo loggear errores importantes
        if (in_array($severity, [E_ERROR, E_WARNING, E_PARSE, E_NOTICE])) {
            self::logError($errorData);
        }

        // Para errores críticos, detener ejecución
        if (in_array($severity, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            Response::jsonError($errorData, 500);
        }

        return true;
    }

    /**
     * Encuentra el origen real del error en el código del usuario
     */
    private static function findRealOrigin(array $trace): array
    {
        $skipFiles = [
            '/vendor/',
            '/RedBeanPHP/',
            'RedBeanCnn.php',
            'ErrorHandler.php',
            'versaTwig.php',
            'Response.php',
            '/twig/',
            '/cache/',
            '/compiled/'
        ];

        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $skip = false;
                foreach ($skipFiles as $skipFile) {
                    if (strpos($frame['file'], $skipFile) !== false) {
                        $skip = true;
                        break;
                    }
                }

                if (!$skip) {
                    return [
                        'file' => str_replace(getcwd(), '', $frame['file']),
                        'line' => $frame['line'] ?? 'Unknown',
                        'function' => $frame['function'] ?? 'Unknown',
                        'class' => $frame['class'] ?? null
                    ];
                }
            }
        }

        return [];
    }

    /**
     * Genera un stack trace centrado en el código del usuario
     */
    private static function getUserStackTrace(array $trace): array
    {
        $userTrace = [];
        $skipFiles = ['/vendor/', '/RedBeanPHP/', '/twig/', '/cache/', '/compiled/'];

        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $skip = false;
                foreach ($skipFiles as $skipFile) {
                    if (strpos($frame['file'], $skipFile) !== false) {
                        $skip = true;
                        break;
                    }
                }

                if (!$skip) {
                    $userTrace[] = [
                        'file' => str_replace(getcwd(), '', $frame['file']),
                        'line' => $frame['line'] ?? 'Unknown',
                        'function' => $frame['function'] ?? 'Unknown',
                        'class' => $frame['class'] ?? null
                    ];
                }
            }
        }

        return $userTrace;
    }

    /**
     * Determina el tipo de error basado en la excepción
     */
    private static function getErrorType(Throwable $e): string
    {
        if ($e instanceof SQL) {
            return 'Database Error (RedBeanPHP)';
        }

        $className = get_class($e);
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Convierte el nivel de severidad a nombre legible
     */
    private static function getSeverityName(int $severity): string
    {
        $severities = [
            E_ERROR => 'Fatal Error',
            E_WARNING => 'Warning',
            E_PARSE => 'Parse Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice',
            E_STRICT => 'Strict Standards',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_DEPRECATED => 'Deprecated',
            E_USER_DEPRECATED => 'User Deprecated'
        ];

        return $severities[$severity] ?? 'Unknown Error';
    }

    /**
     * Registra errores en archivo de log
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

        $logFile = $logDir . '/versawys_errors_' . date('Y-m-d') . '.log';
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $errorData
        ];

        file_put_contents(
            $logFile,
            json_encode($logEntry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n" . str_repeat('-', 100) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
