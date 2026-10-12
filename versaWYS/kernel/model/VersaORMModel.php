<?php

declare(strict_types=1);

namespace versaWYS\kernel\model;

use BadMethodCallException;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Throwable;
use TypeError;
use VersaORM\QueryBuilder;
use VersaORM\VersaModel;
use VersaORM\VersaORM;
use VersaORM\VersaORMException;
use versaWYS\kernel\Response;

/**
 * Modelo base minimalista para integración con VersaORM.
 * Todos los modelos deben extender esta clase y acceder a los métodos nativos de VersaORM y QueryBuilder.
 *
 * Ejemplo de uso en un modelo hijo:
 *   class User extends VersaORMModel {
 *       protected string $table = 'users';
 *   }
 *
 *   // En el controlador o servicio:
 *   User::boot($config); // Solo una vez en la app
 *   $users = (new User())->orm()->table('users')->get();
 *   $user = (new User())->orm()->table('users')->find(1);
 *   // O usando el helper query():
 *   $qb = (new User())->query();
 *   $qb->where('email', '=', 'correo@dominio.com')->first();
 */
class VersaORMModel extends VersaModel
{
    /**
     * Configuración de errores por defecto para todos los modelos
     * Personalizada para el framework versaWYS.
     */
    protected static array $errorConfig = [
        'log_errors' => true,
        'throw_on_error' => false, // No lanzar excepciones por defecto
        'format_for_api' => true, // Formatear para respuestas de API
        'include_suggestions' => true,
        'include_query_info' => true,
        'include_context' => true,
        'sanitize_sensitive_data' => true,
    ];

    /**
     * Instancia global de VersaORM (singleton).
     */
    protected static ?VersaORM $orm = null;

    /**
     * Nombre de la tabla asociada al modelo.
     */
    protected string $table = '';

    /** Campos que no se pueden asignar masivamente. */
    protected array $fillable = [];

    /** Permite asignar cualquier campo salvo que el hijo limite con $guarded específico. */
    protected array $guarded = [];

    /** Reglas de validación para los campos del modelo. */
    protected array $rules = [];

    /** Cache de nombres de tabla por clase para evitar reflection repetido. */
    private static array $tableNameCache = [];

    /**
     * Inicializa la instancia global de VersaORM.
     *
     * @param array $config Configuración de la base de datos y otros ajustes de VersaORM.
     *
     * @throws RuntimeException Si VersaORM ya ha sido inicializado.
     */
    public static function boot(array $config): void
    {
        global $versaOrm;
        $globalConfig = $GLOBALS['config'] ?? [];

        if (self::$orm instanceof VersaORM) {
            // VersaORM ya inicializado, no hacer nada o lanzar error
            throw new RuntimeException('VersaORM ya inicializado. No llames a boot() dos veces.');
        }

        // Fusionar configuraciones: la global y la específica para ORM
        $finalConfig = array_merge((array) ($globalConfig['db'] ?? []), $config);

        // Inicializar la instancia global de VersaORM
        self::$orm = new VersaORM($finalConfig);
        self::$orm->setTimezone($config['build']['timezone'] ?? 'America/Santiago');
        $versaOrm = self::$orm;
        // Registrar en VersaModel para que sus factory methods (dispense, load, find) funcionen
        VersaModel::setORM(self::$orm);
    }

    /**
     * Verifica si VersaORM ya ha sido inicializado.
     *
     * @return bool True si VersaORM está inicializado, false en caso contrario.
     */
    public static function isBooted(): bool
    {
        return self::$orm instanceof VersaORM;
    }

    /**
     * Establece directamente la instancia de VersaORM.
     * Útil cuando la instancia ya ha sido creada externamente.
     *
     * @param VersaORM $orm Instancia de VersaORM a utilizar.
     *
     * @throws RuntimeException Si VersaORM ya ha sido inicializado.
     */
    public static function setOrmInstance(VersaORM $orm): void
    {
        if (self::$orm instanceof VersaORM) {
            throw new RuntimeException('VersaORM ya inicializado. No se puede establecer una nueva instancia.');
        }

        self::$orm = $orm;
        global $versaOrm;
        $versaOrm = $orm;
        // Registrar en VersaModel para que sus factory methods (dispense, load, find) funcionen
        VersaModel::setORM($orm);
    }

    /**
     * Reemplaza la instancia activa de VersaORM.
     * Para el instalador, donde la configuración de BD cambia dentro de la misma petición.
     */
    public static function replaceOrm(VersaORM $orm): void
    {
        self::$orm = $orm;
        $GLOBALS['versaOrm'] = $orm;
        VersaModel::setORM($orm);
    }

