<?php

declare(strict_types=1);

namespace versaWYS\kernel\model;

use Redis;
use RedisException;
use Exception;
use versaWYS\kernel\Loggin;

/**
 * Clase RedisModel optimizada para operaciones Redis.
 *
 * Proporciona una interfaz singleton para trabajar con Redis en todo el proyecto
 * sin necesidad de gestionar múltiples conexiones.
 */
class RedisModel
{
    /**
     * Instancia singleton de Redis.
     */
    private static ?Redis $instance = null;

    /**
     * Configuración de Redis.
     */
    private static array $config = [];

    /**
     * Prefijo para todas las claves.
     */
    private static string $keyPrefix = '';

    /**
     * Modo de serialización: 'serialize' o 'json'.
     */
    private static string $serializationMode = 'json';

    /**
     * Habilitar logging de errores.
     */
    private static bool $enableLogging = false;

    /**
     * Tiempo de expiración por defecto (en segundos).
     */
    private static int $defaultExpiration = 3600;

    /**
     * Constructor privado para prevenir instanciación directa.
     */
    private function __construct()
    {
    }

    /**
     * Prevenir clonación de la instancia.
     */
    private function __clone()
    {
    }

    /**
     * Prevenir deserialización de la instancia.
     */
    public function __wakeup()
    {
        throw new Exception('No se puede deserializar un singleton');
    }

    /**
     * Inicializar configuración de Redis.
     *
     * @param array $config Configuración de Redis
     * @param string $keyPrefix Prefijo opcional para las claves
     * @param string $serializationMode Modo de serialización ('serialize' o 'json')
     * @param bool $enableLogging Habilitar logging de errores
     *
     * @return void
     */
    public static function initialize(
        array $config,
        string $keyPrefix = '',
        string $serializationMode = 'json',
        bool $enableLogging = false,
    ): void {
        self::$config = $config;
        self::$keyPrefix = $keyPrefix;
        self::$serializationMode = $serializationMode;
        self::$enableLogging = $enableLogging;

        if (isset($config['default_expiration'])) {
            self::$defaultExpiration = (int) $config['default_expiration'];
        }
    }

    /**
     * Obtener la instancia singleton de Redis.
     *
     * @param bool $forceReconnect Forzar reconexión
     *
     * @throws RedisException
     *
     * @return Redis
     */
    public static function getInstance(bool $forceReconnect = false): Redis
    {
        if (!self::$instance instanceof Redis || $forceReconnect) {
            self::connect();
        }

        // Verificar si la conexión sigue activa
        try {
            self::$instance->ping();
        } catch (RedisException) {
            self::log('Conexión perdida, reconectando...');
            self::connect();
        }

        return self::$instance;
    }

    // =====================================================
    // OPERACIONES BÁSICAS DE CLAVE-VALOR
    // =====================================================

    /**
     * Establecer un valor con expiración.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $expiration Tiempo en segundos (null = sin expiración)
     *
     * @return bool
     */
    public static function set(string $key, mixed $value, ?int $expiration = null): bool
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            if ($expiration === null) {
                return $redis->set($key, $serializedValue);
            }

