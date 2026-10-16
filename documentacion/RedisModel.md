# Redis (`RedisModel`)

`versaWYS\kernel\model\RedisModel` es un acceso estático a Redis para contadores, colas simples y datos compartidos entre procesos. **Para cachear resultados, usa [`Cache`](./Cache_Colas_Archivos.md#caché-cache)**: con `cache.driver: "redis"` guarda en Redis por medio de esta clase, y sin Redis sigue funcionando en archivos.

## Configuración

Requiere la extensión `redis` de PHP (`versaCLI status` la revisa si Redis está activo).

```json
{
    "redis": {
        "enabled": true,
        "host": "127.0.0.1",
        "port": 6379,
        "password": "",
        "database": 0,
        "timeout": 2.5,
        "persistent": true,
        "default_expiration": 3600
    }
}
```

La conexión se abre en la primera operación y se recupera sola si se corta. Con `enabled: false`, cada operación falla como si Redis no respondiera.

**Si Redis no responde, las operaciones no lanzan excepciones**: `set` devuelve `false`, `get` el valor por defecto y el error queda en el log `RedisModel`. Un Redis caído no tumba la página; revisa `RedisModel::ping()` donde importe saberlo.

## Valores

Los valores se guardan en JSON: textos, números, arreglos y `null` vuelven tal cual.

```php
use versaWYS\kernel\model\RedisModel;

RedisModel::set("carrito:{$userId}", ['items' => [12, 40]], 1800); // vence en 30 minutos
$carrito = RedisModel::get("carrito:{$userId}", ['items' => []]);

RedisModel::exists("carrito:{$userId}");
RedisModel::expire("carrito:{$userId}", 3600);  // nuevo vencimiento
RedisModel::ttl("carrito:{$userId}");           // segundos que le quedan; -1 sin vencimiento, -2 no existe
RedisModel::delete(["carrito:{$userId}", "otra"]);
```

| Método | Para qué |
|---|---|
| `add($clave, $valor, $segundos)` | Guarda solo si no existe: un candado simple entre procesos. |
| `replace($clave, $valor)` | Guarda solo si ya existe. |
| `pull($clave)` | Lee y borra. |
| `remember($clave, fn() => …, $segundos)` | Lee, o calcula y guarda. |

## Contadores

Atómicos aunque varios procesos sumen a la vez:

```php
$vistas = RedisModel::increment("vistas:producto:{$id}");
RedisModel::decrement("cupos:evento:{$id}", 2);
```

## Listas

Una cola simple, primero en entrar primero en salir. Para trabajos con reintentos y registro de fallos, mejor [`Queue`](./Cache_Colas_Archivos.md#colas-queue).

```php
RedisModel::rPush('notificaciones', ['user' => $userId, 'texto' => 'Pedido listo']);

while (($aviso = RedisModel::lPop('notificaciones')) !== null) {
    // enviar $aviso
}

RedisModel::lLen('notificaciones');
RedisModel::lRange('notificaciones', 0, 9);   // los 10 primeros, sin sacarlos
```

## Buscar claves

`scan($patron)` recorre las claves sin bloquear Redis, a diferencia de `KEYS`:

```php
$carritos = RedisModel::scan('carrito:*');
$deAna = RedisModel::searchValueBySubKey('sesion:*', 'email', 'ana@empresa.cl');
```

`searchValueBySubKey` lee cada clave del patrón: úsalo con conjuntos chicos.

## Transacciones

```php
$redis = RedisModel::multi();
$redis->incr('pedidos:total');
$redis->rPush('pedidos:ultimos', (string) $id);
RedisModel::exec();
```

Dentro de `multi()` se usa el cliente `Redis` de phpredis directamente: los valores no pasan por JSON.

## Estado

```php
RedisModel::ping();            // true si responde
RedisModel::info('memory');    // INFO de Redis, por sección
```

Para algo que esta clase no cubre, `RedisModel::getInstance()` entrega el cliente de phpredis. Los demás métodos públicos (hashes, sets, sorted sets, `flushDb`) son internos: pueden cambiar sin aviso (ver `API.md` del kernel).
