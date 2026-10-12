<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use DateTime;
use DateTimeZone;
use Exception;
use Throwable;
use versaWYS\kernel\model\RedisModel;

/**
 * Sistema de Logging Centralizado para versaWYS Framework.
 *
 * Proporciona un sistema completo de logging con soporte para:
 * - Múltiples niveles (DEBUG, INFO, WARNING, ERROR, CRITICAL, etc.)
 * - Módulos para organización
 * - Contexto adicional
 * - Múltiples handlers (archivo, Redis, consola)
 * - Rotación automática de logs
 * - Stack traces para errores
 */
class Loggin
{
    // Niveles de log (compatibles con PSR-3)
    public const DEBUG = 'DEBUG';

    public const INFO = 'INFO';

    public const NOTICE = 'NOTICE';

    public const WARNING = 'WARNING';

    public const ERROR = 'ERROR';

    public const CRITICAL = 'CRITICAL';

    public const ALERT = 'ALERT';

    public const EMERGENCY = 'EMERGENCY';

    /**
     * Niveles ordenados por severidad.
     */
    private const LEVEL_PRIORITY = [
        self::DEBUG => 0,
        self::INFO => 1,
        self::NOTICE => 2,
        self::WARNING => 3,
        self::ERROR => 4,
        self::CRITICAL => 5,
        self::ALERT => 6,
        self::EMERGENCY => 7,
    ];

    /**
     * Directorio de logs.
     */
    private static string $logDir = '';

    /**
     * Nivel mínimo de log.
     */
    private static string $minLevel = self::DEBUG;

    /**
     * Módulo por defecto.
     */
    private static string $defaultModule = 'app';

    /**
     * Habilitar logs en Redis.
     */
    private static bool $useRedis = false;

    /**
     * Habilitar logs en consola.
     */
    private static bool $useConsole = false;

    /**
     * Formato de salida: 'text' o 'json'.
     */
    private static string $format = 'text';

    /**
     * Tamaño máximo de archivo en bytes (0 = sin límite).
     */
    private static int $maxFileSize = 10_485_760; // 10MB por defecto

    /**
     * Días de retención de logs.
     */
    private static int $retentionDays = 30;

    /**
     * Zona horaria.
     */
    private static string $timezone = 'UTC';

    /**
     * Incluir stack trace en errores.
     */
    private static bool $includeStackTrace = true;

    /**
     * Handler personalizado.
     *
     * @var callable|null
     */
    private static $customHandler = null;