    /**
     * Constructor que sobrescribe VersaModel para manejar la inicialización automática.
     *
     * @param string|null $table Nombre de la tabla (opcional, se autodetermina si no se proporciona)
     * @param array|VersaORM|null $orm Instancia ORM (opcional, usa la global si no se proporciona)
     *
     * @throws VersaORMException
     */
    public function __construct(?string $table = null, $orm = null)
    {
        // Autodeterminar tabla si no se proporciona
        $tableName = $table ?? static::tableName();

        // Usar ORM global si no se proporciona uno específico
        if ($orm === null) {
            global $versaOrm;

            // Preferir la instancia estática si está disponible
            if (self::$orm instanceof VersaORM) {
                $orm = self::$orm;
            } elseif (isset($versaOrm) && $versaOrm instanceof VersaORM) {
                // Usar la variable global si existe
                $orm = $versaOrm;
                // Sincronizar con la instancia estática
                self::$orm = $versaOrm;
            } else {
                throw new RuntimeException(
                    'VersaORM no inicializado. Asegúrate de que esté configurado antes de usar modelos.',
                );
            }
        }

        parent::__construct($tableName, $orm);
    }

    /**
     * Devuelve la instancia global de VersaORM.
     */
    public static function orm(): VersaORM
    {
        if (!self::$orm instanceof VersaORM) {
            throw new RuntimeException('VersaORM no inicializado. Llama a VersaORMModel::boot($config) primero.');
        }

        return self::$orm;
    }

    /**
     * Executes a SQL statement with optional parameters.
     *
     * This method serves as a wrapper for the global ORM's exec method,
     * allowing execution of raw SQL queries with parameter binding.
     *
     * @param string $sql The SQL statement to execute
     * @param array $params Optional array of parameters to bind to the SQL statement
     *
     * @return mixed The result of the SQL execution, type depends on the query type
     */
    public function exec(string $sql, array $params = []): mixed
    {
        global $versaOrm;

        return $this->executeWithErrorHandling(static fn () => $versaOrm->exec($sql, $params), [
            'operation' => 'exec',
            'query' => $sql,
            'bindings' => $params,
            'model_class' => static::class,
            'table' => static::tableName(),
        ]);
    }

    /**
     * Get the database table name for the model.
     *
     * This method determines the table name using the following priority:
     * 1. If a protected $table property is defined in the child class, use its value
     * 2. Otherwise, fallback to the lowercase short class name
     *
     * Results are cached to avoid repeated reflection operations.
     *
     * @return string The database table name
     */
    public static function tableName(): string
    {
        $cls = static::class;
        if (isset(self::$tableNameCache[$cls])) {
            return self::$tableNameCache[$cls];
        }

        // Reflection una sola vez si el hijo define protected string $table
        $refl = new ReflectionClass($cls);
        if ($refl->hasProperty('table')) {
            $prop = $refl->getProperty('table');
            $prop->setAccessible(true);
            /** @var mixed $val */
            $val = $prop->isStatic() ? $prop->getValue() : $prop->getValue($refl->newInstanceWithoutConstructor());
            if (is_string($val) && $val !== '') {
                return self::$tableNameCache[$cls] = $val;
            }
        }

        // Fallback al nombre de clase snake plural simple (muy básico)
        $short = strtolower((new ReflectionClass($cls))->getShortName());

        return self::$tableNameCache[$cls] = $short;
    }

    /**
     * Resuelve el nombre de tabla delegando a tableName() para respetar la
     * propiedad protected string $table que usan las subclases de versaBOT.
     * Esto permite que find() (VersaModel) funcione correctamente.
     */
    protected static function resolveTable(): string
    {
        return static::tableName();
    }

    /**
     * Define the property types for the model.
     *
     * This method should be overridden in child classes to specify the data types
     * of model properties. It returns an associative array where keys are property
     * names and values are their corresponding data types.
     *
     * @return array An associative array mapping property names to their data types.
     *               Returns empty array by default in the base implementation.
     */
    public static function definePropertyTypes(): array
    {
        return [];
    }

