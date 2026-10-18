# Configuración

Toda la configuración vive en `versaWYS/config/config.json`. El archivo **no se versiona**: se crea con `php versaCLI config:init`. Los proyectos creados antes de que el kernel fuera un paquete lo tienen en `versaWYS/kernel/config/config.json`: mientras ese archivo exista se sigue usando (junto con `installed.lock` e `install.token` de la misma carpeta), así que no hay que moverlo en los servidores.

Los valores por defecto están en el kernel, en `versaWYS/vendor/versawys/kernel/src/config/config.default.php`, y se fusionan con tu `config.json` en cada arranque, así que `config.json` solo necesita declarar lo que cambia. Ese mismo archivo es la referencia completa de claves disponibles.

## Secciones

| Sección | Para qué sirve |
|---|---|
| `database` | Conexión: `driver` (`mysql`, `postgresql` o `sqlite`, que usan las pruebas), `host`, `port`, `database`, `username`, `password`, `charset`, `collation`. |
| `admin` | `custom_url`: prefijo público del panel (las rutas se declaran como `/admin/...` y se sirven bajo este prefijo). `hide_default`: responde 404 en `/admin` cuando hay un prefijo propio. |
| `build` | `debug`, `name`, `version`, `timezone`, `locale`, `charset`. `version` se usa como parámetro de caché de los assets. `base_url`: URL pública (`https://app.ejemplo.cl`) con la que se arman los enlaces de los correos; en producción conviene fijarla, porque si queda vacía se deduce de la cabecera `Host` de la petición. |
| `trusted_proxies` | IPs o rangos CIDR de los proxies propios (balanceador, Cloudflare). Solo desde ellos se aceptan `X-Forwarded-For`, `-Proto` y `-Host`; sin esto, cualquiera podría falsificarlas. Vacío por defecto. |
| `twig` | `cache`, `compiled_dir`, `templates_dir`, `strict_variables`, `auto_escape`. |
| `auth` | `expiration_days_password`, `inactive_account_days` y `password_policy` (largo mínimo, mayúsculas, minúsculas, números, caracteres especiales). |
| `session` | `key` (firma de los JWT), `lifetime` y `user_cookie` (`domain`, `secure`, `http_only`, `same_site`, `lifetime_remember`). |
| `crypt` | `key` (opcional, 32 bytes en base64): clave de `Crypt` y `SignedUrl`. Sin ella se deriva de `session.key`. Ver [Seguridad](./Seguridad_Principios_Basicos.md#8-secretos-guardados-y-enlaces-firmados). |
| `login_attempt` | `max` intentos y `time` de bloqueo en segundos. |
| `mail` | SMTP: `host`, `port`, `transport`, `secure` (`tls`, `ssl` o vacío), `name_from`, `username`, `password`. El certificado del servidor se verifica; `verify_peer: false` lo desactiva para un SMTP interno autofirmado. |
| `cors` | `allowed_origins`: orígenes externos autorizados a llamar a la API con credenciales. Vacío por defecto. |
| `permissions_policy` | Valor de la cabecera `Permissions-Policy`: qué APIs del navegador puede usar el sitio. Por defecto, cámara, micrófono y ubicación solo para el propio sitio, y pagos, USB, puerto serie y Bluetooth para nadie. Vacío para no enviarla. |
| `csp` | `enabled`, `nonce`, `unsafe_eval` y orígenes extra por directiva, por ejemplo `"connect-src": ["https://api.ejemplo.com"]`. Con `nonce: true` (el valor de las instalaciones nuevas), cada `<script>` inline necesita `nonce="{{ csp_nonce() }}"` y los scripts que crea el JavaScript usan el valor de `<meta name="csp-nonce">`. No se permiten manejadores inline (`onclick=`, `onchange=`): el panel usa `data-history-back` y `data-auto-submit`. Con `unsafe_eval: false` (también el valor de las instalaciones nuevas) la CSP no permite `eval`: ningún componente Vue puede usar `template:` en texto, solo `.vue` compilados o funciones `render`. |
| `rate_limiting` | `enabled` y reglas `global`, `api`, `login`, `upload`, `sensitive` (`requests` por `window` segundos). |
| `logging` | `dir`, `min_level`, `format` (`json` o `text`), `max_file_size`, `retention_days`. Ver [Logging](./Logging.md). |
| `redis` | Conexión opcional a Redis. Ver [RedisModel](./RedisModel.md). |
| `cache` | `driver` (`file` o `redis`), `dir` (por defecto `versaWYS/cache/data/`) y `prefix` de `Cache`. En Redis, cada proyecto usa sus propias claves: `prefix`, o una derivada de `session.key` si está vacío. Así varios sitios comparten un Redis sin leer ni borrar la caché del otro. Ver [Caché, colas y archivos](./Cache_Colas_Archivos.md). |
| `queue` | `driver`: `database` (tabla `versa_jobs`, procesada por `app:queue:work`) o `sync` (el trabajo corre en el acto; para pruebas). |
| `storage` | `root`: carpeta de `Storage`, por defecto `versaWYS/storage`. |
| `profiler` | `enabled` y `allowed_ips` del [profiler](./Profiler.md). Solo actúa con `build.debug`. |
| `websocket_service` | `ws_url` opcional que se entrega al frontend. |
| `assets` | Rutas de origen y destino de JS, CSS, imágenes, avatares y archivos subidos. |

## Cambiar la configuración

- **Desde el panel**: *Configuración* (solo superadministrador) edita:
  - correo, intentos de login, política de contraseñas y URL del panel;
  - los datos del build y el **modo debug**. Activarlo pide confirmación, y cada cambio queda en el log `security` con el usuario que lo hizo.

  También permite enviar un correo de prueba, respaldar la base de datos y ver las [tareas programadas](./Tareas_Programadas.md). El correo es opcional: sin servidor SMTP se pueden guardar las demás pestañas.
- **Desde la terminal**:

  ```bash
  php versaCLI config:debug:true|false
  php versaCLI config:templateCache:true|false
  php versaCLI cache:clear            # limpia las cachés de Twig, rate limiting y Cache
  ```

- **A mano**: edita `config.json`. La conexión a la base de datos y las claves de sesión solo se cambian así o por CLI, nunca desde el navegador.

## Apariencia del panel

Los colores del panel se definen en el bloque `@theme` de `src/dashboard/css/dashboard.css` (Tailwind 4: ya no hay `tailwind.config.js`):

- **`brand`** (`--color-brand-*`) es el color de marca: botones principales, enlaces, foco y elementos activos (`bg-brand`, `text-brand`, `ring-brand`…). Para adaptar el panel a un proyecto basta cambiar esa paleta y recompilar con `pnpm compile`.
- **`gray`** usa grises neutros, para que las tarjetas y campos del tema oscuro queden en la misma gama que el fondo.

El panel trae tema claro y oscuro; el usuario lo cambia desde la barra superior.

En el mismo `dashboard.css` están el modo oscuro por la clase `.dark` (`@custom-variant dark`) y las clases que se arman en tiempo de ejecución (`@source inline(...)`, lo que en Tailwind 3 era `safelist`). Tailwind 4 encuentra solo las clases en los archivos del proyecto que git no ignora. `public/vendor/flowbite/flowbite.min.css` trae solo la base y los componentes de Flowbite (formularios, tooltips, gráficos) en la capa `flowbite`, la de menor prioridad: el CSS del panel siempre la sobrescribe.

## Modo debug

Con `build.debug` en `true`:

- los errores se muestran en detalle: en el navegador, una página con el código alrededor de cada línea de la traza y el SQL; en las API, el mismo detalle en JSON;
- cada página muestra la barra del [profiler](./Profiler.md), con el detalle de la petición en `/_profiler`.

La validación CSRF y los controles de rol siguen activos. No lo actives en un servidor accesible desde internet: en producción las respuestas de error solo llevan el mensaje y el detalle queda en los logs.

## Variables de entorno

Si existe un archivo `.env` en la raíz del proyecto, sus pares `CLAVE=valor` se cargan como variables de entorno (`getenv('CLAVE')`). No reemplazan a `config.json`.

`VERSAWYS_CONFIG` permite usar otro archivo de configuración sin tocar `config.json` (staging, tests). La leen tanto la aplicación como `versaCLI`:

```bash
VERSAWYS_CONFIG=/ruta/config.staging.json php versaCLI migrate:up
```

Debe definirse en el entorno del proceso (servidor web o shell), no en `.env`.

## Tests

`composer test`, desde la raíz del proyecto, corre las pruebas de `versaWYS/tests/` contra una base SQLite desechable: no usa ni modifica `config.json` ni tu base de datos. Necesita `pdo_sqlite`. Ver [Pruebas](./Pruebas.md).

## Ajustes versionados: `config:merge`

`config.json` tiene secretos y no se versiona. Los ajustes **sin secretos** que dependen del proyecto, como los orígenes extra de la CSP, CORS u opciones de correo, pueden vivir en un JSON versionado y aplicarse en cada despliegue:

```json
{
    "csp": {
        "script-src": ["https://accounts.google.com"],
        "form-action": ["https://webpay3g.transbank.cl"]
    }
}
```

```bash
php versaCLI config:merge versaWYS/config.compartida.json
```

Se fusiona con el `config.json` de la máquina sin tocar el resto: las listas se reemplazan enteras y aplicarlo dos veces no cambia nada. Si el archivo no existe o no es JSON válido, termina con código 1 y el despliegue se detiene. En versaDeploy va como primer hook de `post_deploy`.

## Verificar la CSP en el navegador

Al activar `csp.nonce` en un proyecto, cualquier `<script>` sin nonce o manejador inline (`onclick=`) deja de funcionar. `versaWYS/tests/csp-check.mjs` lo comprueba con Chrome sin interfaz: inicia sesión, recorre las pantallas del menú, la navegación SPA, el 404 y los botones del panel, y lista cada bloqueo de CSP y error de JavaScript.

```bash
# Servidor con build.debug=false, csp.enabled y csp.nonce en true (en debug no se envía CSP)
VERSAWYS_CONFIG=/tmp/config-csp.json php -S 127.0.0.1:8790 -t . versaWYS/vendor/versawys/kernel/src/cli/routerServe.php &
PANEL=/admin EMAIL=admin@ejemplo.cl PASS='...' DISCOVER=1 node versaWYS/tests/csp-check.mjs http://127.0.0.1:8790 /tmp/csp
```

Para revisar también páginas fuera del panel (una tienda, formularios públicos), `EXTRA=/tienda,/contacto` las agrega al recorrido. Termina con código 1 si encuentra problemas. Necesita Google Chrome y Node 22 (no instala nada).