    /**
     * Inicializar configuración del logger.
     *
     * @param array $config Configuración
     *
     * @return void
     */
    public static function initialize(array $config = []): void
    {
        // Si no se pasa configuración, usar la global
        if ($config === []) {
            global $config;
        }

        $logConfig = $config['logging'] ?? [];

        self::$logDir = $logConfig['dir'] ?? __DIR__ . '/../../logs/';
        self::$minLevel = $logConfig['min_level'] ?? self::DEBUG;
        self::$defaultModule = $logConfig['default_module'] ?? 'app';
        self::$useRedis = $logConfig['use_redis'] ?? false;
        self::$useConsole = $logConfig['use_console'] ?? false;
        self::$format = $logConfig['format'] ?? 'text';
        self::$maxFileSize = $logConfig['max_file_size'] ?? 10_485_760;
        self::$retentionDays = $logConfig['retention_days'] ?? 30;
        self::$includeStackTrace = $logConfig['include_stack_trace'] ?? true;
        self::$timezone = $logConfig['timezone'] ?? 'UTC';

        // Crear directorio de logs si no existe
        if (!is_dir(self::$logDir)) {
            set_error_handler(static function () {
            }, E_WARNING);
            $mkdirSuccess = @mkdir(self::$logDir, 0o775, true);
            restore_error_handler();

            if (!$mkdirSuccess && !is_dir(self::$logDir)) {
                // Mostrar error visible en pantalla con diseño corporativo
                http_response_code(500);
                // El detalle (rutas, usuario del proceso, comandos) solo se muestra al desarrollar
                if (($config['build']['debug'] ?? false) !== true) {
                    error_log('versaWYS: no se puede crear el directorio de logs: ' . self::$logDir);
                    echo '<h1>Error interno del servidor</h1><p>Por favor, contacte al administrador.</p>';
                    exit();
                }
                echo
                    '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>VersaWYS Error - Sistema de Logs</title><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;margin:0;padding:0;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}.error-container{background:white;padding:0;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:800px;margin:20px;overflow:hidden}.error-header{background:linear-gradient(135deg,#dc2626 0%,#991b1b 100%);color:white;padding:30px;text-align:center}.error-header h1{margin:0 0 10px;font-size:28px;font-weight:600}.error-header p{margin:0;opacity:0.9;font-size:16px}.error-body{padding:30px}.error-section{margin:20px 0;padding:20px;background:#f8fafc;border-left:4px solid #dc2626;border-radius:4px}.error-section h3{margin:0 0 15px;color:#1e293b;font-size:18px;font-weight:600}.code{background:#1e293b;color:#e2e8f0;padding:15px;border-radius:6px;font-family:"JetBrains Mono",Consolas,Monaco,monospace;font-size:13px;overflow-x:auto;line-height:1.6}.location{background:#fef3c7;padding:15px;border-radius:6px;margin:10px 0;border-left:4px solid #f59e0b}.info-grid{display:grid;grid-template-columns:120px 1fr;gap:10px;margin:10px 0}.info-grid strong{color:#475569}.solution-steps{background:white;padding:15px;border-radius:6px;border:2px solid #10b981}.solution-steps h4{margin:0 0 15px;color:#059669;font-size:16px}.command{background:#1e293b;color:#10b981;padding:12px;border-radius:4px;font-family:monospace;margin:8px 0;font-size:13px;cursor:pointer;transition:all 0.2s}.command:hover{background:#0f172a;transform:translateX(4px)}.footer{text-align:center;padding:20px;color:#64748b;font-size:14px}.icon{font-size:48px;margin-bottom:10px}</style></head><body><div class="error-container"><div class="error-header"><div class="icon">📝</div><h1>VersaWYS Framework Error</h1><p>Error en Sistema de Logs</p></div><div class="error-body"><div class="error-section"><h3>📁 Directorio de Logs No Disponible</h3><div class="code">'
                        . htmlspecialchars(self::$logDir)
                        . '</div></div><div class="error-section"><h3>⚠️ Descripción del Problema</h3><p>El sistema no puede crear o acceder al directorio de logs debido a restricciones de permisos.</p><div class="location"><div class="info-grid"><strong>Error:</strong><span>Permission denied (Permisos denegados)</span><strong>Componente:</strong><span>Sistema de Logging</span><strong>Directorio:</strong><span>'
                        . htmlspecialchars(self::$logDir)
                        . '</span></div></div></div><div class="error-section"><div class="solution-steps"><h4>✅ Solución (Ejecutar en Terminal)</h4><div class="command">sudo mkdir -p '
                        . htmlspecialchars(self::$logDir)
                        . '</div><div class="command">sudo chown -R www-data:www-data '
                        . htmlspecialchars(self::$logDir)
                        . '</div><div class="command">sudo chmod -R 775 '
                        . htmlspecialchars(self::$logDir)
                        . '</div></div></div></div><div class="footer">VersaWYS Framework © 2026 | Sistema de Manejo de Errores</div></div></body></html>'
                ;
                exit();
            }
            // 02775 = 0775 + setgid: los archivos de log creados dentro heredan el grupo
            @chmod(self::$logDir, 0o2775);
        }

        // Limpiar logs antiguos
        self::cleanOldLogs();
    }

    /**
     * Establecer handler personalizado.
     *
     * @param callable $handler function(string $level, string $message, string $module, array $context, string $formatted)
     *
     * @return void
     */
    public static function setCustomHandler(callable $handler): void
    {
        self::$customHandler = $handler;
    }

