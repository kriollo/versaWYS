# Guía de Instalación de versaWYS-PHP

Esta guía te ayudará a instalar y configurar versaWYS-PHP en tu entorno de desarrollo de manera clara y ordenada.

## Tabla de Contenidos

- [Requisitos Previos](#requisitos-previos)
- [Flujo de Instalación Paso a Paso](#flujo-de-instalación-paso-a-paso)
- [Configuración del Servidor Web (Opcional)](#configuración-del-servidor-web-opcional)
  - [Apache](#apache)
  - [NGINX](#nginx)
- [Compilación de Assets para Producción](#compilación-de-assets-para-producción)

## Requisitos Previos

Asegúrate de tener los siguientes componentes instalados en tu sistema:

- **PHP 8.3 o superior** (incluye la extensión `php-intl`)
- **Composer** (manejador de dependencias para PHP)
- **MySQL 5.7 o superior** (o MariaDB)
- **Servidor web Apache 2.4+** (con `mod_rewrite` habilitado) o **NGINX**
- **Node.js 20 o superior** y **npm** (para la compilación de assets CSS/JS)

---

## Flujo de Instalación Paso a Paso

### 1. Obtener el Código Fuente

Abre una terminal en el directorio donde deseas instalar el proyecto y ejecuta uno de los siguientes comandos:

- **Clonar con Git (recomendado):**
    ```bash
    git clone https://github.com/kriollo/versaWYS.git .
    ```
- **Descargar ZIP:**
    Descarga desde [https://github.com/kriollo/versaWYS/archive/refs/heads/main.zip](https://github.com/kriollo/versaWYS/archive/refs/heads/main.zip) y descomprímelo en tu directorio de proyecto.

### 2. Instalar Dependencias PHP

Navega a la carpeta raíz del proyecto `versaWYS` y ejecuta:

```bash
composer install
```

Esto instalará todas las dependencias necesarias para el backend.

### 3. Crear el Archivo de Configuración

Antes de continuar, debes crear el archivo de configuración principal del framework. Ejecuta:

```bash
php versaCLI config:init
```

Esto generará el archivo `versaWYS/kernel/config/config.json` con una plantilla base. **No continúes hasta que este archivo exista.**

### 4. Configurar la Base de Datos

- Crea una base de datos en tu servidor MySQL/MariaDB.
- Abre el archivo `versaWYS/kernel/config/config.json` en tu editor.
- Actualiza la sección `"DB"` con tus credenciales de base de datos:
    ```json
    "DB": {
        "DB_HOST": "localhost",
        "DB_USER": "root",
        "DB_PASS": "tu_contraseña",
        "DB_NAME": "nombre_de_tu_bd"
    }
    ```

### 5. Ejecutar Migraciones de Base de Datos

Desde la raíz del proyecto, ejecuta:

```bash
php versaCLI migrate:up
```

Esto creará las tablas necesarias en tu base de datos.

### 6. Instalar Dependencias Frontend y Compilar Assets

Si vas a trabajar con el frontend (panel de administración, dashboard, etc.):

- **Instala las dependencias de Node.js:**
    ```bash
    npm install
    ```

- **Compila los archivos CSS y JS:**
    - Para compilar archivos JS:
        ```bash
        npm run versaCompileJS
        ```
    - Para compilar solo los estilos del dashboard:
        ```bash
        npm run versaCompileCSS
        ```
    - Consulta tu archivo `package.json` para ver todos los scripts disponibles y elige el que se ajuste a tu flujo de trabajo.

### 7. Iniciar el Servidor de Desarrollo

Puedes usar el servidor incorporado de PHP a través de la CLI de versaWYS:

```bash
node --run watch
```

Por defecto, tu aplicación estará disponible en [http://localhost:3000](http://localhost:3000).

- **Credenciales por defecto:**
    - Usuario: `admin@wys.cl`
    - Contraseña: `admin2023`

---

## Configuración del Servidor Web (Opcional)

Si vas a desplegar en producción o usar Apache/NGINX, revisa la sección correspondiente para configurar correctamente las URLs amigables y el acceso a los assets.

### Apache

Asegúrate de que `mod_rewrite` esté habilitado. Necesitarás un archivo `.htaccess` en el directorio raíz de tu proyecto (o en el directorio `public/` si esa es tu raíz web) con reglas similares a las siguientes para redirigir todas las solicitudes al `index.php` principal:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirige todas las solicitudes a index.php si no es un archivo o directorio existente
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [L,QSA]
</IfModule>
```

La configuración exacta puede variar dependiendo de si la raíz de tu sitio apunta al directorio principal del proyecto o a una subcarpeta como `public/`.

### NGINX

Para NGINX, la configuración del servidor podría verse así:

```nginx
server {
    listen 80;
    server_name tu_dominio.com;
    root /ruta/a/tu/proyecto/public; # O la raíz de tu proyecto si manejas las rutas de manera diferente

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # Ajusta la versión de PHP y la ruta del socket FPM
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Ajusta `tu_dominio.com`, la ruta al proyecto (`root`) y la configuración de `fastcgi_pass` según tu entorno.

---

## Compilación de Assets para Producción

Cuando estés listo para desplegar tu aplicación, compila y minifica tus archivos CSS y JS:

- Para CSS:
    ```bash
    npm run versaCompileCSS
    ```
- Para JS y otros assets:
    ```bash
    npm run versaCompileJS
    ```

**Nota Importante:**
Los nombres exactos de los scripts (`versaCompileCSS`, `watch`, `versaCompileJS`, etc.) **dependen exclusivamente de cómo estén definidos en la sección `"scripts"` de tu archivo `package.json`**. Siempre consulta ese archivo como la fuente autoritativa para estos comandos.

Si estás utilizando tareas preconfiguradas de VSCode que ejecutan estos scripts, asegúrate de que apunten a los nombres correctos definidos en tu `package.json`.

---

**¡Listo!**
Tu entorno versaWYS-PHP debería estar funcionando. Si tienes dudas, revisa la documentación incluida en la carpeta `documentacion/` o ejecuta `php versaCLI help` para ver todos los comandos disponibles.


**NOTA**
**versaCompiler:** El comando `npm run watch` o `npx versacompiler` compila y transpila los archivos JS/CSS de `src/` hacia los directorios públicos, facilitando el flujo de trabajo frontend.

para más información sobre versaCompiler, consulta su [repositorio oficial](https://github.com/versaCompiler)