    /**
     * Ejecuta una operación con manejo mejorado de errores personalizado para versaWYS.
     *
     * @param callable $operation La operación a ejecutar
     * @param array $context Información de contexto para el error
     *
     * @return mixed El resultado de la operación o null si hay error
     */
    public function executeWithErrorHandling(callable $operation, array $context = [])
    {
        try {
            return $operation();
        } catch (VersaORMException $e) {
            // Formatear error de base de datos y enviarlo
            $errorData = $this->formatDatabaseError($e, array_merge($context, [
                'type' => 'VersaORMException',
                'message' => $e->getMessage(),
                'model_class' => static::class,
                'timestamp' => date('Y-m-d H:i:s'),
                'operation_type' => $context['operation'] ?? 'unknown',
            ]));

            $this->sendErrorResponse($errorData);

            return null;
        } catch (Throwable $e) {
            // Formatear error general y enviarlo
            $errorData = $this->formatGeneralError($e, array_merge($context, [
                'type' => 'GeneralException',
                'message' => $e->getMessage(),
                'model_class' => static::class,
                'timestamp' => date('Y-m-d H:i:s'),
                'operation_type' => $context['operation'] ?? 'unknown',
            ]));

            $this->sendErrorResponse($errorData);

            return null;
        }
    }

    /**
     * Escapa un valor para uso seguro en SQL.
     *
     * @param mixed $e Valor a escapar
     *
     * @return mixed Valor escapado
     */
    public function escape(string $e): mixed
    {
        if ($e === null) {
            return '';
        }

        if (is_numeric($e) && $e <= 2_147_483_647) {
            // Convertir a string para poder usar explode
            $eStr = (string) $e;
            if (explode('.', $eStr)[0] !== $eStr) {
                return (float) $e;
            }

            return (int) $e;
        }

        return trim(str_replace(
            ['\\', "\x00", '\n', '\r', "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\'", '\"', '\\Z'],
            $e,
        ));
    }

    protected function qb(?string $table = null): QueryBuilder
    {
        global $versaOrm;

        return $this->executeWithErrorHandling(
            static fn () => $versaOrm->table($table ?? static::tableName(), static::class),
            [
                'operation' => 'queryBuilder',
                'table' => $table ?? static::tableName(),
                'model_class' => static::class,
            ],
        );
    }