    /**
     * Log principal.
     *
     * @param string $level Nivel de log
     * @param string $message Mensaje
     * @param string|null $module Módulo (null = usar default)
     * @param array $context Datos de contexto adicionales
     *
     * @return void
     */
    public static function log(string $level, string $message, ?string $module = null, array $context = []): void
    {
        // Inicializar si no se ha hecho
        if (self::$logDir === '' || self::$logDir === '0') {
            self::initialize();
        }

        // Validar nivel
        if (!isset(self::LEVEL_PRIORITY[$level])) {
            $level = self::INFO;
        }

        // Filtrar por nivel mínimo
        if (self::LEVEL_PRIORITY[$level] < self::LEVEL_PRIORITY[self::$minLevel]) {
            return;
        }

        $module ??= self::$defaultModule;

        // Preparar datos del log
        $logData = self::prepareLogData($level, $message, $module, $context);

        // Formatear mensaje
        $formattedMessage = self::formatMessage($logData);

        // Escribir en archivo
        self::writeToFile($logData, $formattedMessage);

        // Escribir en Redis si está habilitado
        if (self::$useRedis) {
            self::writeToRedis($logData);
        }

        // Escribir en consola si está habilitado
        if (self::$useConsole) {
            self::writeToConsole($logData, $formattedMessage);
        }

        // Handler personalizado
        if (self::$customHandler !== null) {
            call_user_func(self::$customHandler, $level, $message, $module, $context, $formattedMessage);
        }
    }

    // =====================================================
    // MÉTODOS DE CONVENIENCIA PARA CADA NIVEL
    // =====================================================

    /**
     * Log nivel DEBUG
     * Para información detallada de debugging.
     *
     * @param string $message
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function debug(string $message, ?string $module = null, array $context = []): void
    {
        self::log(self::DEBUG, $message, $module, $context);
    }

    /**
     * Log nivel INFO
     * Eventos informativos generales.
     *
     * @param string $message
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function info(string $message, ?string $module = null, array $context = []): void
    {
        self::log(self::INFO, $message, $module, $context);
    }

    /**
     * Log nivel NOTICE
     * Eventos normales pero significativos.
     *
     * @param string $message
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function notice(string $message, ?string $module = null, array $context = []): void
    {
        self::log(self::NOTICE, $message, $module, $context);
    }

    /**
     * Log nivel WARNING
     * Advertencias que no impiden el funcionamiento.
     *
     * @param string $message
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function warning(string $message, ?string $module = null, array $context = []): void
    {
        self::log(self::WARNING, $message, $module, $context);
    }

    /**
     * Log nivel ERROR
     * Errores que no requieren acción inmediata.
     *
     * @param string $message
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function error(string $message, ?string $module = null, array $context = []): void
    {
        self::log(self::ERROR, $message, $module, $context);
    }

    /**
     * Log nivel CRITICAL
     * Condiciones críticas (ej: componente no disponible).
     *
     * @param string $message
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function critical(string $message, ?string $module = null, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $module, $context);
    }

    /**
     * Log nivel ALERT
     * Se debe tomar acción inmediata.
     *
     * @param string $message
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function alert(string $message, ?string $module = null, array $context = []): void
    {
        self::log(self::ALERT, $message, $module, $context);
    }

    /**
     * Log nivel EMERGENCY
     * Sistema inutilizable.
     *
     * @param string $message
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function emergency(string $message, ?string $module = null, array $context = []): void
    {
        self::log(self::EMERGENCY, $message, $module, $context);
    }

    // =====================================================
    // MÉTODOS HELPER PARA CASOS COMUNES
    // =====================================================

    /**
     * Log de excepción.
     *
     * @param Throwable $exception
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function exception(Throwable $exception, ?string $module = null, array $context = []): void
    {
        $context['exception'] = [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if (self::$includeStackTrace) {
            $context['trace'] = $exception->getTrace();
        }

        self::error('Exception: ' . $exception->getMessage(), $module, $context);
    }

    /**
     * Log de query SQL.
     *
     * @param string $query
     * @param array $params
     * @param float $executionTime
     * @param string|null $module
     *
     * @return void
     */
    public static function query(
        string $query,
        array $params = [],
        float $executionTime = 0,
        ?string $module = null,
    ): void {
        self::debug('SQL Query executed', $module ?? 'database', [
            'query' => $query,
            'params' => $params,
            'execution_time' => round($executionTime, 4) . 's',
        ]);
    }

