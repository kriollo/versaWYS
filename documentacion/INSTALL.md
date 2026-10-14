# Guía de Instalación de versaWYS-PHP

## Requisitos

- **PHP 8.3 o superior** con las extensiones `intl`, `pdo`, `mbstring`, `fileinfo`, `curl`, `zip` y el driver de tu base de datos (`pdo_mysql` o `pdo_pgsql`).
- **Composer**.
- **MySQL 8 / MariaDB 10.5+** o **PostgreSQL 13+**.
- **Apache 2.4+** (con `mod_rewrite`) o **NGINX**. Para desarrollo basta `php versaCLI serve`.
- **Node.js 20+** y **pnpm** para compilar el frontend.

Puedes revisar el estado del entorno en cualquier momento con `php versaCLI status`.

## Instalación paso a paso

### 1. Obtener el código

```bash
git clone https://github.com/kriollo/versaWYS.git mi-proyecto
cd mi-proyecto
```

### 2. Instalar dependencias

```bash
cd versaWYS && composer install && cd ..
pnpm install
```

### 3. Crear la configuración

```bash
php versaCLI config:init
```

Genera `versaWYS/config/config.json` con claves de sesión aleatorias y muestra el **token del instalador web**. El token también queda en `versaWYS/config/install.token`.

### 4. Compilar el frontend

```bash
pnpm compile      # producción
pnpm watch        # desarrollo, con recarga en caliente
```

### 5. Ejecutar el instalador web

Crea una base de datos **vacía**, levanta el servidor y abre `/install`:

```bash
php versaCLI serve
# http://localhost:8000/install
```

El asistente pide los datos de la base de datos, de la empresa, del usuario superadministrador y el token del paso 3. Con eso:

1. prueba la conexión y guarda los datos en `config.json`;
2. ejecuta las migraciones y los seeders;
3. crea la empresa y el superadministrador;
4. escribe `installed.lock` y elimina el token.

Desde ese momento `/install` deja de estar disponible y puedes entrar por `/admin/login`.

> En desarrollo local sin HTTPS, cambia `session.user_cookie.secure` a `false` en `config.json`; de lo contrario el navegador no enviará la cookie de sesión.

### Instalación sin el asistente

Si prefieres la terminal, completa la sección `database` de `config.json` y ejecuta:

```bash
php versaCLI migrate:up
php versaCLI seeder:runAll
```

Los seeders no crean usuarios: el superadministrador se crea con el instalador web.

## Servidor web

En `configs/` hay plantillas para NGINX (`nginx.conf.example`) y Apache (`apache-vhost.conf.example`). La raíz del sitio es la carpeta del proyecto (donde está `index.php`); los `.htaccess` incluidos bloquean el acceso directo a `app/`, `versaWYS/` y a los archivos de configuración.

Para desplegar con versaDeploy, copia `versa_deploy.example.yml` a `versa_deploy.yml` y completa los datos del servidor.

### Qué debe quedar accesible desde el navegador

El frontend compilado importa Vue y SweetAlert2 desde `/node_modules/<paquete>/dist/`. Por eso `node_modules` de producción (`pnpm install --production`) debe existir en el servidor, y las reglas incluidas (`.htaccess`, plantillas de `configs/` y el servidor de desarrollo) sirven de esa carpeta **solo archivos `.js`, `.mjs` y `.css`**. Si las páginas del panel aparecen en blanco, revisa en la pestaña de red del navegador que esas dos librerías carguen.

## Producción

```bash
php versaCLI config:debug:false
php versaCLI config:templateCache:true
php versaCLI route:compile
```

Con la caché de plantillas activa, Twig no detecta cambios en los `.twig`. Después de cada despliegue ejecuta `php versaCLI cache:twig` (y `route:compile` si cambiaron rutas); la plantilla de versaDeploy ya lo hace.

Consulta la [Guía de Configuración](./CONFIGURATION.md) para el resto de opciones.
