<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use PDO;
use RedBeanPHP\R;
use RedBeanPHP\RedException\SQL;
use RedBeanPHP\SimpleModel;
use Throwable;

class RedBeanCnn extends SimpleModel
{
    protected mixed $host;
    protected mixed $user;
    protected mixed $pass;
    protected mixed $dbName;

    public function connet(): void
    {
        global $config;

        $db_config = $config['DB'];

        $this->host = $db_config['DB_HOST'];
        $this->user = $db_config['DB_USER'];
        $this->pass = $db_config['DB_PASS'];
        $this->dbName = $db_config['DB_NAME'];

        if (R::$currentDB == null) {
            R::setup('mysql:host=' . $this->host . ';dbname=' . $this->dbName, $this->user, $this->pass, false, false, [
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            ]);

            R::useFeatureSet('novice/latest');

            R::freeze();
        }
    }

    public function closeDB(): void
    {
        R::close();
    }
    public function scape($e)
    {
        if (null === $e) {
            return '';
        }

        if (is_numeric($e) && $e <= 2147483647) {
            if (explode('.', $e)[0] != $e) {
                return (float) $e;
            }
            return (int) $e;
        }

        return (string) trim(
            str_replace(
                ['\\', "\x00", '\n', '\r', "'", '"', "\x1a"],
                ['\\\\', '\\0', '\\n', '\\r', "\'", '\"', '\\Z'],
                $e
            )
        );
    }

    /**
     * Ejecuta una query de RedBeanPHP con manejo mejorado de errores
     * Este método envuelve las operaciones de RedBeanPHP para capturar errores con mejor contexto
     */
    protected function executeWithErrorHandling(callable $operation, array $debug_info = [])
    {
        try {
            return $operation();
        } catch (SQL $e) {
            $this->handleDatabaseError($e, $debug_info);
        } catch (Throwable $e) {
            $this->handleGeneralError($e, $debug_info);
        }
    }

    /**
     * Maneja errores específicos de base de datos con contexto mejorado
     */
    private function handleDatabaseError(SQL $e, array $debug_info = []): void
    {
        global $config, $versawys_error_handling_in_progress;

        // Protección contra ejecución múltiple global
        if ($versawys_error_handling_in_progress) {
            return;
        }
        $versawys_error_handling_in_progress = true;

        // Obtener el stack trace real de donde se originó la llamada
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $realOrigin = $this->findRealOrigin($trace);

        $errorData = [
            'type' => 'Database Error (RedBeanPHP)',
            'message' => $e->getMessage(),
            'sql_error' => $e->getSQLState() ?? 'Unknown SQL State',
            'real_file' => $realOrigin['file'] ?? 'Unknown',
            'real_line' => $realOrigin['line'] ?? 'Unknown',
            'real_function' => $realOrigin['function'] ?? 'Unknown',
            'real_class' => $realOrigin['class'] ?? 'Unknown',
            'debug_info' => $debug_info,
            'user_trace' => $this->getUserStackTrace($trace),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($config['build']['debug']) {
            $errorData['full_trace'] = $e->getTraceAsString();
            $errorData['raw_exception'] = [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }

        Response::jsonError($errorData, 500);
    }

    /**
     * Maneja errores generales con contexto mejorado
     */
    private function handleGeneralError(Throwable $e, array $debug_info = []): void
    {
        global $config, $versawys_error_handling_in_progress;

        // Protección contra ejecución múltiple global
        if ($versawys_error_handling_in_progress) {
            return;
        }
        $versawys_error_handling_in_progress = true;

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $realOrigin = $this->findRealOrigin($trace);

        $errorData = [
            'type' => 'General Error',
            'message' => $e->getMessage(),
            'real_file' => $realOrigin['file'] ?? $e->getFile(),
            'real_line' => $realOrigin['line'] ?? $e->getLine(),
            'real_function' => $realOrigin['function'] ?? 'Unknown',
            'real_class' => $realOrigin['class'] ?? 'Unknown',
            'debug_info' => $debug_info,
            'user_trace' => $this->getUserStackTrace($trace),
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($config['build']['debug']) {
            $errorData['full_trace'] = $e->getTraceAsString();
            $errorData['raw_exception'] = [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];
        }

        Response::jsonError($errorData, 500);
    }

    /**
     * Encuentra el origen real del error en el código del usuario (no en vendor/RedBeanPHP)
     */
    private function findRealOrigin(array $trace): array
    {
        $skipFiles = [
            'RedBeanCnn.php',
            '/vendor/',
            '/RedBeanPHP/',
            'SimpleModel.php'
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
                        'file' => $frame['file'],
                        'line' => $frame['line'] ?? 'Unknown',
                        'function' => $frame['function'] ?? 'Unknown',
                        'class' => $frame['class'] ?? null
                    ];
                }
            }
        }

        // Si no encontramos un origen válido, devolver el primero disponible
        return $trace[0] ?? [];
    }

