# Guía de Instalación de versaWYS-PHP

Esta guía te ayudará a instalar y configurar versaWYS-PHP en tu entorno de desarrollo.

## Tabla de Contenidos

- [Requisitos Previos](#requisitos-previos)
- [Pasos de Instalación](#pasos-de-instalación)
  - [1. Obtener el Código Fuente](#1-obtener-el-código-fuente)
  - [2. Instalar Dependencias PHP](#2-instalar-dependencias-php)
  - [3. Configurar la Base de Datos](#3-configurar-la-base-de-datos)
  - [4. Ejecutar Migraciones de Base de Datos](#4-ejecutar-migraciones-de-base-de-datos)
  - [5. Instalar Dependencias Frontend y Compilar Assets](#5-instalar-dependencias-frontend-y-compilar-assets)
  - [6. Iniciar el Servidor de Desarrollo](#6-iniciar-el-servidor-de-desarrollo)
- [Configuración del Servidor Web (Opcional)](#configuración-del-servidor-web-opcional)
  - [Apache](#apache)
  - [NGINX](#nginx)
- [Compilación de Assets para Producción](#compilación-de-assets-para-producción)

## Requisitos Previos

Asegúrate de tener los siguientes componentes instalados en tu sistema:

- PHP 8.3 o superior (incluyendo la extensión `php-intl`, puedes instalarla con `apt install php-intl` en sistemas Debian/Ubuntu)
- Composer (manejador de dependencias para PHP)
- MySQL 5.7 o superior (o una base de datos compatible como MariaDB)
- Servidor web Apache 2.4 o superior (con `mod_rewrite` habilitado) o NGINX.
- Node.js 20 o superior (para la compilación de assets CSS/JS)

## Pasos de Instalación

Sigue estos pasos para poner en marcha tu proyecto con versaWYS-PHP:

### 1. Obtener el Código Fuente

Abre una terminal en el directorio donde deseas instalar el proyecto y ejecuta uno de los siguientes comandos:

*   Para clonar con Git (recomendado):
    ```bash
    git clone https://github.com/kriollo/versaWYS.git .
    ```
*   Para descargar el archivo ZIP:
    Descarga desde [https://github.com/kriollo/versaWYS/archive/refs/heads/main.zip](https://github.com/kriollo/versaWYS/archive/refs/heads/main.zip) y descomprímelo en tu directorio de proyecto.

### 2. Instalar Dependencias PHP

Navega a la carpeta raíz del proyecto (donde clonaste o descomprimiste el código) y ejecuta Composer para instalar las dependencias del backend:

```bash
# Si no estás en la carpeta del proyecto, navega a ella primero.
# Ejemplo: cd ruta/a/tu/proyecto/versaWYS
composer install
```

### 3. Configurar la Base de Datos

*   Crea una base de datos en tu servidor MySQL.
*   Abre el archivo de configuración `versaWYS/kernel/config/config.json` en tu editor de código.
*   Actualiza la sección `"DB"` con tus credenciales de base de datos:
    ```json
    {
        // ... otras configuraciones ...
        "DB": {
            "DB_HOST": "tu_host_de_bd",       // Ejemplo: "localhost" o "127.0.0.1"
            "DB_USER": "tu_usuario_de_bd",   // Ejemplo: "root"
            "DB_PASS": "tu_contraseña_de_bd", // Ejemplo: "password"
            "DB_NAME": "tu_nombre_de_bd"     // Ejemplo: "versawys_db"
        },
        // ... otras configuraciones ...
    }
    ```

### 4. Ejecutar Migraciones de Base de Datos

En la terminal, desde la raíz del proyecto, ejecuta el siguiente comando para crear las tablas necesarias en tu base de datos:
```bash
php versaCLI migrate:up
```

### 5. Instalar Dependencias Frontend y Compilar Assets

Estos pasos son necesarios si planeas trabajar con el panel de administración o los assets frontend que vienen con el framework.

*   **Instalar dependencias de Node.js:**
    Asegúrate de estar en la raíz del proyecto y ejecuta:
    ```bash
    npm install
    ```
    Esto leerá el archivo `package.json` y descargará las librerías necesarias (como TailwindCSS, etc.).

*   **Compilar archivos CSS y JS (Desarrollo):**
    Los comandos para compilar tus assets (CSS y JavaScript) durante el desarrollo se encuentran definidos como scripts en tu archivo `package.json`. Revisa la sección `"scripts"` de ese archivo para conocer los comandos exactos.

    Comúnmente, podrías tener scripts como:
    -   Para CSS (ej. TailwindCSS en modo watch):
        ```bash
        npm run watch:css  # O el nombre del script que tengas definido, ej: dev:css, tailwind:watch
        ```
    -   Para JavaScript (ej. en modo watch):
        ```bash
        npm run watch:js   # O el nombre del script que tengas definido, ej: dev:js, watch
        ```

    **Importante:** Los nombres de los scripts (`watch:css`, `watch:js`, `dashboard`, `watch`) pueden variar. **Consulta siempre tu `package.json` para los comandos correctos.** Por ejemplo, si en tu `package.json` tienes:
    ```json
    "scripts": {
      "compile-styles": "tailwindcss -i ./src/css/input.css -o ./public/css/style.css --watch",
      "compile-scripts": "node watch.js"
    }
    ```
    Entonces ejecutarías `npm run compile-styles` y `npm run compile-scripts`.

### 6. Iniciar el Servidor de Desarrollo

Puedes usar el servidor incorporado de PHP a través de la CLI de versaWYS:
```bash
php versaCLI serve
```
Esto iniciará tu aplicación, y por defecto será accesible en `http://localhost:8000`.

*   **Credenciales por Defecto:**
    *   Usuario: `admin@wys.cl`
    *   Contraseña: `admin2023`

¡Felicidades! Ahora deberías tener una instancia funcional de versaWYS-PHP ejecutándose en tu máquina local.

## Configuración del Servidor Web (Opcional)

Si planeas desplegar tu aplicación en un entorno de producción o usar un servidor web más robusto que el servidor de desarrollo de PHP (como Apache o NGINX), necesitarás configurarlo adecuadamente para que las URLs amigables funcionen correctamente.

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
Asegúrate de ajustar `tu_dominio.com`, la ruta al proyecto (`root`) y la configuración de `fastcgi_pass` según tu entorno.

## Compilación de Assets para Producción

Cuando estés listo para desplegar tu aplicación, querrás tener tus archivos CSS y JS compilados y minificados para producción.

Al igual que con los scripts de desarrollo, los comandos para la compilación de producción se definen en tu `package.json`.

-   **Para compilar y minificar CSS (ej. con TailwindCSS):**
    Busca un script en tu `package.json` similar a `build:css`, `prod:css` o `tailwindcss:build`.
    ```bash
    npm run build:css  # Reemplaza build:css con el nombre de tu script
    ```

-   **Para compilar y minificar/ofuscar JS:**
    Busca un script en tu `package.json` similar a `build:js`, `prod:js` o `compile:js`.
    ```bash
    npm run build:js   # Reemplaza build:js con el nombre de tu script
    ```

**Nota Importante:**
Los nombres exactos de los scripts (`build:css`, `build:js`, `dashboard`, `watch`, etc.) **dependen exclusivamente de cómo estén definidos en la sección `"scripts"` de tu archivo `package.json`**. Siempre consulta ese archivo como la fuente autoritativa para estos comandos.

Si estás utilizando tareas preconfiguradas de VSCode que ejecutan estos scripts, asegúrate de que apunten a los nombres correctos definidos en tu `package.json`.