            return $redis->setex($key, $expiration, $serializedValue);
        } catch (\RedisException $redisException) {
            self::log('Error en set(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Obtener un valor.
     *
     * @param string $key
     * @param mixed $default Valor por defecto si no existe
     *
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $value = $redis->get($key);

            if ($value === false) {
                return $default;
            }

            return self::unserialize($value);
        } catch (\RedisException $redisException) {
            self::log('Error en get(): ' . $redisException->getMessage());

            return $default;
        }
    }

    /**
     * Establecer múltiples valores.
     *
     * @param array $data Array asociativo [clave => valor]
     * @param int|null $expiration
     *
     * @return bool
     */
    public static function setMultiple(array $data, ?int $expiration = null): bool
    {
        try {
            $redis = self::getInstance();
            $redis->multi();

            foreach ($data as $key => $value) {
                $key = self::prefixKey($key);
                $serializedValue = self::serialize($value);

                if ($expiration === null) {
                    $redis->set($key, $serializedValue);
                } else {
                    $redis->setex($key, $expiration, $serializedValue);
                }
            }

            $result = $redis->exec();

            return !in_array(false, $result, true);
        } catch (RedisException $redisException) {
            self::log('Error en setMultiple(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Obtener múltiples valores.
     *
     * @param array $keys
     *
     * @return array
     */
    public static function getMultiple(array $keys): array
    {
        try {
            $redis = self::getInstance();
            $prefixedKeys = array_map(self::prefixKey(...), $keys);
            $values = $redis->mGet($prefixedKeys);

            $result = [];
            foreach ($keys as $index => $key) {
                $result[$key] = self::unserialize($values[$index]);
            }

            return $result;
        } catch (RedisException $redisException) {
            self::log('Error en getMultiple(): ' . $redisException->getMessage());

            return array_fill_keys($keys, null);
        }
    }

    /**
     * Eliminar una o más claves.
     *
     * @param array|string $keys
     *
     * @return int Número de claves eliminadas
     */
    public static function delete(string|array $keys): int
    {
        try {
            $redis = self::getInstance();
            $keys = is_array($keys) ? $keys : [$keys];
            $prefixedKeys = array_map(self::prefixKey(...), $keys);

            return $redis->del($prefixedKeys);
        } catch (RedisException $redisException) {
            self::log('Error en delete(): ' . $redisException->getMessage());

            return 0;
        }
    }

    public static function del(string|array $keys): int
    {
        return self::delete($keys);
    }

    /**
     * Verificar si una clave existe.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function exists(string $key): bool
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);

            return $redis->exists($key) > 0;
        } catch (RedisException $redisException) {
            self::log('Error en exists(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Establecer expiración a una clave existente.
     *
     * @param string $key
     * @param int $seconds
     *
     * @return bool
     */
    public static function expire(string $key, int $seconds): bool
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);

            return $redis->expire($key, $seconds);
        } catch (RedisException $redisException) {
            self::log('Error en expire(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Obtener tiempo de vida restante (TTL).
     *
     * @param string $key
     *
     * @return int -2 si no existe, -1 si no tiene expiración, o segundos restantes
     */
    public static function ttl(string $key): int
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);

            return $redis->ttl($key);
        } catch (RedisException $redisException) {
            self::log('Error en ttl(): ' . $redisException->getMessage());

            return -2;
        }
    }

    /**
     * Incrementar valor numérico.
     *
     * @param string $key
     * @param int $value
     *
     * @return false|int Nuevo valor o false en error
     */
    public static function increment(string $key, int $value = 1): int|false
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);

            return $redis->incrBy($key, $value);
        } catch (RedisException $redisException) {
            self::log('Error en increment(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Decrementar valor numérico.
     *
     * @param string $key
     * @param int $value
     *
     * @return false|int Nuevo valor o false en error
     */
    public static function decrement(string $key, int $value = 1): int|false
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);

            return $redis->decrBy($key, $value);
        } catch (RedisException $redisException) {
            self::log('Error en decrement(): ' . $redisException->getMessage());

            return false;
        }
    }

    // =====================================================
    // OPERACIONES CON HASHES
    // =====================================================

    /**
     * Establecer campo en hash.
     *
     * @param string $key
     * @param string $field
     * @param mixed $value
     *
     * @return bool
     */
    public static function hSet(string $key, string $field, mixed $value): bool
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            return $redis->hSet($key, $field, $serializedValue) !== false;
        } catch (RedisException $redisException) {
            self::log('Error en hSet(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Obtener campo de hash.
     *
     * @param string $key
     * @param string $field
     * @param mixed $default
     *
     * @return mixed
     */
    public static function hGet(string $key, string $field, mixed $default = null): mixed
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $value = $redis->hGet($key, $field);

            if ($value === false) {
                return $default;
            }

            return self::unserialize($value);
        } catch (RedisException $redisException) {
            self::log('Error en hGet(): ' . $redisException->getMessage());

            return $default;
        }
    }

    /**
     * Establecer múltiples campos en un hash de forma atómica (HSET múltiple).
     *
     * @param string $key
     * @param array  $fields Array asociativo [campo => valor]
     *
     * @return bool
     */
    public static function hSetMultiple(string $key, array $fields): bool
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedFields = [];

            foreach ($fields as $field => $value) {
                $serializedFields[$field] = self::serialize($value);
            }

            return $redis->hMSet($key, $serializedFields);
        } catch (RedisException $redisException) {
            self::log('Error en hSetMultiple(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Obtener todos los campos de un hash.
     *
     * @param string $key
     *
     * @return array
     */
    public static function hGetAll(string $key): array
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $data = $redis->hGetAll($key);

            $result = [];
            foreach ($data as $field => $value) {
                $result[$field] = self::unserialize($value);
            }

            return $result;
        } catch (RedisException $redisException) {
            self::log('Error en hGetAll(): ' . $redisException->getMessage());

            return [];
        }
    }

    /**
     * Eliminar campo de hash.
     *
     * @param string $key
     * @param array|string $fields
     *
     * @return int Número de campos eliminados
     */
    public static function hDel(string $key, string|array $fields): int
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $fields = is_array($fields) ? $fields : [$fields];

            return $redis->hDel($key, ...$fields);
        } catch (RedisException $redisException) {
            self::log('Error en hDel(): ' . $redisException->getMessage());

            return 0;
        }
    }

    /**
     * Verificar si campo existe en hash.
     *
     * @param string $key
     * @param string $field
     *
     * @return bool
     */
    public static function hExists(string $key, string $field): bool
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);

            return $redis->hExists($key, $field);
        } catch (RedisException $redisException) {
            self::log('Error en hExists(): ' . $redisException->getMessage());

            return false;
        }
    }

    // =====================================================
    // OPERACIONES CON LISTAS
    // =====================================================

    /**
     * Agregar elemento al inicio de la lista.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return false|int Longitud de la lista
     */
    public static function lPush(string $key, mixed $value): int|false
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            return $redis->lPush($key, $serializedValue);
        } catch (RedisException $redisException) {
            self::log('Error en lPush(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Agregar elemento al final de la lista.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return false|int Longitud de la lista
     */
    public static function rPush(string $key, mixed $value): int|false
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            return $redis->rPush($key, $serializedValue);
        } catch (RedisException $redisException) {
            self::log('Error en rPush(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Obtener y eliminar primer elemento de la lista.
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function lPop(string $key): mixed
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $value = $redis->lPop($key);

            return self::unserialize($value);
        } catch (RedisException $redisException) {
            self::log('Error en lPop(): ' . $redisException->getMessage());

            return null;
        }
    }

    /**
     * Obtener y eliminar último elemento de la lista.
     *
     * @param string $key
     *
     * @return mixed
     */
    public static function rPop(string $key): mixed
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $value = $redis->rPop($key);

            return self::unserialize($value);
        } catch (RedisException $redisException) {
            self::log('Error en rPop(): ' . $redisException->getMessage());

            return null;
        }
    }

    /**
     * Obtener rango de elementos de la lista.
     *
     * @param string $key
     * @param int $start
     * @param int $end
     *
     * @return array
     */
    public static function lRange(string $key, int $start = 0, int $end = -1): array
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $values = $redis->lRange($key, $start, $end);

            return array_map(self::unserialize(...), $values);
        } catch (RedisException $redisException) {
            self::log('Error en lRange(): ' . $redisException->getMessage());

            return [];
        }
    }

    /**
     * Obtener longitud de la lista.
     *
     * @param string $key
     *
     * @return int
     */
    public static function lLen(string $key): int
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);

            return $redis->lLen($key);
        } catch (RedisException $redisException) {
            self::log('Error en lLen(): ' . $redisException->getMessage());

            return 0;
        }
    }

    // =====================================================
    // OPERACIONES CON SETS
    // =====================================================

    /**
     * Agregar miembro(s) a un set.
     *
     * @param string $key
     * @param mixed ...$values
     *
     * @return int Número de elementos agregados
     */
    public static function sAdd(string $key, mixed ...$values): int
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValues = array_map(self::serialize(...), $values);

            return $redis->sAdd($key, ...$serializedValues);
        } catch (RedisException $redisException) {
            self::log('Error en sAdd(): ' . $redisException->getMessage());

            return 0;
        }
    }

    /**
     * Obtener todos los miembros de un set.
     *
     * @param string $key
     *
     * @return array
     */
    public static function sMembers(string $key): array
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $values = $redis->sMembers($key);

            return array_map(self::unserialize(...), $values);
        } catch (RedisException $redisException) {
            self::log('Error en sMembers(): ' . $redisException->getMessage());

            return [];
        }
    }

    /**
     * Verificar si un valor es miembro del set.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public static function sIsMember(string $key, mixed $value): bool
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            return $redis->sIsMember($key, $serializedValue);
        } catch (RedisException $redisException) {
            self::log('Error en sIsMember(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Eliminar miembro(s) de un set.
     *
     * @param string $key
     * @param mixed ...$values
     *
     * @return int Número de elementos eliminados
     */
    public static function sRem(string $key, mixed ...$values): int
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValues = array_map(self::serialize(...), $values);

            return $redis->sRem($key, ...$serializedValues);
        } catch (RedisException $redisException) {
            self::log('Error en sRem(): ' . $redisException->getMessage());

            return 0;
        }
    }

    /**
     * Obtener número de miembros en un set.
     *
     * @param string $key
     *
     * @return int
     */
    public static function sCard(string $key): int
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);

            return $redis->sCard($key);
        } catch (RedisException $redisException) {
            self::log('Error en sCard(): ' . $redisException->getMessage());

            return 0;
        }
    }

    // =====================================================
    // OPERACIONES CON SORTED SETS
    // =====================================================

    /**
     * Agregar miembro con score a sorted set.
     *
     * @param string $key
     * @param float $score
     * @param mixed $value
     *
     * @return int
     */
    public static function zAdd(string $key, float $score, mixed $value): int
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            return $redis->zAdd($key, $score, $serializedValue);
        } catch (RedisException $redisException) {
            self::log('Error en zAdd(): ' . $redisException->getMessage());

            return 0;
        }
    }

    /**
     * Obtener rango de sorted set por índice.
     *
     * @param string $key
     * @param int $start
     * @param int $end
     * @param bool $withScores
     *
     * @return array
     */
    public static function zRange(string $key, int $start, int $end, bool $withScores = false): array
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $values = $redis->zRange($key, $start, $end, $withScores);

            if ($withScores) {
                $result = [];
                foreach ($values as $value => $score) {
                    $result[self::unserialize($value)] = $score;
                }

                return $result;
            }

            return array_map(self::unserialize(...), $values);
        } catch (RedisException $redisException) {
            self::log('Error en zRange(): ' . $redisException->getMessage());

            return [];
        }
    }

    /**
     * Eliminar miembro de sorted set.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return int
     */
    public static function zRem(string $key, mixed $value): int
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            return $redis->zRem($key, $serializedValue);
        } catch (RedisException $redisException) {
            self::log('Error en zRem(): ' . $redisException->getMessage());

            return 0;
        }
    }

    /**
     * Obtener score de un miembro.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return false|float
     */
    public static function zScore(string $key, mixed $value): float|false
    {
        try {
            $redis = self::getInstance();
            $key = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            return $redis->zScore($key, $serializedValue);
        } catch (RedisException $redisException) {
            self::log('Error en zScore(): ' . $redisException->getMessage());

            return false;
        }
    }

    // =====================================================
    // BÚSQUEDA Y UTILIDADES
    // =====================================================

    /**
     * Buscar claves por patrón usando SCAN (más eficiente que KEYS).
     *
     * @param string $pattern Patrón de búsqueda (ej: "user:*")
     * @param int $count Número de claves a retornar por iteración
     *
     * @return array
     */
    public static function scan(string $pattern, int $count = 100): array
    {
        try {
            $redis = self::getInstance();
            $pattern = self::prefixKey($pattern);
            $keys = [];
            $iterator = null;

            $prefixLen = strlen(self::$keyPrefix);

            while ($scannedKeys = $redis->scan($iterator, $pattern, $count)) {
                foreach ($scannedKeys as $key) {
                    // substr() elimina solo el prefijo inicial; str_replace() eliminaría
                    // todas las ocurrencias si el nombre de la clave contiene el prefijo
                    $keys[] = $prefixLen > 0 ? substr($key, $prefixLen) : $key;
                }
            }

            return $keys;
        } catch (RedisException $redisException) {
            self::log('Error en scan(): ' . $redisException->getMessage());

            return [];
        }
    }

    /**
     * Buscar valores por sub-clave (optimizado con SCAN).
     *
     * @param string $pattern Patrón de búsqueda
     * @param string $subKey Clave dentro del valor
     * @param mixed $subValue Valor a buscar
     *
     * @return array
     */
    public static function searchValueBySubKey(string $pattern, string $subKey, mixed $subValue): array
    {
        $keys = self::scan($pattern);
        $results = [];

        foreach ($keys as $key) {
            $value = self::get($key);
            if (is_array($value) && isset($value[$subKey]) && $value[$subKey] === $subValue) {
                $results[] = $value;
            }
        }

        return $results;
    }

    /**
     * Obtener información del servidor Redis.
     *
     * @param string|null $section Sección específica
     *
     * @return array
     */
    public static function info(?string $section = null): array
    {
        try {
            $redis = self::getInstance();

            return $section ? $redis->info($section) : $redis->info();
        } catch (RedisException $redisException) {
            self::log('Error en info(): ' . $redisException->getMessage());

            return [];
        }
    }

    /**
     * Obtener número de claves en la base de datos actual.
     *
     * @return int
     */
    public static function dbSize(): int
    {
        try {
            $redis = self::getInstance();

            return $redis->dbSize();
        } catch (RedisException $redisException) {
            self::log('Error en dbSize(): ' . $redisException->getMessage());

            return 0;
        }
    }

    /**
     * Limpiar base de datos actual.
     *
     * @return bool
     */
    public static function flushDb(): bool
    {
        try {
            $redis = self::getInstance();

            return $redis->flushDB();
        } catch (RedisException $redisException) {
            self::log('Error en flushDb(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Limpiar todas las bases de datos.
     *
     * @return bool
     */
    public static function flushAll(): bool
    {
        try {
            $redis = self::getInstance();

            return $redis->flushAll();
        } catch (RedisException $redisException) {
            self::log('Error en flushAll(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Ejecutar comando Redis personalizado.
     *
     * @param string $command
     * @param mixed ...$args
     *
     * @return mixed
     */
    public static function rawCommand(string $command, mixed ...$args): mixed
    {
        try {
            $redis = self::getInstance();

            return $redis->rawCommand($command, ...$args);
        } catch (RedisException $redisException) {
            self::log('Error en rawCommand(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Verificar conexión con Redis.
     *
     * @return bool
     */
    public static function ping(): bool
    {
        try {
            $redis = self::getInstance();
            $response = $redis->ping();

            // phpredis >= 5.x retorna true; versiones anteriores retornan '+PONG'
            return $response === true || $response === '+PONG';
        } catch (RedisException $redisException) {
            self::log('Error en ping(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Cerrar conexión.
     *
     * @return void
     */
    public static function close(): void
    {
        if (self::$instance instanceof Redis) {
            try {
                self::$instance->close();
                self::$instance = null;
            } catch (RedisException $e) {
                self::log('Error al cerrar conexión: ' . $e->getMessage());
            }
        }
    }

    /**
     * Iniciar transacción (MULTI).
     *
     * @return Redis
     */
    public static function multi(): Redis
    {
        $redis = self::getInstance();
        $redis->multi();

        return $redis;
    }

    /**
     * Ejecutar transacción (EXEC).
     *
     * @return array|false
     */
    public static function exec(): array|false
    {
        try {
            $redis = self::getInstance();

            return $redis->exec();
        } catch (RedisException $redisException) {
            self::log('Error en exec(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Cancelar transacción (DISCARD).
     *
     * @return bool
     */
    public static function discard(): bool
    {
        try {
            $redis = self::getInstance();

            return $redis->discard();
        } catch (RedisException $redisException) {
            self::log('Error en discard(): ' . $redisException->getMessage());

            return false;
        }
    }

    // =====================================================
    // MÉTODOS DE CACHE HELPER
    // =====================================================

    /**
     * Recordar valor en cache (obtener o establecer).
     *
     * @param string $key
     * @param callable $callback Función a ejecutar si no existe en cache
     * @param int|null $expiration
     *
     * @return mixed
     */
    public static function remember(string $key, callable $callback, ?int $expiration = null): mixed
    {
        // Usar exists() en lugar de comparar con null para evitar re-ejecución
        // cuando el valor almacenado es legítimamente null o false
        if (self::exists($key)) {
            return self::get($key);
        }

        $value = $callback();
        self::set($key, $value, $expiration ?? self::$defaultExpiration);

        return $value;
    }

    /**
     * Olvidar valor en cache si existe.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function forget(string $key): bool
    {
        return self::delete($key) > 0;
    }

    /**
     * Obtener y eliminar valor.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::delete($key);

        return $value;
    }

    /**
     * Agregar valor solo si la clave no existe.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $expiration
     *
     * @return bool
     */
    public static function add(string $key, mixed $value, ?int $expiration = null): bool
    {
        try {
            $redis = self::getInstance();
            $prefixedKey = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            // SET NX es atómico: evita race condition entre exists() + set()
            if ($expiration !== null) {
                return (bool) $redis->set($prefixedKey, $serializedValue, ['nx', 'ex' => $expiration]);
            }

            return (bool) $redis->setnx($prefixedKey, $serializedValue);
        } catch (RedisException $redisException) {
            self::log('Error en add(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Establecer valor solo si la clave existe.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return bool
     */
    public static function replace(string $key, mixed $value): bool
    {
        try {
            $redis = self::getInstance();
            $prefixedKey = self::prefixKey($key);
            $serializedValue = self::serialize($value);

            // Obtener TTL antes del SET XX para preservar la expiración existente
            $ttl = $redis->ttl($prefixedKey);

            // SET XX es atómico: solo actualiza si la clave existe
            if ($ttl > 0) {
                return (bool) $redis->set($prefixedKey, $serializedValue, ['xx', 'ex' => $ttl]);
            }

            return (bool) $redis->set($prefixedKey, $serializedValue, ['xx']);
        } catch (RedisException $redisException) {
            self::log('Error en replace(): ' . $redisException->getMessage());

            return false;
        }
    }

    /**
     * Establecer conexión con Redis.
     *
     * @throws RedisException
     *
     * @return void
     */
    private static function connect(): void
    {
        global $config;

        // Usar configuración global si no se ha inicializado
        if (self::$config === [] && isset($config['redis']) && $config['redis']['enabled']) {
            self::$config = $config['redis'];
        }

        if (self::$config === []) {
            throw new RedisException('Configuración de Redis no disponible');
        }

        try {
            self::$instance = new Redis();

            $host = self::$config['host'] ?? '127.0.0.1';
            $port = self::$config['port'] ?? 6379;
            $timeout = self::$config['timeout'] ?? 2.5;
            $persistent = self::$config['persistent'] ?? false;

            // Usar conexión persistente si está configurada
            if ($persistent) {
                $persistentId = self::$config['persistent_id'] ?? 'versa_redis';
                self::$instance->pconnect($host, $port, $timeout, $persistentId);
            } else {
                self::$instance->connect($host, $port, $timeout);
            }

            // Autenticación si está configurada
            if (isset(self::$config['password']) && !empty(self::$config['password'])) {
                self::$instance->auth(self::$config['password']);
            }

            // Seleccionar base de datos
            if (isset(self::$config['database'])) {
                self::$instance->select((int) self::$config['database']);
            }

            // Configurar opciones adicionales
            self::$instance->setOption(Redis::OPT_READ_TIMEOUT, $timeout);
        } catch (RedisException $redisException) {
            self::log('Error al conectar con Redis: ' . $redisException->getMessage());

            throw $redisException;
        }
    }

    /**
     * Agregar prefijo a la clave.
     *
     * @param string $key
     *
     * @return string
     */
    private static function prefixKey(string $key): string
    {
        return self::$keyPrefix . $key;
    }

    /**
     * Serializar valor.
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function serialize(mixed $value): string
    {
        if (self::$serializationMode === 'json') {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded === false) {
                self::log('Error en json_encode(): ' . json_last_error_msg() . ' — usando serialize() como fallback');

                return serialize($value);
            }

            return $encoded;
        }

        return serialize($value);
    }

    /**
     * Deserializar valor.
     *
     * @param false|string $value
     *
     * @return mixed
     */
    private static function unserialize(string|false $value): mixed
    {
        if ($value === false) {
            return null;
        }

        if (self::$serializationMode === 'json') {
            $decoded = json_decode($value, true);

            // Si json_decode falla, el dato puede ser legado con PHP serialize().
            // Se intenta recuperar en lugar de perder la información.
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                if (@unserialize($value) !== false) {
                    self::log(
                        'Dato con formato PHP serialize detectado al leer en modo json — clave posiblemente migrada',
                    );

                    return unserialize($value);
                }

                return $value;
            }

            return $decoded;
        }

        return unserialize($value);
    }

    /**
     * Registrar mensaje de log.
     *
     * @param string $message
     *
     * @return void
     */
    private static function log(string $message): void
    {
        if (self::$enableLogging) {
            Loggin::error('[RedisModel] ' . $message, 'RedisModel');
        }
    }
}