    /**
     * Formatear errores de base de datos con información completa para debugging.
     */
    protected function formatDatabaseError(VersaORMException $e, array $context = []): array
    {
        global $config;

        // Generar mensaje descriptivo basado en el error
        $userFriendlyMessage = $this->generateUserFriendlyMessage($e, $context);

        $errorData = [
            'type' => 'VersaORMException',
            'message' => $userFriendlyMessage,
            'success' => false,
            'error' => [
                'type' => 'Database Error',
                'message' => $userFriendlyMessage,
                'detailed_message' => $e->getMessage(),
                'code' => $e->getCode(),
                'sql_state' => $e->getSQLState() ?? 'Unknown',
                'timestamp' => $context['timestamp'] ?? date('Y-m-d H:i:s'),
                'model' => $this->getShortClassName($context['model_class'] ?? static::class),
                'operation' => $context['operation'] ?? 'unknown',
            ],
        ];

        // Información adicional en modo debug
        if ($config['build']['debug'] ?? false) {
            $trace = $this->getCleanStackTrace();
            $realOrigin = $this->findUserCodeOrigin($trace);

            $errorData['error'] = array_merge($errorData['error'], [
                'origin' => [
                    'file' => $realOrigin['file'] ?? 'Unknown',
                    'line' => $realOrigin['line'] ?? 'Unknown',
                    'function' => $realOrigin['function'] ?? 'Unknown',
                    'class' => $realOrigin['class'] ?? 'Unknown',
                ],
                'context' => $context,
                'stack_trace' => $this->getUserStackTrace($trace),
                'query_info' => [
                    'sql' => $context['query'] ?? null,
                    'bindings' => $context['bindings'] ?? null,
                    'table' => $context['table'] ?? null,
                ],
                'raw_exception' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            // Agregar sugerencias si está habilitado
            if (static::$errorConfig['include_suggestions'] ?? true) {
                $errorData['error']['suggestions'] = $this->generateErrorSuggestions($e, $context);
            }
        } else {
            // En producción, solo mostrar información esencial
            $errorData['error']['operation_context'] = [
                'table' => $context['table'] ?? 'unknown',
                'operation' => $context['operation'] ?? 'unknown',
            ];
        }

        return $errorData;
    }

    /**
     * Formatear errores generales con información completa para debugging.
     */
    protected function formatGeneralError(Throwable $e, array $context = []): array
    {
        global $config;

        $trace = $this->getCleanStackTrace();
        $realOrigin = $this->findUserCodeOrigin($trace);

        // Generar mensaje descriptivo
        $userFriendlyMessage = $this->generateGeneralErrorMessage($e, $context);

        $errorData = [
            'type' => 'GeneralException',
            'message' => $e->getMessage(),
            'success' => false,
            'error' => [
                'type' => 'General Error',
                'message' => $userFriendlyMessage,
                'detailed_message' => $e->getMessage(),
                'code' => $e->getCode(),
                'timestamp' => $context['timestamp'] ?? date('Y-m-d H:i:s'),
                'model' => $this->getShortClassName($context['model_class'] ?? static::class),
                'operation' => $context['operation'] ?? 'unknown',
                'origin' => [
                    'file' => $realOrigin['file'] ?? $e->getFile(),
                    'line' => $realOrigin['line'] ?? $e->getLine(),
                    'function' => $realOrigin['function'] ?? 'Unknown',
                    'class' => $realOrigin['class'] ?? 'Unknown',
                ],
            ],
        ];

        // Información adicional en modo debug
        if ($config['build']['debug'] ?? false) {
            $errorData['error'] = array_merge($errorData['error'], [
                'context' => $context,
                'stack_trace' => $this->getUserStackTrace($trace),
                'raw_exception' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);
        }

        return $errorData;
    }

    /**
     * Enviar respuesta de error usando el sistema de respuesta de versaWYS.
     */
    protected function sendErrorResponse(array $errorData): void
    {
        global $versawys_error_handling_in_progress;

        // Protección contra ejecución múltiple
        if ($versawys_error_handling_in_progress) {
            return;
        }

        $versawys_error_handling_in_progress = true;

        // Usar el sistema de respuesta de versaWYS
        Response::jsonError($errorData, 500);

        $versawys_error_handling_in_progress = false;
    }

    /**
     * Generar sugerencias útiles basadas en el tipo de error.
     */
    private function generateErrorSuggestions(VersaORMException $e, array $context): array
    {
        $suggestions = [];
        $message = strtolower($e->getMessage());

        // Sugerencias específicas basadas en el mensaje de error
        if (str_contains($message, 'table') && str_contains($message, "doesn't exist")) {
            $suggestions[] = 'Verifica que la tabla exista en la base de datos';
            $suggestions[] = 'Ejecuta las migraciones pendientes: php versaCLI migrate:up';
        }

        if (str_contains($message, 'column') && str_contains($message, 'unknown')) {
            $suggestions[] = 'Verifica que la columna existe en la tabla';
            $suggestions[] = 'Revisa la estructura de la tabla en la base de datos';
        }

        if (str_contains($message, 'duplicate entry')) {
            $suggestions[] = 'Este registro ya existe en la base de datos';
            $suggestions[] = 'Verifica las restricciones de unicidad';
        }

        if (str_contains($message, 'foreign key')) {
            $suggestions[] = 'Verifica que las claves foráneas referencien registros existentes';
            $suggestions[] = 'Revisa las restricciones de integridad referencial';
        }

        if (str_contains($message, 'connection')) {
            $suggestions[] = 'Verifica la conexión a la base de datos';
            $suggestions[] = 'Revisa la configuración de conexión en config.json';
        }

        // Sugerencias específicas por operación
        if (isset($context['operation'])) {
            switch ($context['operation']) {
                case 'queryBuilder':
                    $suggestions[] = 'Verifica la sintaxis del QueryBuilder';
                    break;
                case 'exec':
                    $suggestions[] = 'Revisa la sintaxis SQL de la consulta';
                    if (isset($context['query'])) {
                        $suggestions[] = 'SQL ejecutado: ' . substr($context['query'], 0, 100) . '...';
                    }

                    break;
            }
        }

        return array_unique($suggestions);
    }

    /**
     * Obtener stack trace limpio sin archivos internos.
     */
    private function getCleanStackTrace(): array
    {
        return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
    }

    /**
     * Encontrar el origen del error en código del usuario.
     */
    private function findUserCodeOrigin(array $trace): array
    {
        $skipPatterns = [
            '/vendor/',
            '/versaWYS/kernel/',
            'VersaORMModel.php',
            'HandlesErrors.php',
            '/versaorm/',
            'SimpleModel.php',
        ];

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            $skip = false;
            foreach ($skipPatterns as $pattern) {
                if (!str_contains($frame['file'], $pattern)) {
                    continue;
                }

                $skip = true;
                break;
            }

            if (!$skip) {
                return [
                    'file' => str_replace(getcwd(), '', $frame['file']),
                    'line' => $frame['line'] ?? 'Unknown',
                    'function' => $frame['function'] ?? 'Unknown',
                    'class' => $frame['class'] ?? null,
                ];
            }
        }

        return $trace[0] ?? [];
    }

    /**
     * Generar stack trace centrado en código del usuario.
     */
    private function getUserStackTrace(array $trace): array
    {
        $userTrace = [];
        $skipPatterns = ['/vendor/', '/versaWYS/kernel/', 'HandlesErrors.php'];

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            $skip = false;
            foreach ($skipPatterns as $pattern) {
                if (!str_contains($frame['file'], $pattern)) {
                    continue;
                }

                $skip = true;
                break;
            }

            if (!$skip) {
                $userTrace[] = [
                    'file' => str_replace(getcwd(), '', $frame['file']),
                    'line' => $frame['line'] ?? 'Unknown',
                    'function' => $frame['function'] ?? 'Unknown',
                    'class' => $frame['class'] ?? null,
                ];
            }

            // Limitar a 5 niveles para mantener la respuesta manejable
            if (count($userTrace) >= 5) {
                break;
            }
        }

        return $userTrace;
    }

    /**
     * Generar mensaje amigable para errores de base de datos.
     */
    private function generateUserFriendlyMessage(VersaORMException $e, array $context): string
    {
        $message = strtolower($e->getMessage());
        $operation = $context['operation'] ?? 'unknown';
        $table = $context['table'] ?? 'tabla';

        // Mensajes específicos basados en el tipo de error
        if (str_contains($message, 'table') && str_contains($message, "doesn't exist")) {
            return sprintf("La tabla '%s' no existe en la base de datos", $table);
        }

        if (str_contains($message, 'column') && str_contains($message, 'unknown')) {
            preg_match("/column '([^']+)'/i", $e->getMessage(), $matches);
            $column = $matches[1] ?? 'desconocida';

            return sprintf("La columna '%s' no existe en la tabla '%s'", $column, $table);
        }

        if (str_contains($message, 'duplicate entry')) {
            preg_match("/duplicate entry '([^']+)'/i", $e->getMessage(), $matches);
            $value = $matches[1] ?? '';

            return sprintf("Ya existe un registro con el valor '%s' en la tabla '%s'", $value, $table);
        }

        if (str_contains($message, 'foreign key')) {
            return 'Error de integridad: el registro referenciado no existe o está siendo usado por otros registros';
        }

        if (str_contains($message, 'connection') || str_contains($message, 'server has gone away')) {
            return 'Error de conexión con la base de datos';
        }

        if (str_contains($message, 'access denied')) {
            return 'Acceso denegado a la base de datos - verificar credenciales';
        }

        if (str_contains($message, 'syntax error')) {
            return 'Error de sintaxis en la consulta SQL';
        }

        // Mensajes específicos por operación
        return match ($operation) {
            'queryBuilder' => sprintf("Error al construir la consulta para la tabla '%s'", $table),
            'exec' => sprintf("Error al ejecutar consulta SQL en la tabla '%s'", $table),
            default => sprintf("Error en operación de base de datos en la tabla '%s': ", $table)
                . trim(substr($e->getMessage(), 0, 100)),
        };
    }

    /**
     * Generar mensaje amigable para errores generales.
     */
    private function generateGeneralErrorMessage(Throwable $e, array $context): string
    {
        $operation = $context['operation'] ?? 'unknown';
        $modelClass = $this->getShortClassName($context['model_class'] ?? static::class);

        // Tipos específicos de error
        if ($e instanceof InvalidArgumentException) {
            return 'Argumento inválido en el modelo ' . $modelClass;
        }

        if ($e instanceof RuntimeException) {
            return 'Error de ejecución en el modelo ' . $modelClass;
        }

        if ($e instanceof TypeError) {
            return 'Error de tipo de datos en el modelo ' . $modelClass;
        }

        if ($e instanceof BadMethodCallException) {
            return 'Método no válido llamado en el modelo ' . $modelClass;
        }

        // Mensaje por operación
        return match ($operation) {
            'queryBuilder' => 'Error al inicializar QueryBuilder en ' . $modelClass,
            'exec' => 'Error al ejecutar consulta en ' . $modelClass,
            default => sprintf('Error interno en %s: ', $modelClass) . trim(substr($e->getMessage(), 0, 80)),
        };
    }

    /**
     * Obtener nombre corto de la clase (sin namespace).
     */
    private function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }
}
