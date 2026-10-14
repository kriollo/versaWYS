# Configuración

Toda la configuración vive en `versaWYS/config/config.json`. El archivo **no se versiona**: se crea con `php versaCLI config:init`. Los proyectos creados antes de que el kernel fuera un paquete lo tienen en `versaWYS/kernel/config/config.json`: mientras ese archivo exista se sigue usando (junto con `installed.lock` e `install.token` de la misma carpeta), así que no hay que moverlo en los servidores.

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

## Apariencia del panel

Los colores del panel se definen en `tailwind.config.js`:

- **`brand`** es el color de marca: botones principales, enlaces, foco y elementos activos (`bg-brand`, `text-brand`, `ring-brand`…). Para adaptar el panel a un proyecto basta cambiar esa paleta y recompilar con `pnpm compile`.
- **`gray`** usa grises neutros, para que las tarjetas y campos del tema oscuro queden en la misma gama que el fondo.

El panel trae tema claro y oscuro; el usuario lo cambia desde la barra superior.

## Modo debug

Con `build.debug` en `true` se muestran errores detallados (rutas de archivos, trazas y SQL) y la barra de depuración. La validación CSRF y los controles de rol siguen activos. No lo actives en un servidor accesible desde internet: en producción las respuestas de error solo llevan el mensaje y el detalle queda en los logs.

## Variables de entorno

Si existe un archivo `.env` en la raíz del proyecto, sus pares `CLAVE=valor` se cargan como variables de entorno (`getenv('CLAVE')`). No reemplazan a `config.json`.

`VERSAWYS_CONFIG` permite usar otro archivo de configuración sin tocar `config.json` (staging, tests). La leen tanto la aplicación como `versaCLI`:

```bash
VERSAWYS_CONFIG=/ruta/config.staging.json php versaCLI migrate:up
```

Debe definirse en el entorno del proceso (servidor web o shell), no en `.env`.

## Tests

`composer test` (dentro de `versaWYS/`) ejecuta dos suites:

- `tests/KernelTest.php`: pruebas unitarias del kernel y del CLI.
- `tests/HttpTest.php`: levanta la aplicación con `php -S` contra una base SQLite temporal y verifica por HTTP las cabeceras, la sesión, los tokens (CSRF y JWT), los permisos y la forma de los errores. Necesita las extensiones `pdo_sqlite` y `curl`; no usa ni modifica `config.json`.