    /**
     * Log de petición HTTP.
     *
     * @param string $method
     * @param string $url
     * @param int $statusCode
     * @param float $responseTime
     * @param string|null $module
     *
     * @return void
     */
    public static function request(
        string $method,
        string $url,
        int $statusCode,
        float $responseTime = 0,
        ?string $module = null,
    ): void {
        $level = $statusCode >= 500 ? self::ERROR : ($statusCode >= 400 ? self::WARNING : self::INFO);

        self::log(sprintf('HTTP Request: %s %s', $method, $url), $level, $module ?? 'http', [
            'method' => $method,
            'url' => $url,
            'status_code' => $statusCode,
            'response_time' => round($responseTime, 4) . 's',
        ]);
    }

    /**
     * Log de autenticación.
     *
     * @param string $action 'login', 'logout', 'failed_login', etc
     * @param int|string $userId
     * @param bool $success
     * @param array $context
     *
     * @return void
     */
    public static function auth(string $action, string|int $userId, bool $success = true, array $context = []): void
    {
        $context['user_id'] = $userId;
        $context['action'] = $action;
        $context['success'] = $success;

        $level = $success ? self::INFO : self::WARNING;
        $message = $success ? sprintf('Auth: %s successful', $action) : sprintf('Auth: %s failed', $action);

        self::log($level, $message, 'auth', $context);
    }

    /**
     * Log de performance/benchmark.
     *
     * @param string $operation
     * @param float $duration
     * @param string|null $module
     * @param array $context
     *
     * @return void
     */
    public static function performance(
        string $operation,
        float $duration,
        ?string $module = null,
        array $context = [],
    ): void {
        $context['operation'] = $operation;
        $context['duration'] = round($duration, 4) . 's';

        // Warning si tarda más de 1 segundo
        $level = $duration > 1.0 ? self::WARNING : self::DEBUG;

        self::log(
            sprintf('Performance: %s took ', $operation) . round($duration, 4) . 's',
            $level,
            $module ?? 'performance',
            $context,
        );
    }

    // =====================================================
    // MÉTODOS DE CONSULTA
    // =====================================================

    /**
     * Leer logs de un módulo y fecha.
     *
     * @param string $module
     * @param string|null $date Formato Y-m-d (null = hoy)
     * @param string|null $level Filtrar por nivel
     *
     * @return array
     */
    public static function read(string $module, ?string $date = null, ?string $level = null): array
    {
        $date ??= date('Y-m-d');
        $dateFormatted = str_replace('-', '_', $date);
        $logFile = self::$logDir . $module . '_' . $dateFormatted . '.log';

        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];

        foreach ($lines as $line) {
            // Si es JSON
            if (self::$format === 'json') {
                $log = json_decode($line, true);
                if ($log && (!$level || $log['level'] === $level)) {
                    $logs[] = $log;
                }
            } elseif (!$level || str_contains($line, sprintf('[%s]', $level))) {
                // Si es texto y se filtra por nivel
                $logs[] = $line;
            }
        }