    /**
     * Genera un stack trace centrado en el código del usuario
     */
    private function getUserStackTrace(array $trace): array
    {
        $userTrace = [];
        $skipFiles = ['/vendor/', '/RedBeanPHP/', 'SimpleModel.php'];

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
     * Métodos wrapper para operaciones comunes de RedBeanPHP con manejo mejorado de errores
     */
    public function getAll(string $sql, array $bindings = []): array
    {
        return $this->executeWithErrorHandling(
            fn() => R::getAll($sql, $bindings),
            ['query' => $sql, 'bindings' => $bindings, 'operation' => 'getAll']
        );
    }

    public function getRow(string $sql, array $bindings = []): array
    {
        return $this->executeWithErrorHandling(
            fn() => R::getRow($sql, $bindings),
            ['query' => $sql, 'bindings' => $bindings, 'operation' => 'getRow']
        ) ?? [];
    }

    public function getCell(string $sql, array $bindings = [])
    {
        return $this->executeWithErrorHandling(
            fn() => R::getCell($sql, $bindings),
            ['query' => $sql, 'bindings' => $bindings, 'operation' => 'getCell']
        );
    }

    public function exec(string $sql, array $bindings = []): int
    {
        return $this->executeWithErrorHandling(
            fn() => R::exec($sql, $bindings),
            ['query' => $sql, 'bindings' => $bindings, 'operation' => 'exec']
        );
    }

    public function store($bean)
    {
        return $this->executeWithErrorHandling(
            fn() => R::store($bean),
            ['operation' => 'store', 'bean_type' => $bean->getMeta('type') ?? 'unknown']
        );
    }

    public function storeAll(array $beans): array
    {
        return $this->executeWithErrorHandling(
            fn() => R::storeAll($beans),
            ['operation' => 'storeAll', 'bean_types' => array_map(fn($b) => $b->getMeta('type'), $beans)]
        );
    }

    public function findOne(string $type, string $sql = NULL, array $bindings = [])
    {
        return $this->executeWithErrorHandling(
            fn() => R::findOne($type, $sql, $bindings),
            ['operation' => 'findOne', 'type' => $type, 'query' => $sql, 'bindings' => $bindings]
        );
    }

    public function findAll(string $type, string $sql = NULL, array $bindings = []): array
    {
        return $this->executeWithErrorHandling(
            fn() => R::findAll($type, $sql, $bindings),
            ['operation' => 'findAll', 'type' => $type, 'query' => $sql, 'bindings' => $bindings]
        ) ?? [];
    }

    public function load(string $type, $id)
    {
        return $this->executeWithErrorHandling(
            fn() => R::load($type, $id),
            ['operation' => 'load', 'type' => $type, 'id' => $id]
        );
    }

    public function dispense(string $type)
    {
        return $this->executeWithErrorHandling(
            fn() => R::dispense($type),
            ['operation' => 'dispense', 'type' => $type]
        );
    }

    public function trash($bean): int
    {
        return $this->executeWithErrorHandling(
            fn() => R::trash($bean),
            ['operation' => 'trash', 'bean_type' => $bean->getMeta('type') ?? 'unknown']
        );
    }

    public function wipe(string $type): bool
    {
        return $this->executeWithErrorHandling(
            fn() => R::wipe($type),
            ['operation' => 'wipe', 'type' => $type]
        );
    }



    public function freeze($type = true): void
    {
        $this->executeWithErrorHandling(
            fn() => R::freeze($type),
            ['operation' => 'freeze', 'type' => $type]
        );
    }
    /**
     * Habilita o deshabilita el modo de depuración de RedBeanPHP
     *
     * @param bool $enable Si se debe habilitar el modo de depuración
     * @param int $level Nivel de detalle del modo de depuración
     */
    public function debug(bool $enable = true, int $level = 1): void
    {
        $this->executeWithErrorHandling(
            fn() => R::debug($enable, $level),
            ['operation' => 'debug', 'enable' => $enable, 'level' => $level]
        );
    }
}
