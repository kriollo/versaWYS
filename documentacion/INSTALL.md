# Guía de Instalación de versaWYS-PHP

## Requisitos

- **PHP 8.3 o superior** con las extensiones `pdo`, `json`, `mbstring`, `intl`, `openssl`, `fileinfo`, `curl`, `zip`, `xml`, `dom` y el driver de tu base de datos (`pdo_mysql` o `pdo_pgsql`). Más las que pidan las dependencias de producción: `versaCLI status` y el asistente de `/install` las calculan a partir de `composer.lock`.
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

> La cookie de sesión es segura (`session.user_cookie.secure`). En `localhost`, Chrome y Firefox la aceptan por `http`. Con otro dominio sin HTTPS, el navegador no la guarda: activa SSL o, solo para pruebas, cambia esa clave a `false` en `config.json`.

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

**Desde el navegador**: `php versaCLI serve` y abre `http://localhost:8000`. Mientras la instalación esté pendiente, cualquier página lleva a `/install`, que primero muestra los requisitos del servidor. El asistente pide además el token del paso 1 (está en `versaWYS/config/install.token`) y lo elimina al terminar.

Luego entra por `/admin/login`.

## Hosting sin terminal (cPanel)

Todo se hace desde el navegador y el Administrador de archivos; no hace falta consola.

1. **Prepara el proyecto en tu equipo:** `composer install --no-dev --optimize-autoloader` y `pnpm install && pnpm compile`. El hosting no necesita Composer ni Node.
2. **Sube los archivos** a la carpeta del dominio, incluidos `versaWYS/vendor/` y `public/`, pero sin `versaWYS/config/config.json`.
3. **Crea la base de datos** vacía y su usuario en «Bases de datos MySQL» (o PostgreSQL) de cPanel.
4. **Abre el sitio con `https://`.** Si el SSL todavía no está activo, actívalo en «SSL/TLS Status» (AutoSSL): por `http` el navegador no guarda la cookie de sesión y el instalador no puede funcionar.
5. **El primer acceso** crea `versaWYS/config/config.json` y el token de instalación, y abre el asistente en `/install`.
6. **Paso 1, revisión del servidor:** la misma lista que `php versaCLI status`, con versión de PHP, extensiones, carpetas con permiso de escritura, frontend compilado y HTTPS. Cada falla dice cómo corregirla en cPanel. «Continuar» solo se habilita cuando todo está bien.
7. **Paso 2, datos:** base de datos, empresa, superadministrador y token. Para el token, abre `versaWYS/config/install.token` en el Administrador de archivos y copia su contenido.
8. **Paso 3:** el resumen de lo que hizo y el enlace al panel.

Para las tareas programadas, agrega en «Trabajos de cron» de cPanel la línea de `app:schedule:run` (ver [Tareas programadas](./Tareas_Programadas.md)).

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

Para una migración larga o una restauración, `php versaCLI app:down --secret=equipo` deja el sitio en 503 mientras el equipo sigue entrando por `/equipo`, y `app:up` lo vuelve a abrir (ver [Modo mantenimiento](./LineaDeComandos.md#modo-mantenimiento)).

Con la caché de plantillas activa, Twig no detecta cambios en los `.twig`. Después de cada despliegue ejecuta `php versaCLI cache:twig`; `app:deploy:finish` (el hook de la plantilla de versaDeploy) ya lo hace. Las rutas no tienen caché: se leen de `app/routes` y siempre están al día.

### PHP del servidor web

Lo que más pesa en el rendimiento es cómo corre PHP en el servidor, no el código. Medido con `vendor/bin/pest --group=bench`: una página tarda **1,4 ms** con OPcache y sin Xdebug, y **15 ms** con Xdebug activo y sin caché de Twig.

- **OPcache activo** (`opcache.enable=1`) en el `php.ini` de FPM o Apache. Con versaDeploy, que recarga php-fpm después de cada despliegue, puede usarse `opcache.validate_timestamps=0`.
- **Sin Xdebug** en producción.
- `twig.cache` activo (`php versaCLI config:templateCache:true`).

El PHP de la terminal suele tener otro `php.ini` que el del servidor web. Por eso `app:check:deploy --production` le pregunta a **`/up`** cómo corre PHP en el servidor web. `/up` responde `{"status":"ok"}` a cualquiera; el detalle solo lo entrega a quien envía la cabecera firmada con `session.key`, como hace `app:check:deploy` (la terminal y el servidor web deben usar el mismo `config.json`); también sirve para monitorear el sitio.

### Tareas programadas y cola de trabajos

Una sola línea en el crontab del servidor ejecuta todas las tareas de `app/schedule.php`:

```cron
* * * * * /usr/bin/php /var/www/mi-proyecto/current/versaCLI app:schedule:run >> /var/www/mi-proyecto/current/versaWYS/logs/cron.log 2>&1
```

Si el proyecto usa `Queue`, la cola se procesa como una tarea más (`$schedule->command('app:queue:work --stop-when-empty')->everyMinute()->withoutOverlapping()`). Si la cola es muy activa, usa `php versaCLI app:queue:work` como servicio de systemd o supervisor y reinícialo en cada despliegue. Ver [Tareas programadas](./Tareas_Programadas.md) y [Caché, colas y archivos](./Cache_Colas_Archivos.md).

Consulta la [Guía de Configuración](./CONFIGURATION.md) para el resto de opciones.