        return $logs;
    }

    /**
     * Obtener estadísticas de logs.
     *
     * @param string $module
     * @param string|null $date
     *
     * @return array
     */
    public static function stats(string $module, ?string $date = null): array
    {
        $logs = self::read($module, $date);
        $stats = [
            'total' => count($logs),
            'by_level' => [],
        ];

        foreach (array_keys(self::LEVEL_PRIORITY) as $level) {
            $stats['by_level'][$level] = 0;
        }

        if (self::$format === 'json') {
            foreach ($logs as $log) {
                if (!isset($log['level'])) {
                    continue;
                }

                $stats['by_level'][$log['level']]++;
            }
        } else {
            foreach ($logs as $line) {
                foreach (array_keys(self::LEVEL_PRIORITY) as $level) {
                    if (!str_contains($line, sprintf('[%s]', $level))) {
                        continue;
                    }

                    $stats['by_level'][$level]++;
                    break;
                }
            }
        }

        return $stats;
    }

    /**
     * Obtener configuración actual.
     *
     * @return array
     */
    public static function getConfig(): array
    {
        return [
            'log_dir' => self::$logDir,
            'min_level' => self::$minLevel,
            'default_module' => self::$defaultModule,
            'use_redis' => self::$useRedis,
            'use_console' => self::$useConsole,
            'format' => self::$format,
            'max_file_size' => self::$maxFileSize,
            'retention_days' => self::$retentionDays,
            'timezone' => self::$timezone,
            'include_stack_trace' => self::$includeStackTrace,
        ];
    }

    /**
     * Preparar datos del log.
     *
     * @param string $level
     * @param string $message
     * @param string $module
     * @param array $context
     *
     * @return array
     */
    private static function prepareLogData(string $level, string $message, string $module, array $context): array
    {
        try {
            $date = new DateTime('now', new DateTimeZone(self::$timezone));
        } catch (Exception) {
            $date = new DateTime('now');
        }

        $data = [
            'timestamp' => $date->format('Y-m-d H:i:s'),
            'level' => $level,
            'module' => $module,
            'message' => $message,
            'context' => $context,
        ];

        // Agregar información adicional para errores
        if (in_array($level, [self::ERROR, self::CRITICAL, self::ALERT, self::EMERGENCY], strict: true)) {
            // Stack trace
            if (self::$includeStackTrace && !isset($context['trace'])) {
                $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
                // Remover las llamadas internas de Loggin
                $trace = array_filter(
                    $trace,
                    static fn (array $item): bool => !isset($item['class']) || $item['class'] !== self::class,
                );
                $data['trace'] = array_values($trace);
            }

            // Información de la petición si está disponible
            if (!isset($context['request']) && isset($_SERVER['REQUEST_METHOD'])) {
                $data['request'] = [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                ];
            }
        }

        // Agregar memoria y tiempo si está en modo debug
        if ($level === self::DEBUG) {
            $data['memory_usage'] = memory_get_usage(true);
            $data['memory_peak'] = memory_get_peak_usage(true);
            $data['execution_time'] = microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
        }

        return $data;
    }

    /**
     * Formatear mensaje según el formato configurado.
     *
     * @param array $logData
     *
     * @return string
     */
    private static function formatMessage(array $logData): string
    {
        if (self::$format === 'json') {
            return json_encode($logData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        }

        // Formato de texto
        $message = sprintf(
            '[%s] [%s] [%s] %s',
            $logData['timestamp'],
            str_pad($logData['level'], 9),
            str_pad($logData['module'], 15),
            $logData['message'],
        );

        // Agregar contexto si existe
        if (!empty($logData['context'])) {
            $contextStr = json_encode($logData['context'], JSON_UNESCAPED_UNICODE);
            $message .= ' | Context: ' . $contextStr;
        }

        // Agregar trace si existe (solo primeras 3 líneas para no saturar)
        if (isset($logData['trace']) && !empty($logData['trace'])) {
            $message .= PHP_EOL . '  Stack Trace:';
            $traceLines = array_slice($logData['trace'], 0, 5);
            foreach ($traceLines as $index => $trace) {
                $file = $trace['file'] ?? 'unknown';
                $line = $trace['line'] ?? 0;
                $function = $trace['function'] ?? 'unknown';
                $message .= PHP_EOL . sprintf('    #%s %s:%s %s()', $index, $file, $line, $function);
            }
        }

        return $message . PHP_EOL;
    }

    /**
     * Escribir log en archivo.
     *
     * @param array $logData
     * @param string $formattedMessage
     *
     * @return void
     */
    private static function writeToFile(array $logData, string $formattedMessage): void
    {
        try {
            $date = new DateTime('now', new DateTimeZone(self::$timezone));
        } catch (Exception) {
            $date = new DateTime('now');
        }

        // Archivo por día y módulo
        $logFile = self::$logDir . $logData['module'] . '_' . $date->format('Y_m_d') . '.log';

        // Verificar que el directorio existe y es escribible
        if (!is_dir(self::$logDir)) {
            set_error_handler(static function () {
            }, E_WARNING);
            $mkdirSuccess = @mkdir(self::$logDir, 0o775, true);
            restore_error_handler();

            if (!$mkdirSuccess && !is_dir(self::$logDir)) {
                // No mostrar error aquí ya que se mostró en initialize
                error_log('Failed to create log directory: ' . self::$logDir);
                return;
            }
            // 02775 = 0775 + setgid: los archivos de log creados dentro heredan el grupo
            @chmod(self::$logDir, 0o2775);
        }

        // Rotar archivo si excede el tamaño máximo
        if (self::$maxFileSize > 0 && file_exists($logFile) && filesize($logFile) >= self::$maxFileSize) {
            self::rotateLogFile($logFile);
        }

        if (!file_exists($logFile)) {
            $previousUmask = umask(0);
            set_error_handler(static function () {
            }, E_WARNING);
            $handle = @fopen($logFile, 'ab');
            restore_error_handler();
            umask($previousUmask);

            if ($handle === false) {
                error_log("Failed to create log file: {$logFile}");
                return;
            }

            fclose($handle);
            self::ensurePermissions($logFile, 0o775);
        }

        // Escribir log
        if (@file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX) === false) {
            error_log("Failed to write to log file: {$logFile}");
        } else {
            self::ensurePermissions($logFile, 0o775);
        }
    }

    private static function ensurePermissions(string $path, int $mode): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            return;
        }

        clearstatcache(true, $path);
        $perms = fileperms($path);
        if ($perms === false) {
            return;
        }

        $currentMode = $perms & 0o777;
        if ($currentMode === $mode) {
            return;
        }

        set_error_handler(static function () {
        }, E_WARNING);
        $chmodSuccess = @chmod($path, $mode);
        restore_error_handler();

        clearstatcache(true, $path);
        $updatedPerms = fileperms($path);
        $updatedMode = $updatedPerms === false ? $currentMode : $updatedPerms & 0o777;

        if (!$chmodSuccess || $updatedMode !== $mode) {
            error_log(sprintf(
                'Loggin::ensurePermissions no pudo aplicar %o a %s (actual: %o)',
                $mode,
                $path,
                $updatedMode,
            ));
        }
    }

    /**
     * Rotar archivo de log.
     *
     * @param string $logFile
     *
     * @return void
     */
    private static function rotateLogFile(string $logFile): void
    {
        $counter = 1;
        $rotatedFile = $logFile . '.' . $counter;

        while (file_exists($rotatedFile)) {
            $counter++;
            $rotatedFile = $logFile . '.' . $counter;
        }

        rename($logFile, $rotatedFile);
    }

    /**
     * Escribir log en Redis.
     *
     * @param array $logData
     *
     * @return void
     */
    private static function writeToRedis(array $logData): void
    {
        try {
            if (!class_exists('versaWYS\kernel\model\RedisModel')) {
                return;
            }

            $redisKey = 'logs:' . $logData['module'] . ':' . date('Y-m-d');

            // Usar RedisModel para almacenar
            RedisModel::rPush($redisKey, $logData);

            // Establecer expiración según retención
            $ttl = self::$retentionDays * 86_400;
            RedisModel::expire($redisKey, $ttl);

            // Mantener solo últimos 1000 logs por día/módulo
            $length = RedisModel::lLen($redisKey);
            if ($length > 1000) {
                $redis = RedisModel::getInstance();
                $redis->ltrim($redisKey, -1000, -1);
            }
        } catch (Exception) {
            // Ignorar errores de Redis para no afectar la aplicación
        }
    }

    /**
     * Escribir log en consola.
     *
     * @param array $logData
     * @param string $formattedMessage
     *
     * @return void
     */
    private static function writeToConsole(array $logData, string $formattedMessage): void
    {
        if (PHP_SAPI === 'cli') {
            // Colores ANSI para terminal
            $colors = [
                self::DEBUG => "\033[36m", // Cyan
                self::INFO => "\033[32m", // Green
                self::NOTICE => "\033[34m", // Blue
                self::WARNING => "\033[33m", // Yellow
                self::ERROR => "\033[31m", // Red
                self::CRITICAL => "\033[35m", // Magenta
                self::ALERT => "\033[41m", // Red background
                self::EMERGENCY => "\033[41;1m", // Red background bold
            ];

            $reset = "\033[0m";
            $color = $colors[$logData['level']] ?? '';

            echo $color . trim($formattedMessage) . $reset . PHP_EOL;
        }
    }

    /**
     * Limpiar logs antiguos.
     *
     * @return void
     */
    private static function cleanOldLogs(): void
    {
        if (self::$retentionDays <= 0) {
            return;
        }

        $files = glob(self::$logDir . '*.log*');
        $cutoffTime = time() - (self::$retentionDays * 86_400);

        foreach ($files as $file) {
            if (!(is_file($file) && filemtime($file) < $cutoffTime)) {
                continue;
            }

            @unlink($file);
        }
    }
}
