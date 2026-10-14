# Guía de Instalación de versaWYS-PHP

## Requisitos

- **PHP 8.3 o superior** con las extensiones `intl`, `pdo`, `mbstring`, `fileinfo`, `curl`, `zip` y el driver de tu base de datos (`pdo_mysql` o `pdo_pgsql`).
- **Composer**.
- **MySQL 8 / MariaDB 10.5+** o **PostgreSQL 13+**.
- **Apache 2.4+** (con `mod_rewrite`) o **NGINX**. Para desarrollo basta `php versaCLI serve`.
- **Node.js 20+** y **pnpm** para compilar el frontend.

Puedes revisar el estado del entorno en cualquier momento con `php versaCLI status`.

## Inicio rápido

Crea una base de datos **vacía** (MySQL o PostgreSQL) y luego:

```bash
composer create-project versawys/versawys mi-proyecto   # instala y crea versaWYS/config/config.json
cd mi-proyecto && pnpm install && pnpm compile
php versaCLI app:install                                  # pregunta la base de datos, la empresa y el superadministrador
php versaCLI serve                                        # http://localhost:8000/admin/login
```

> En desarrollo local sin HTTPS, cambia `session.user_cookie.secure` a `false` en `config.json`; de lo contrario el navegador no enviará la cookie de sesión.

## Instalación paso a paso

### 1. Obtener el código e instalar dependencias

```bash
composer create-project versawys/versawys mi-proyecto
cd mi-proyecto
pnpm install
```

`create-project` ejecuta `php versaCLI config:init`, que genera `versaWYS/config/config.json` con claves de sesión aleatorias y el **token del instalador web** (también queda en `versaWYS/config/install.token`).

Si clonas el repositorio en vez de usar `create-project`: `git clone https://github.com/kriollo/versaWYS.git mi-proyecto && cd mi-proyecto && composer install && php versaCLI config:init`.

### 2. Compilar el frontend

```bash
pnpm compile      # producción
pnpm watch        # desarrollo, con recarga en caliente
```

### 3. Instalar la aplicación

Las dos vías hacen lo mismo:
- prueban la conexión y guardan los datos en `config.json`;
- ejecutan las migraciones y los seeders;
- crean la empresa y el superadministrador;
- escriben `installed.lock`.

Desde ese momento ninguna de las dos vuelve a ejecutarse.

**Desde la terminal** (también sirve en servidores sin navegador):

```bash
php versaCLI app:install
```

Pregunta cada dato; las contraseñas no se muestran al escribirlas. Para un script, todo va como opciones: `php versaCLI app:install --db-driver=postgresql --db-host=localhost --db-port=5432 --db-name=... --db-user=... --db-pass=... --empresa-nombre=... --empresa-rut=... --admin-name=... --admin-email=... --admin-password=...`

**Desde el navegador**: `php versaCLI serve` y abre `http://localhost:8000/install`. El asistente pide además el token del paso 1, y lo elimina al terminar.

Luego entra por `/admin/login`.

## Servidor web

En `configs/` hay plantillas para NGINX (`nginx.conf.example`) y Apache (`apache-vhost.conf.example`). La raíz del sitio es la carpeta del proyecto (donde está `index.php`); los `.htaccess` incluidos bloquean el acceso directo a `app/`, `versaWYS/` y a los archivos de configuración.

Para desplegar con versaDeploy, copia `versa_deploy.example.yml` a `versa_deploy.yml` y completa los datos del servidor.

### Qué debe quedar accesible desde el navegador

El frontend compilado importa Vue y SweetAlert2 desde `/node_modules/<paquete>/dist/`. Por eso `node_modules` de producción (`pnpm install --production`) debe existir en el servidor, y las reglas incluidas (`.htaccess`, plantillas de `configs/` y el servidor de desarrollo) sirven de esa carpeta **solo archivos `.js`, `.mjs` y `.css`**. Si las páginas del panel aparecen en blanco, revisa en la pestaña de red del navegador que esas dos librerías carguen.

## Producción

```bash
php versaCLI config:debug:false
php versaCLI config:templateCache:true
```

Con la caché de plantillas activa, Twig no detecta cambios en los `.twig`. Después de cada despliegue ejecuta `php versaCLI cache:twig`; `app:deploy:finish` (el hook de la plantilla de versaDeploy) ya lo hace. Las rutas no tienen caché: se leen de `app/routes` y siempre están al día.

### PHP del servidor web

Lo que más pesa en el rendimiento es cómo corre PHP en el servidor, no el código. Medido con `vendor/bin/pest --group=bench`: una página tarda **1,4 ms** con OPcache y sin Xdebug, y **15 ms** con Xdebug activo y sin caché de Twig.

- **OPcache activo** (`opcache.enable=1`) en el `php.ini` de FPM o Apache. Con versaDeploy, que recarga php-fpm después de cada despliegue, puede usarse `opcache.validate_timestamps=0`.
- **Sin Xdebug** en producción.
- `twig.cache` activo (`php versaCLI config:templateCache:true`).

El PHP de la terminal suele tener otro `php.ini` que el del servidor web. Por eso `app:check:deploy --production` le pregunta a **`/up`** cómo corre PHP en el servidor web. `/up` responde `{"status":"ok"}` a cualquiera; el detalle solo lo entrega a quien envía la cabecera firmada con `session.key`, como hace `app:check:deploy` (la terminal y el servidor web deben usar el mismo `config.json`); también sirve para monitorear el sitio.

Consulta la [Guía de Configuración](./CONFIGURATION.md) para el resto de opciones.
