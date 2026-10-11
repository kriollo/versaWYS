# RedisModel - Documentación Completa

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Instalación y Configuración](#instalación-y-configuración)
3. [Inicialización](#inicialización)
4. [Operaciones Básicas](#operaciones-básicas)
5. [Operaciones con Hashes](#operaciones-con-hashes)
6. [Operaciones con Listas](#operaciones-con-listas)
7. [Operaciones con Sets](#operaciones-con-sets)
8. [Operaciones con Sorted Sets](#operaciones-con-sorted-sets)
9. [Búsqueda y Utilidades](#búsqueda-y-utilidades)
10. [Helpers de Cache](#helpers-de-cache)
11. [Transacciones](#transacciones)
12. [Ejemplos Prácticos](#ejemplos-prácticos)
13. [Mejores Prácticas](#mejores-prácticas)
14. [Troubleshooting](#troubleshooting)

---

## Introducción

`RedisModel` es una clase optimizada para trabajar con Redis en el framework versaWYS. Utiliza el patrón Singleton para garantizar una única conexión a Redis en todo el proyecto, con reconexión automática, manejo robusto de errores y soporte completo para todas las estructuras de datos de Redis.

### Características Principales

✅ **Singleton Pattern** - Una única instancia de conexión  
✅ **Reconexión Automática** - Manejo inteligente de conexiones perdidas  
✅ **Serialización Flexible** - Soporte para `serialize` y `json`  
✅ **Prefijos de Claves** - Organización y prevención de colisiones  
✅ **Manejo de Errores** - Try-catch en todas las operaciones  
✅ **Logging Opcional** - Para debugging y monitoreo  
✅ **Operaciones Completas** - Strings, Hashes, Lists, Sets, Sorted Sets  
✅ **Cache Helpers** - Métodos auxiliares para patrones comunes  
✅ **Transacciones** - Operaciones atómicas con MULTI/EXEC  
✅ **Búsqueda Optimizada** - Usa SCAN en lugar de KEYS

---

## Instalación y Configuración

### Requisitos Previos

```bash
# Instalar extensión PHP Redis
sudo apt-get install php-redis

# O mediante PECL
pecl install redis
```

### Configuración en `config.json`

```json
{
    "redis": {
        "host": "127.0.0.1",
        "port": 6379,
        "password": "tu_password_opcional",
        "database": 0,
        "timeout": 2.5,
        "persistent": true,
        "persistent_id": "versa_redis",
        "default_expiration": 3600
    }
}
```

---

## Inicialización

### Inicialización Automática

La clase usa automáticamente la configuración global de `config.json`:

```php
<?php
use versaWYS\kernel\model\RedisModel;

// No requiere inicialización explícita
// Usa automáticamente $config['redis']
RedisModel::set('clave', 'valor');
```

### Inicialización Personalizada

```php
<?php
// Inicializar con configuración personalizada
RedisModel::initialize(
    config: [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => 'mi_password',
        'database' => 0,
        'persistent' => true,
        'timeout' => 2.5
    ],
    keyPrefix: 'app:',              // Prefijo para todas las claves
    serializationMode: 'json',       // 'serialize' o 'json'
    enableLogging: true              // Habilitar logs
);
```

### Verificar Conexión

```php
<?php
// Verificar que Redis está disponible
if (RedisModel::ping()) {
    echo "✅ Redis conectado correctamente";
} else {
    echo "❌ Error de conexión";
}

// Obtener información del servidor
$info = RedisModel::info();
print_r($info);
```

---

## Operaciones Básicas

### SET - Establecer Valor

```php
<?php
// Establecer con expiración (3600 segundos por defecto)
RedisModel::set('usuario:1', ['nombre' => 'Juan', 'edad' => 25], 3600);

// Establecer sin expiración
RedisModel::set('config:app', ['debug' => true], null);

// Establecer valor simple
RedisModel::set('contador', 0, 3600);
```

### GET - Obtener Valor

```php
<?php
// Obtener valor
$usuario = RedisModel::get('usuario:1');
// Retorna: ['nombre' => 'Juan', 'edad' => 25]

// Obtener con valor por defecto
$config = RedisModel::get('config:inexistente', ['default' => true]);
// Retorna: ['default' => true] si no existe

// Valor simple
$contador = RedisModel::get('contador');
```

### SET/GET Múltiples

```php
<?php
// Establecer múltiples valores
$datos = [
    'usuario:1' => ['nombre' => 'Juan'],
    'usuario:2' => ['nombre' => 'María'],
    'usuario:3' => ['nombre' => 'Pedro']
];
RedisModel::setMultiple($datos, 3600);

// Obtener múltiples valores
$usuarios = RedisModel::getMultiple(['usuario:1', 'usuario:2', 'usuario:3']);
// Retorna: ['usuario:1' => [...], 'usuario:2' => [...], 'usuario:3' => [...]]
```

### DELETE - Eliminar Claves

```php
<?php
// Eliminar una clave
$eliminados = RedisModel::delete('usuario:1');

// Eliminar múltiples claves
$eliminados = RedisModel::delete(['usuario:1', 'usuario:2', 'usuario:3']);
echo "Eliminados: $eliminados claves";
```

### EXISTS - Verificar Existencia

```php
<?php
if (RedisModel::exists('usuario:1')) {
    echo "El usuario existe";
}
```

### EXPIRE/TTL - Gestión de Expiración

```php
<?php
// Establecer expiración a clave existente
RedisModel::expire('usuario:1', 7200); // 2 horas

// Obtener tiempo de vida restante
$ttl = RedisModel::ttl('usuario:1');
echo "Expira en: $ttl segundos";

// Valores especiales de TTL:
// -2: La clave no existe
// -1: La clave existe pero no tiene expiración
// > 0: Segundos restantes
```

### INCREMENT/DECREMENT - Contadores

```php
<?php
// Incrementar contador
RedisModel::set('visitas:home', 0);
RedisModel::increment('visitas:home'); // 1
RedisModel::increment('visitas:home', 5); // 6

// Decrementar contador
RedisModel::decrement('visitas:home'); // 5
RedisModel::decrement('visitas:home', 2); // 3

// Obtener valor actual
$visitas = RedisModel::get('visitas:home');
```

---

## Operaciones con Hashes

Los hashes son ideales para representar objetos con múltiples campos.

### HSET/HGET - Establecer/Obtener Campo

```php
<?php
// Establecer campo en hash
RedisModel::hSet('usuario:1', 'nombre', 'Juan');
RedisModel::hSet('usuario:1', 'email', 'juan@example.com');
RedisModel::hSet('usuario:1', 'edad', 25);

// Obtener campo específico
$nombre = RedisModel::hGet('usuario:1', 'nombre');
// Retorna: 'Juan'

// Obtener con valor por defecto
$telefono = RedisModel::hGet('usuario:1', 'telefono', 'No especificado');
```

### HGETALL - Obtener Todos los Campos

```php
<?php
// Obtener todos los campos del hash
$usuario = RedisModel::hGetAll('usuario:1');
// Retorna: ['nombre' => 'Juan', 'email' => 'juan@example.com', 'edad' => 25]
```

### HDEL - Eliminar Campos

```php
<?php
// Eliminar un campo
RedisModel::hDel('usuario:1', 'edad');

// Eliminar múltiples campos
RedisModel::hDel('usuario:1', ['email', 'telefono']);
```

### HEXISTS - Verificar Campo

```php
<?php
if (RedisModel::hExists('usuario:1', 'email')) {
    echo "El usuario tiene email";
}
```

### Ejemplo Completo: Sesión de Usuario

```php
<?php
$sessionId = 'sess_abc123';

// Crear sesión
RedisModel::hSet($sessionId, 'user_id', 1);
RedisModel::hSet($sessionId, 'username', 'juan');
RedisModel::hSet($sessionId, 'last_activity', time());
RedisModel::hSet($sessionId, 'ip', '192.168.1.1');
RedisModel::expire($sessionId, 1800); // 30 minutos

// Actualizar actividad
RedisModel::hSet($sessionId, 'last_activity', time());

// Obtener sesión completa
$session = RedisModel::hGetAll($sessionId);

// Verificar campo
if (RedisModel::hExists($sessionId, 'user_id')) {
    $userId = RedisModel::hGet($sessionId, 'user_id');
}

// Eliminar sesión
RedisModel::delete($sessionId);
```

---

## Operaciones con Listas

Las listas son perfectas para colas, logs, y estructuras de datos ordenadas.

### LPUSH/RPUSH - Agregar Elementos

```php
<?php
// Agregar al inicio (izquierda)
RedisModel::lPush('cola:emails', ['to' => 'user1@example.com', 'subject' => 'Hola']);

// Agregar al final (derecha)
RedisModel::rPush('cola:emails', ['to' => 'user2@example.com', 'subject' => 'Bienvenido']);
```

### LPOP/RPOP - Obtener y Eliminar

```php
<?php
// Obtener y eliminar del inicio
$email = RedisModel::lPop('cola:emails');

// Obtener y eliminar del final
$email = RedisModel::rPop('cola:emails');
```

### LRANGE - Obtener Rango

```php
<?php
// Obtener todos los elementos
$emails = RedisModel::lRange('cola:emails', 0, -1);

// Obtener primeros 10 elementos
$emails = RedisModel::lRange('cola:emails', 0, 9);

// Obtener últimos 5 elementos
$emails = RedisModel::lRange('cola:emails', -5, -1);
```

### LLEN - Longitud de Lista

```php
<?php
$cantidad = RedisModel::lLen('cola:emails');
echo "Emails pendientes: $cantidad";
```

### Ejemplo: Sistema de Cola de Trabajos

```php
<?php
// Productor: Agregar trabajos a la cola
class JobQueue
{
    public static function enqueue(string $job, array $data): void
    {
        $jobData = [
            'job' => $job,
            'data' => $data,
            'created_at' => time()
        ];
        RedisModel::rPush('jobs:pending', $jobData);
    }
}

// Consumidor: Procesar trabajos
class JobWorker
{
    public static function process(): void
    {
        while (true) {
            $job = RedisModel::lPop('jobs:pending');

            if ($job === null) {
                sleep(1); // Esperar si no hay trabajos
                continue;
            }

            // Procesar trabajo
            self::handleJob($job);
        }
    }

    private static function handleJob(array $job): void
    {
        try {
            // Ejecutar trabajo
            echo "Procesando: {$job['job']}\n";

            // Si falla, mover a cola de errores
        } catch (Exception $e) {
            RedisModel::rPush('jobs:failed', $job);
        }
    }
}

// Uso
JobQueue::enqueue('SendEmail', ['to' => 'user@example.com']);
JobQueue::enqueue('ProcessImage', ['path' => '/uploads/image.jpg']);

// Ver estado
$pending = RedisModel::lLen('jobs:pending');
$failed = RedisModel::lLen('jobs:failed');
echo "Pendientes: $pending | Fallidos: $failed";
```

---

## Operaciones con Sets

Los sets almacenan colecciones únicas de elementos (sin duplicados).

### SADD - Agregar Miembros

```php
<?php
// Agregar un miembro
RedisModel::sAdd('tags:articulo:1', 'php');

// Agregar múltiples miembros
RedisModel::sAdd('tags:articulo:1', 'redis', 'cache', 'backend');
```

### SMEMBERS - Obtener Todos los Miembros

```php
<?php
$tags = RedisModel::sMembers('tags:articulo:1');
// Retorna: ['php', 'redis', 'cache', 'backend']
```

### SISMEMBER - Verificar Miembro

```php
<?php
if (RedisModel::sIsMember('tags:articulo:1', 'php')) {
    echo "El artículo tiene el tag 'php'";
}
```

### SREM - Eliminar Miembros

```php
<?php
// Eliminar un miembro
RedisModel::sRem('tags:articulo:1', 'cache');

// Eliminar múltiples miembros
RedisModel::sRem('tags:articulo:1', 'php', 'redis');
```

### SCARD - Contar Miembros

```php
<?php
$cantidad = RedisModel::sCard('tags:articulo:1');
echo "El artículo tiene $cantidad tags";
```

### Ejemplo: Sistema de Seguidores

```php
<?php
class FollowerSystem
{
    // Seguir usuario
    public static function follow(int $userId, int $followId): void
    {
        RedisModel::sAdd("user:$userId:following", $followId);
        RedisModel::sAdd("user:$followId:followers", $userId);
    }

    // Dejar de seguir
    public static function unfollow(int $userId, int $followId): void
    {
        RedisModel::sRem("user:$userId:following", $followId);
        RedisModel::sRem("user:$followId:followers", $userId);
    }

    // Verificar si sigue
    public static function isFollowing(int $userId, int $followId): bool
    {
        return RedisModel::sIsMember("user:$userId:following", $followId);
    }

    // Obtener seguidores
    public static function getFollowers(int $userId): array
    {
        return RedisModel::sMembers("user:$userId:followers");
    }

    // Obtener siguiendo
    public static function getFollowing(int $userId): array
    {
        return RedisModel::sMembers("user:$userId:following");
    }

    // Contar seguidores
    public static function countFollowers(int $userId): int
    {
        return RedisModel::sCard("user:$userId:followers");
    }
}

// Uso
FollowerSystem::follow(1, 2); // Usuario 1 sigue a usuario 2
FollowerSystem::follow(1, 3);
FollowerSystem::follow(1, 4);

$following = FollowerSystem::getFollowing(1);
echo "Usuario 1 sigue a: " . implode(', ', $following);

$followers = FollowerSystem::countFollowers(2);
echo "Usuario 2 tiene $followers seguidores";
```

---

## Operaciones con Sorted Sets

Los sorted sets son sets ordenados por un score (puntuación).

### ZADD - Agregar con Score

```php
<?php
// Agregar jugador con puntuación
RedisModel::zAdd('leaderboard', 1500, 'jugador1');
RedisModel::zAdd('leaderboard', 2000, 'jugador2');
RedisModel::zAdd('leaderboard', 1750, 'jugador3');
```

### ZRANGE - Obtener Rango

```php
<?php
// Obtener top 10 (menor a mayor score)
$top10 = RedisModel::zRange('leaderboard', 0, 9);

// Obtener con scores
$top10WithScores = RedisModel::zRange('leaderboard', 0, 9, true);
// Retorna: ['jugador1' => 1500, 'jugador3' => 1750, 'jugador2' => 2000]

// Obtener todos
$all = RedisModel::zRange('leaderboard', 0, -1, true);
```

### ZSCORE - Obtener Score

```php
<?php
$score = RedisModel::zScore('leaderboard', 'jugador1');
echo "Score de jugador1: $score";
```

### ZREM - Eliminar Miembro

```php
<?php
RedisModel::zRem('leaderboard', 'jugador1');
```

### Ejemplo: Sistema de Ranking

```php
<?php
class LeaderboardSystem
{
    private const KEY = 'game:leaderboard';

    // Actualizar puntuación
    public static function updateScore(string $player, float $score): void
    {
        RedisModel::zAdd(self::KEY, $score, $player);
    }

    // Obtener top jugadores
    public static function getTop(int $limit = 10): array
    {
        // zRange ordena de menor a mayor, necesitamos revertir
        $players = RedisModel::zRange(self::KEY, -$limit, -1, true);
        return array_reverse($players, true);
    }

    // Obtener puntuación de jugador
    public static function getPlayerScore(string $player): ?float
    {
        $score = RedisModel::zScore(self::KEY, $player);
        return $score !== false ? $score : null;
    }

    // Obtener posición de jugador (ranking)
    public static function getPlayerRank(string $player): ?int
    {
        $allPlayers = RedisModel::zRange(self::KEY, 0, -1, true);
        $allPlayers = array_reverse($allPlayers, true);

        $position = 1;
        foreach ($allPlayers as $p => $score) {
            if ($p === $player) {
                return $position;
            }
            $position++;
        }

        return null;
    }
}

// Uso
LeaderboardSystem::updateScore('alice', 2500);
LeaderboardSystem::updateScore('bob', 3000);
LeaderboardSystem::updateScore('charlie', 2800);

$top3 = LeaderboardSystem::getTop(3);
foreach ($top3 as $player => $score) {
    echo "$player: $score puntos\n";
}

$aliceRank = LeaderboardSystem::getPlayerRank('alice');
echo "Alice está en la posición: $aliceRank";
```

---

## Búsqueda y Utilidades

### SCAN - Buscar Claves (Optimizado)

```php
<?php
// Buscar todas las claves de usuarios
$userKeys = RedisModel::scan('usuario:*');

// Buscar con límite de iteración
$keys = RedisModel::scan('sesion:*', 50);

// Procesar resultados
foreach ($keys as $key) {
    $data = RedisModel::get($key);
    // Procesar...
}
```

### Buscar por Sub-Clave

```php
<?php
// Buscar usuarios activos
$usuariosActivos = RedisModel::searchValueBySubKey(
    'usuario:*',
    'activo',
    true
);

// Buscar por rol
$admins = RedisModel::searchValueBySubKey(
    'usuario:*',
    'rol',
    'admin'
);
```

### Información del Servidor

```php
<?php
// Información completa
$info = RedisModel::info();

// Información de memoria
$memory = RedisModel::info('memory');
echo "Memoria usada: " . $memory['used_memory_human'];

// Número de claves
$totalKeys = RedisModel::dbSize();
echo "Total de claves: $totalKeys";
```

### Limpiar Base de Datos

```php
<?php
// Limpiar base de datos actual
RedisModel::flushDb();

// Limpiar todas las bases de datos (¡CUIDADO!)
RedisModel::flushAll();
```

### Comando Personalizado

```php
<?php
// Ejecutar comando Redis directo
$result = RedisModel::rawCommand('CLIENT', 'LIST');
```

---

## Helpers de Cache

### REMEMBER - Cache con Callback

```php
<?php
// Obtener de cache o ejecutar callback
$usuarios = RedisModel::remember('usuarios:todos', function() {
    // Esta función solo se ejecuta si no existe en cache
    return obtenerUsuariosDeBD();
}, 3600);

// Ejemplo con consulta pesada
$estadisticas = RedisModel::remember('stats:mensuales', function() {
    return calcularEstadisticasComplejas();
}, 86400); // 24 horas
```

### FORGET - Olvidar Valor

```php
<?php
// Eliminar valor del cache
RedisModel::forget('config:app');

// Equivalente a
RedisModel::delete('config:app');
```

### PULL - Obtener y Eliminar

```php
<?php
// Obtener valor y eliminarlo inmediatamente
$mensaje = RedisModel::pull('mensajes:usuario:1');

// Con valor por defecto
$config = RedisModel::pull('config:temporal', ['default' => true]);
```

### ADD - Agregar Solo Si No Existe

```php
<?php
// Solo se establece si la clave no existe
$agregado = RedisModel::add('usuario:1', ['nombre' => 'Juan'], 3600);

if ($agregado) {
    echo "Usuario agregado";
} else {
    echo "Usuario ya existe";
}
```

### REPLACE - Actualizar Solo Si Existe

```php
<?php
// Solo actualiza si la clave existe
$actualizado = RedisModel::replace('usuario:1', ['nombre' => 'Juan Actualizado']);

if ($actualizado) {
    echo "Usuario actualizado";
} else {
    echo "Usuario no existe";
}
```

---

## Transacciones

Las transacciones permiten ejecutar múltiples comandos de forma atómica.

### Ejemplo Básico

```php
<?php
// Iniciar transacción
$redis = RedisModel::multi();

// Ejecutar comandos (se encolan)
RedisModel::set('key1', 'value1');
RedisModel::increment('contador');
RedisModel::hSet('usuario:1', 'nombre', 'Juan');

// Ejecutar todos los comandos
$results = RedisModel::exec();

// Resultados es un array con el resultado de cada comando
print_r($results);
```

### Ejemplo: Transferencia de Puntos

```php
<?php
class PointsTransfer
{
    public static function transfer(int $fromUser, int $toUser, int $points): bool
    {
        // Verificar saldo antes de la transacción
        $balance = RedisModel::get("user:$fromUser:points", 0);

        if ($balance < $points) {
            return false;
        }

        // Iniciar transacción atómica
        RedisModel::multi();

        RedisModel::decrement("user:$fromUser:points", $points);
        RedisModel::increment("user:$toUser:points", $points);

        // Log de transacción
        RedisModel::rPush('transactions:log', [
            'from' => $fromUser,
            'to' => $toUser,
            'points' => $points,
            'timestamp' => time()
        ]);

        // Ejecutar
        $results = RedisModel::exec();

        return $results !== false;
    }
}

// Uso
if (PointsTransfer::transfer(1, 2, 100)) {
    echo "✅ Transferencia exitosa";
} else {
    echo "❌ Saldo insuficiente";
}
```

### Cancelar Transacción

```php
<?php
RedisModel::multi();

try {
    RedisModel::set('key1', 'value1');

    // Algo sale mal
    if ($error) {
        // Cancelar transacción
        RedisModel::discard();
        return;
    }

    RedisModel::set('key2', 'value2');
    RedisModel::exec();
} catch (Exception $e) {
    RedisModel::discard();
}
```

---

## Ejemplos Prácticos

### 1. Sistema de Rate Limiting

```php
<?php
class RateLimiter
{
    public static function check(string $identifier, int $maxAttempts, int $windowSeconds): bool
    {
        $key = "ratelimit:$identifier";
        $attempts = RedisModel::get($key, 0);

        if ($attempts >= $maxAttempts) {
            return false; // Límite excedido
        }

        // Incrementar intentos
        RedisModel::increment($key);

        // Si es el primer intento, establecer expiración
        if ($attempts === 0) {
            RedisModel::expire($key, $windowSeconds);
        }

        return true;
    }

    public static function remaining(string $identifier, int $maxAttempts): int
    {
        $key = "ratelimit:$identifier";
        $attempts = RedisModel::get($key, 0);
        return max(0, $maxAttempts - $attempts);
    }
}

// Uso: Limitar login a 5 intentos por 15 minutos
$ip = $_SERVER['REMOTE_ADDR'];

if (RateLimiter::check($ip, 5, 900)) {
    // Permitir intento de login
    $remaining = RateLimiter::remaining($ip, 5);
    echo "Intentos restantes: $remaining";
} else {
    echo "Demasiados intentos. Intenta más tarde.";
}
```

### 2. Sistema de Cache de Vistas

```php
<?php
class ViewCache
{
    public static function render(string $view, array $data, int $ttl = 3600): string
    {
        $cacheKey = "view:$view:" . md5(serialize($data));

        return RedisModel::remember($cacheKey, function() use ($view, $data) {
            // Renderizar vista (ejemplo con Twig)
            global $twig;
            return $twig->render($view, $data);
        }, $ttl);
    }

    public static function invalidate(string $view): void
    {
        $keys = RedisModel::scan("view:$view:*");
        foreach ($keys as $key) {
            RedisModel::delete($key);
        }
    }
}

// Uso
$html = ViewCache::render('products/list.twig', [
    'products' => $products
], 1800);

// Invalidar cache cuando se actualicen productos
ViewCache::invalidate('products/list.twig');
```

### 3. Sistema de Notificaciones en Tiempo Real

```php
<?php
class NotificationSystem
{
    public static function push(int $userId, array $notification): void
    {
        $notification['id'] = uniqid();
        $notification['timestamp'] = time();
        $notification['read'] = false;

        // Agregar a lista de notificaciones
        RedisModel::lPush("notifications:$userId", $notification);

        // Incrementar contador
        RedisModel::increment("notifications:$userId:unread");

        // Mantener solo últimas 100 notificaciones
        RedisModel::getInstance()->ltrim("notifications:$userId", 0, 99);
    }

    public static function getUnread(int $userId): array
    {
        $notifications = RedisModel::lRange("notifications:$userId", 0, -1);
        return array_filter($notifications, fn($n) => !$n['read']);
    }

    public static function markAsRead(int $userId, string $notificationId): void
    {
        $notifications = RedisModel::lRange("notifications:$userId", 0, -1);

        foreach ($notifications as $index => $notification) {
            if ($notification['id'] === $notificationId) {
                $notification['read'] = true;
                RedisModel::getInstance()->lSet("notifications:$userId", $index, serialize($notification));
                RedisModel::decrement("notifications:$userId:unread");
                break;
            }
        }
    }

    public static function getUnreadCount(int $userId): int
    {
        return RedisModel::get("notifications:$userId:unread", 0);
    }
}

// Uso
NotificationSystem::push(1, [
    'type' => 'message',
    'title' => 'Nuevo mensaje',
    'body' => 'Tienes un nuevo mensaje de Juan',
    'url' => '/messages/123'
]);

$unreadCount = NotificationSystem::getUnreadCount(1);
$notifications = NotificationSystem::getUnread(1);
```

### 4. Sistema de Configuración Dinámica

```php
<?php
class ConfigManager
{
    private const KEY = 'config:app';

    public static function set(string $key, mixed $value): void
    {
        RedisModel::hSet(self::KEY, $key, $value);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return RedisModel::hGet(self::KEY, $key, $default);
    }

    public static function all(): array
    {
        return RedisModel::hGetAll(self::KEY);
    }

    public static function has(string $key): bool
    {
        return RedisModel::hExists(self::KEY, $key);
    }

    public static function remove(string $key): void
    {
        RedisModel::hDel(self::KEY, $key);
    }
}

// Uso
ConfigManager::set('maintenance_mode', false);
ConfigManager::set('max_upload_size', 10485760);
ConfigManager::set('allowed_extensions', ['jpg', 'png', 'pdf']);

if (ConfigManager::get('maintenance_mode')) {
    echo "Sitio en mantenimiento";
}

$maxSize = ConfigManager::get('max_upload_size', 5242880);
```

### 5. Sistema de Logs con Rotación

```php
<?php
class LogRotator
{
    private const MAX_LOGS = 1000;

    public static function log(string $level, string $message, array $context = []): void
    {
        $entry = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s')
        ];

        $key = 'logs:' . date('Y-m-d');

        // Agregar log
        RedisModel::rPush($key, $entry);

        // Establecer expiración de 7 días
        if (RedisModel::lLen($key) === 1) {
            RedisModel::expire($key, 604800);
        }

        // Rotar si excede el máximo
        $length = RedisModel::lLen($key);
        if ($length > self::MAX_LOGS) {
            RedisModel::getInstance()->ltrim($key, -self::MAX_LOGS, -1);
        }
    }

    public static function getLogs(string $date = null, int $limit = 100): array
    {
        $date = $date ?? date('Y-m-d');
        $key = "logs:$date";

        return RedisModel::lRange($key, -$limit, -1);
    }

    public static function search(string $date, string $level): array
    {
        $logs = self::getLogs($date, -1);
        return array_filter($logs, fn($log) => $log['level'] === $level);
    }
}

// Uso
LogRotator::log('error', 'Database connection failed', [
    'host' => 'localhost',
    'port' => 5432
]);

LogRotator::log('info', 'User logged in', ['user_id' => 1]);

// Ver logs de hoy
$todayLogs = LogRotator::getLogs();

// Ver solo errores
$errors = LogRotator::search(date('Y-m-d'), 'error');
```

---

## Mejores Prácticas

### 1. Usar Prefijos de Claves

```php
<?php
// ✅ BUENO: Usar prefijos organizados
RedisModel::initialize($config, 'app:', 'json', true);
RedisModel::set('usuario:1', $data);          // Clave real: app:usuario:1
RedisModel::set('sesion:abc123', $data);      // Clave real: app:sesion:abc123

// ❌ MALO: Sin organización
RedisModel::set('u1', $data);
RedisModel::set('data', $data);
```

### 2. Establecer Expiraciones

```php
<?php
// ✅ BUENO: Siempre establecer TTL apropiado
RedisModel::set('sesion:user1', $data, 1800);      // 30 minutos
RedisModel::set('cache:productos', $data, 3600);    // 1 hora
RedisModel::set('stats:diarias', $data, 86400);     // 24 horas

// ❌ MALO: Claves sin expiración innecesariamente
RedisModel::set('temp_data', $data, null);
```

### 3. Usar Estructuras Apropiadas

```php
<?php
// ✅ BUENO: Hash para objetos
RedisModel::hSet('usuario:1', 'nombre', 'Juan');
RedisModel::hSet('usuario:1', 'email', 'juan@example.com');

// ❌ MALO: Múltiples claves
RedisModel::set('usuario:1:nombre', 'Juan');
RedisModel::set('usuario:1:email', 'juan@example.com');

// ✅ BUENO: Lista para colas
RedisModel::rPush('jobs', $job);

// ✅ BUENO: Set para colecciones únicas
RedisModel::sAdd('tags:1', 'php', 'redis');

// ✅ BUENO: Sorted Set para rankings
RedisModel::zAdd('leaderboard', 1500, 'player1');
```

### 4. Manejo de Errores

```php
<?php
// ✅ BUENO: Verificar resultados críticos
$success = RedisModel::set('critical_data', $data, 3600);
if (!$success) {
    // Manejar error
    logError('Failed to save critical data to Redis');
    // Fallback alternativo
}

// ✅ BUENO: Usar valores por defecto
$config = RedisModel::get('config', [
    'debug' => false,
    'timeout' => 30
]);
```

### 5. Usar SCAN en Producción

```php
<?php
// ✅ BUENO: Usar scan() para buscar claves
$keys = RedisModel::scan('usuario:*');

// ❌ MALO: KEYS bloquea el servidor en bases grandes
// RedisModel::getInstance()->keys('usuario:*');
```

### 6. Optimizar Serialización

```php
<?php
// Para datos simples, JSON es más rápido
RedisModel::initialize($config, 'app:', 'json');

// Para objetos complejos, serialize preserva tipos
RedisModel::initialize($config, 'app:', 'serialize');
```

### 7. Usar Remember para Queries Pesadas

```php
<?php
// ✅ BUENO: Cache automático con remember
$usuarios = RedisModel::remember('usuarios:activos', function() {
    return obtenerUsuariosActivosDeBD();
}, 3600);

// ❌ MALO: Cache manual repetitivo
$usuarios = RedisModel::get('usuarios:activos');
if ($usuarios === null) {
    $usuarios = obtenerUsuariosActivosDeBD();
    RedisModel::set('usuarios:activos', $usuarios, 3600);
}
```

### 8. Limpiar Cache Relacionado

```php
<?php
class CacheManager
{
    public static function invalidateUserCache(int $userId): void
    {
        // Limpiar todas las claves relacionadas
        RedisModel::delete([
            "usuario:$userId",
            "usuario:$userId:perfil",
            "usuario:$userId:posts",
            "usuario:$userId:stats"
        ]);
    }
}

// Al actualizar usuario
$usuario->update($data);
CacheManager::invalidateUserCache($usuario->id);
```

---

## Troubleshooting

### Problema: No se puede conectar a Redis

```php
<?php
// Verificar conexión
if (!RedisModel::ping()) {
    echo "Error: No se puede conectar a Redis\n";

    // Verificar configuración
    $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 2.5
    ];

    try {
        $redis = new Redis();
        $redis->connect($config['host'], $config['port'], $config['timeout']);
        echo "Conexión directa exitosa\n";
    } catch (RedisException $e) {
        echo "Error de conexión: " . $e->getMessage() . "\n";
    }
}
```

### Problema: Valores no se deserializan correctamente

```php
<?php
// Cambiar modo de serialización
RedisModel::initialize($config, 'app:', 'json');  // Probar con JSON

// O usar serialize
RedisModel::initialize($config, 'app:', 'serialize');

// Verificar manualmente
$redis = RedisModel::getInstance();
$raw = $redis->get('app:clave');
var_dump($raw); // Ver valor raw
```

### Problema: Claves no expiran

```php
<?php
// Verificar TTL
$ttl = RedisModel::ttl('mi_clave');
echo "TTL: $ttl\n";

// Establecer expiración manualmente
RedisModel::expire('mi_clave', 3600);

// Verificar que se estableció
$ttl = RedisModel::ttl('mi_clave');
echo "Nuevo TTL: $ttl\n";
```

### Problema: Memoria alta

```php
<?php
// Ver uso de memoria
$info = RedisModel::info('memory');
echo "Memoria usada: " . $info['used_memory_human'] . "\n";
echo "Memoria máxima: " . $info['maxmemory_human'] . "\n";

// Contar claves
$total = RedisModel::dbSize();
echo "Total de claves: $total\n";

// Buscar claves sin expiración
$redis = RedisModel::getInstance();
$iterator = null;
$keysWithoutTTL = [];

while ($keys = $redis->scan($iterator)) {
    foreach ($keys as $key) {
        if (RedisModel::ttl($key) === -1) {
            $keysWithoutTTL[] = $key;
        }
    }
}

echo "Claves sin expiración: " . count($keysWithoutTTL) . "\n";
```

### Problema: Lentitud en operaciones

```php
<?php
// Habilitar logging
RedisModel::initialize($config, 'app:', 'json', true);

// Medir tiempo de operación
$start = microtime(true);
RedisModel::set('test', 'value');
$elapsed = microtime(true) - $start;
echo "Tiempo de set(): " . ($elapsed * 1000) . "ms\n";

// Verificar latencia
$redis = RedisModel::getInstance();
$start = microtime(true);
$redis->ping();
$latency = (microtime(true) - $start) * 1000;
echo "Latencia: {$latency}ms\n";
```

### Habilitar Debug

```php
<?php
// Activar logs
RedisModel::initialize($config, 'app:', 'json', true);

// Ver todos los errores en error_log
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Ejecutar operación
$result = RedisModel::set('test', 'value');
var_dump($result);

// Ver logs del sistema
// Linux: tail -f /var/log/apache2/error.log
// Windows: ver logs de PHP
```

---

## Resumen de Métodos

### Operaciones Básicas

- `set(key, value, expiration)` - Establecer valor
- `get(key, default)` - Obtener valor
- `setMultiple(data, expiration)` - Establecer múltiples
- `getMultiple(keys)` - Obtener múltiples
- `delete(keys)` - Eliminar claves
- `exists(key)` - Verificar existencia
- `expire(key, seconds)` - Establecer expiración
- `ttl(key)` - Obtener tiempo de vida
- `increment(key, value)` - Incrementar
- `decrement(key, value)` - Decrementar

### Hashes

- `hSet(key, field, value)` - Establecer campo
- `hGet(key, field, default)` - Obtener campo
- `hGetAll(key)` - Obtener todos los campos
- `hDel(key, fields)` - Eliminar campos
- `hExists(key, field)` - Verificar campo

### Listas

- `lPush(key, value)` - Agregar al inicio
- `rPush(key, value)` - Agregar al final
- `lPop(key)` - Obtener y eliminar del inicio
- `rPop(key)` - Obtener y eliminar del final
- `lRange(key, start, end)` - Obtener rango
- `lLen(key)` - Obtener longitud

### Sets

- `sAdd(key, ...values)` - Agregar miembros
- `sMembers(key)` - Obtener todos los miembros
- `sIsMember(key, value)` - Verificar miembro
- `sRem(key, ...values)` - Eliminar miembros
- `sCard(key)` - Contar miembros

### Sorted Sets

- `zAdd(key, score, value)` - Agregar con score
- `zRange(key, start, end, withScores)` - Obtener rango
- `zRem(key, value)` - Eliminar miembro
- `zScore(key, value)` - Obtener score

### Utilidades

- `scan(pattern, count)` - Buscar claves
- `searchValueBySubKey(pattern, subKey, subValue)` - Buscar valores
- `info(section)` - Información del servidor
- `dbSize()` - Número de claves
- `flushDb()` - Limpiar base de datos
- `ping()` - Verificar conexión

### Cache Helpers

- `remember(key, callback, expiration)` - Cache con callback
- `forget(key)` - Olvidar valor
- `pull(key, default)` - Obtener y eliminar
- `add(key, value, expiration)` - Agregar si no existe
- `replace(key, value)` - Actualizar si existe

### Transacciones

- `multi()` - Iniciar transacción
- `exec()` - Ejecutar transacción
- `discard()` - Cancelar transacción

---

## Soporte y Contribuciones

Para reportar bugs o solicitar features, contacta al equipo de desarrollo de versaWYS.

**Versión**: 1.0.0  
**Última actualización**: 2024  
**Framework**: versaWYS-PHP
