# Logging

`versaWYS\kernel\Loggin` escribe un archivo por módulo y por día en `versaWYS/logs/`: `auth_2026_10_16.log`, `router_2026_10_16.log`. Cada entrada lleva fecha, nivel, módulo, mensaje, el `request_id` de la petición y su contexto.

```php
use versaWYS\kernel\Loggin;

Loggin::info('Pedido creado', 'pedidos', ['id' => $id, 'total' => $total]);
Loggin::warning('Stock bajo', 'inventario', ['sku' => $sku, 'quedan' => 2]);
Loggin::error('El courier no respondió', 'courier', ['http' => 504]);
```

La firma es la misma en todos los niveles: `(mensaje, módulo, contexto)`. Sin módulo, la entrada va a `logging.default_module` (`app`).

## Niveles

De menor a mayor: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`. Se guardan los que alcanzan `logging.min_level` (`INFO` por defecto): con `DEBUG` se guarda todo, útil solo al desarrollar.

Desde `error` en adelante, la entrada incluye la traza si `logging.include_stack_trace` está activo.

## Lo que nunca queda en el log

Las claves del contexto que parecen secretos se guardan como `[oculto]`, a cualquier profundidad: `password`, `secret`, `token`, `api_key`, `authorization`, `cookie`, `csrf`. Puedes pasar `$request->getAllParams()` completo sin filtrarlo antes:

```php
Loggin::info('Login', 'auth', ['params' => $request->getAllParams()]);
// {"params": {"email": "ana@empresa.cl", "password": "[oculto]"}}
```

Otros datos personales (RUT, teléfono, dirección) no se ocultan solos: no los pongas en el contexto si no hacen falta.

## Ayudas

| Método | Escribe |
|---|---|
| `Loggin::exception($e, 'modulo')` | `error` con clase, mensaje, archivo, línea y traza de la excepción. |
| `Loggin::auth('login', $userId, $ok)` | En `auth`: `info` si `$ok`, `warning` si no. |
| `Loggin::performance('reporte', $segundos)` | En `performance`: `warning` si pasa de 1 segundo, `debug` si no. |
| `Loggin::request('GET', $url, $estado, $segundos)` | En `http`: `error` desde 500, `warning` desde 400. |
| `Loggin::query($sql, $params, $segundos)` | `debug` en `database`. Para ver las consultas de una petición, mejor el [Profiler](./Profiler.md). |

```php
try {
    $courier->enviar($pedido);
} catch (Throwable $e) {
    Loggin::exception($e, 'courier', ['pedido' => $pedido['id']]);

    return ['success' => 0, 'message' => 'No se pudo enviar', 'code' => 502];
}
```

## El identificador de la petición

Todas las entradas de una misma petición comparten `request_id`, que también viaja en la cabecera `X-Request-Id` de la respuesta. En producción, si una respuesta de error trae detalle interno (una consulta SQL, una ruta del servidor), el usuario ve «Error interno del servidor (ref. 3f9c…)» y el mensaje original queda en el log `security` con esa referencia. Para encontrarla:

```bash
grep -h 3f9c1a2b7d4e5f60 versaWYS/logs/*.log
```

Detrás de un proxy de `trusted_proxies`, se conserva el `X-Request-Id` que este envíe, para seguir la petición de punta a punta.

## Configuración (`logging`)

| Clave | Por defecto | Para qué |
|---|---|---|
| `dir` | `./versaWYS/logs/` | Carpeta de los archivos. Debe tener permiso de escritura. |
| `min_level` | `INFO` | Nivel mínimo que se guarda. |
| `default_module` | `app` | Módulo cuando no se indica. |
| `format` | `json` | `json` (una entrada por línea, para `jq` o un recolector) o `text`. |
| `max_file_size` | 10 MB | Al llegar, el archivo pasa a `.1`, `.2`… y se empieza otro. |
| `retention_days` | 30 | Los archivos más antiguos se borran solos. |
| `include_stack_trace` | `true` | Traza en las entradas desde `error`. |
| `use_console` | `false` | En `versaCLI` y las tareas programadas, también muestra cada entrada en la terminal, con colores. |
| `timezone` | `America/Santiago` | Zona horaria de la fecha de cada entrada. |

## Leer los logs

```bash
versaCLI logs:list                  # archivos disponibles
versaCLI logs:tail:router --lines=50
tail -f versaWYS/logs/app_$(date +%Y_%m_%d).log | jq .
```

Desde el código, `Loggin::read('pedidos', '2026-10-16', 'ERROR')` devuelve las entradas de ese día, filtradas por nivel.

## Enviar los errores a otro servicio

`setCustomHandler` recibe cada entrada que pasa el filtro de nivel, además de escribirla en el archivo:

```php
Loggin::setCustomHandler(static function (string $level, string $message, string $module, array $context): void {
    if (in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)) {
        Functions::httpPostJson('https://hooks.ejemplo.com/alertas', compact('level', 'message', 'module'));
    }
});
```

Regístralo una vez, al arrancar la aplicación. El contexto que recibe es el original, sin ocultar: no lo envíes completo a un servicio externo.
