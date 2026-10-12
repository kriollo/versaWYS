# Configuración

Toda la configuración vive en `versaWYS/kernel/config/config.json`. El archivo **no se versiona**: se crea con `php versaCLI config:init`.

Los valores por defecto están en `versaWYS/kernel/config/config.default.php` y se fusionan con tu `config.json` en cada arranque, así que `config.json` solo necesita declarar lo que cambia. Ese mismo archivo es la referencia completa de claves disponibles.

## Secciones

| Sección | Para qué sirve |
|---|---|
| `database` | Conexión: `driver` (`mysql` o `postgresql`), `host`, `port`, `database`, `username`, `password`, `charset`, `collation`. |
| `admin` | `custom_url`: prefijo público del panel (las rutas se declaran como `/admin/...` y se sirven bajo este prefijo). `hide_default`: responde 404 en `/admin` cuando hay un prefijo propio. |
| `build` | `debug`, `name`, `version`, `timezone`, `locale`, `charset`, `cacheRoute` y `cacheRouteFile`. `version` se usa como parámetro de caché de los assets. |
| `twig` | `cache`, `compiled_dir`, `templates_dir`, `strict_variables`, `auto_escape`. |
| `auth` | `expiration_days_password`, `inactive_account_days` y `password_policy` (largo mínimo, mayúsculas, minúsculas, números, caracteres especiales). |
| `session` | `key` (firma de los JWT), `lifetime` y `user_cookie` (`domain`, `secure`, `http_only`, `same_site`, `lifetime_remember`). |
| `login_attempt` | `max` intentos y `time` de bloqueo en segundos. |
| `mail` | SMTP: `host`, `port`, `transport`, `secure` (`tls`, `ssl` o vacío), `name_from`, `username`, `password`. El certificado del servidor se verifica; `verify_peer: false` lo desactiva para un SMTP interno autofirmado. |
| `cors` | `allowed_origins`: orígenes externos autorizados a llamar a la API con credenciales. Vacío por defecto. |
| `csp` | `enabled` y orígenes extra por directiva, por ejemplo `"connect-src": ["https://api.ejemplo.com"]`. |
| `rate_limiting` | `enabled` y reglas `global`, `api`, `login`, `upload`, `sensitive` (`requests` por `window` segundos). |
| `logging` | `dir`, `min_level`, `format` (`json` o `text`), `max_file_size`, `retention_days`. Ver [Logging](./Logging_Quickstart.md). |
| `redis` | Conexión opcional a Redis. Ver [RedisModel](./RedisModel.md). |
| `websocket_service` | `ws_url` opcional que se entrega al frontend. |
| `assets` | Rutas de origen y destino de JS, CSS, imágenes, avatares y archivos subidos. |

## Cambiar la configuración

- **Desde el panel**: *Configuración* (solo superadministrador) edita correo, intentos de login, política de contraseñas, datos del build y URL del panel. Incluye el envío de un correo de prueba y el respaldo de la base de datos.
- **Desde la terminal**:

  ```bash
  php versaCLI config:debug:true|false
  php versaCLI config:templateCache:true|false
  php versaCLI route:compile          # genera y activa la caché de rutas
  php versaCLI cache:clear            # limpia cachés de rutas, Twig y rate limiting
  ```

- **A mano**: edita `config.json`. La conexión a la base de datos, las claves de sesión y el modo debug solo se cambian así o por CLI, nunca desde el navegador.

## Modo debug

Con `build.debug` en `true` se muestran errores detallados (rutas de archivos, trazas y SQL) y la barra de depuración. La validación CSRF y los controles de rol siguen activos. No lo actives en un servidor accesible desde internet: en producción las respuestas de error solo llevan el mensaje y el detalle queda en los logs.

## Variables de entorno

Si existe un archivo `.env` en la raíz del proyecto, sus pares `CLAVE=valor` se cargan como variables de entorno (`getenv('CLAVE')`). No reemplazan a `config.json`.
