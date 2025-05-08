# Archivo de Configuración `versaWYS/kernel/config/config.json`

El archivo `config.json` es fundamental para el funcionamiento de versaWYS-PHP, ya que centraliza todas las configuraciones esenciales del framework y de la aplicación.

## ¿Qué es este archivo?

Este archivo contiene toda la configuración centralizada del framework versaWYS-PHP. Si tienes dudas sobre cómo crearlo o modificarlo, consulta la [Guía de Línea de Comandos (CLI)](./Guia_versaCLI.md) y la [Guía de Instalación](./INSTALL.md).

**¡IMPORTANTE!**

- Antes de iniciar el desarrollo o la ejecución de cualquier comando, debes asegurarte de que el archivo de configuración exista. Puedes crearlo fácilmente con [versaCLI](./Guia_versaCLI.md).
- Si es tu primer uso, simplemente ejecuta en la terminal:

```bash
php versaCLI config:init
```

Esto creará automáticamente el archivo `config.json` con una plantilla base en la ruta `versaWYS/kernel/config/config.json`. Si el archivo ya existe, el comando te avisará y no lo sobrescribirá.

Este archivo es cargado automáticamente al inicio de la aplicación.

> **Recomendación:** No subas (no "versiones") este archivo con información sensible real a ningún repositorio público o compartido. Consulta la sección de **Consideraciones de Seguridad** al final de este documento o la [Guía de Seguridad](./Seguridad_Principios_Basicos.md).

## Tabla de Contenidos

- [Estructura General del Archivo](#estructura-general-del-archivo)
- [Secciones Principales](#secciones-principales)
  - [`framework`](#framework-string)
  - [`DB`](#db-object)
  - [`twig`](#twig-object)
  - [`build`](#build-object)
  - [`session`](#session-object)
  - [`api`](#api-object)
  - [`login_attempt`](#login_attempt-object)
  - [`mail`](#mail-object)
  - [`assets`](#assets-object)
- [Uso de la Configuración en la Aplicación](#uso-de-la-configuración-en-la-aplicación)
- [¡Importante! Consideraciones de Seguridad](#importante-consideraciones-de-seguridad)
- [Guía de Línea de Comandos (CLI)](./Guia_versaCLI.md)

## Estructura General del Archivo

El archivo sigue un formato JSON. Aquí se muestra un ejemplo completo de su estructura:

```json
{
    "framework": "versaWYS-PHP 0.9.8", // Versión actual del framework
    "DB": { // Configuración de la conexión a la base de datos
        "DB_HOST": "localhost",         // Host de la base de datos (ej. "127.0.0.1")
        "DB_USER": "local",             // Usuario de la base de datos
        "DB_PASS": "local",             // Contraseña del usuario de la base de datos
        "DB_NAME": "versawys"           // Nombre de la base de datos
    },
    "twig": { // Configuración del motor de plantillas Twig
        "cache": false,                 // Habilita o deshabilita la caché de plantillas (true/false). Para desarrollo se recomienda false.
        "compiled_dir": "./app/templates/.cache", // Directorio donde se almacenan las plantillas compiladas en caché.
        "templates_dir": "./app/templates", // Directorio principal donde se encuentran los archivos de plantillas (.twig).
        "strict_variables": true,       // Si es true, Twig lanzará una excepción si una variable no existe. Si es false, las variables no definidas se tratan como null.
        "auto_escape": true,            // Habilita o deshabilita el auto-escapado de variables en las plantillas para prevenir XSS.
        "default_template_404": "versaWYS/404" // Plantilla Twig por defecto a mostrar para errores 404 (Not Found).
    },
    "build": { // Configuración general de la aplicación
        "debug": true,                  // Modo debug (true/false). Afecta la visualización de errores detallados.
        "name": "WYS Soluciones Informáticas", // Nombre de la aplicación o empresa.
        "version": "1.0.0",             // Versión de la aplicación.
        "timezone": "America/Santiago", // Zona horaria de la aplicación (ej. "UTC", "America/New_York").
        "charset": "utf-8"              // Juego de caracteres por defecto para la aplicación.
    },
    "session": { // Configuración de las sesiones PHP
        "key": "WYS",                   // Nombre de la cookie de sesión.
        "lifetime": 3600,               // Tiempo de vida de la sesión en segundos (1 hora por defecto).
        "user_cookie": { // Configuración específica para la cookie de "recordar usuario"
            "domain": "localhost",          // Dominio para el cual la cookie es válida.
            "enable": true,                 // Habilita o deshabilita la funcionalidad de "recordar usuario" (true/false).
            "key_encript": "X8f3p@9Vde0WvKauzI8C2", // Clave secreta para encriptar/desencriptar el contenido de la cookie. ¡Cambiar por una clave segura!
            "lifetime": 3600,               // Tiempo de vida de la cookie si no se marca "recordar" (1 hora).
            "lifetime_remember": 2592000,   // Tiempo de vida de la cookie si se marca "recordar" (30 días).
            "secure": false,                // Si es true, la cookie solo se enviará sobre conexiones HTTPS.
            "http_only": false              // Si es true, la cookie no será accesible mediante JavaScript. // RECOMENDADO: true para producción
        }
    },
    "api": { // Configuración relacionada con la API
        "auth": false                   // Indica si la API requiere autenticación por defecto. Puede ser sobrescrito a nivel de ruta.
    },
    "login_attempt": { // Configuración para la protección contra ataques de fuerza bruta en el login
        "max": 5,                       // Número máximo de intentos fallidos de login antes de bloquear.
        "time": 120                     // Tiempo en segundos que se bloquea al usuario tras superar los intentos (2 minutos).
    },
    "mail": { // Configuración para el envío de correos electrónicos (usando Symfony Mailer)
        "host": "mail.server.cl",       // Servidor SMTP.
        "port": 25,                     // Puerto del servidor SMTP (comunes: 25, 465, 587).
        "transport": "smtp",            // Protocolo de transporte (ej. "smtp").
        "secure": "tls",                // Tipo de encriptación (null, "tls", "ssl").
        "name_from": "WYS Soluciones Informáticas", // Nombre del remitente por defecto.
        "username": "mail@wys.cl",      // Usuario para la autenticación SMTP.
        "password": "*"                 // Contraseña para la autenticación SMTP.
    },
    "assets": { // Configuración de las rutas para los assets (CSS, JS, imágenes, etc.)
        "dashboard": { // Ejemplo de un grupo de assets llamado "dashboard"
            "js": {
                "src": "src/dashboard/js",    // Directorio fuente de los archivos JavaScript.
                "dist": "public/dashboard/js" // Directorio de destino para los JS compilados/públicos.
            },
            "css": {
                "src": "src/dashboard/css",   // Directorio fuente de los archivos CSS.
                "dist": "public/dashboard/css"// Directorio de destino para los CSS compilados/públicos.
            },
            "img": {
                "dist": "public/dashboard/img" // Directorio público para las imágenes.
            },
            "store": {
                "dist": "public/dashboard/store" // Directorio público para otros archivos (ej. subidas).
            }
        }
        // Puedes añadir más grupos de assets aquí, por ejemplo, para un frontend de cliente:
        // "frontend": { ... }
    }
}
```

## Secciones Principales

A continuación, se describe cada una de las claves de primer nivel del archivo `config.json`.

### `framework` (string)

-   **Descripción:** Indica la versión actual del framework versaWYS-PHP.
-   **Ejemplo:** `"versaWYS-PHP 0.9.8"`

### `DB` (object)

-   **Descripción:** Contiene los parámetros para la conexión a la base de datos principal.
-   **Parámetros:**
    -   `DB_HOST`: (string) Host de la base de datos (ej. `"localhost"`, `"127.0.0.1"`).
    -   `DB_USER`: (string) Usuario para la conexión a la base de datos.
    -   `DB_PASS`: (string) Contraseña del usuario de la base de datos.
    -   `DB_NAME`: (string) Nombre de la base de datos a utilizar.

### `twig` (object)

-   **Descripción:** Configuración para el motor de plantillas Twig.
-   **Parámetros:**
    -   `cache`: (boolean) Habilita (`true`) o deshabilita (`false`) la caché de plantillas. Se recomienda `false` para desarrollo y `true` para producción para un mejor rendimiento.
    -   `compiled_dir`: (string) Directorio donde se almacenarán las plantillas compiladas si la caché está habilitada (ej. `"./app/templates/.cache"`).
    -   `templates_dir`: (string) Directorio raíz donde se encuentran los archivos de plantillas `.twig` (ej. `"./app/templates"`).
    -   `strict_variables`: (boolean) Si es `true`, Twig lanzará una excepción si se intenta acceder a una variable, atributo o método que no existe. Si es `false`, las variables no definidas se tratan como `null`.
    -   `auto_escape`: (boolean) Controla la estrategia de auto-escapado. Por defecto es `true` (escapa HTML), lo cual es una práctica de seguridad recomendada para prevenir ataques XSS.
    -   `default_template_404`: (string) Ruta a la plantilla Twig (relativa a `templates_dir`) que se mostrará por defecto para errores HTTP 404 (Not Found) si no se especifica otra (ej. `"versaWYS/404"`).

### `build` (object)

-   **Descripción:** Configuraciones generales de la aplicación.
-   **Parámetros:**
    -   `debug`: (boolean) Activa (`true`) o desactiva (`false`) el modo debug. En modo debug, se suelen mostrar errores más detallados. Idealmente `true` para desarrollo y `false` para producción.
    -   `name`: (string) Nombre de la aplicación o empresa (ej. `"WYS Soluciones Informáticas"`).
    -   `version`: (string) Versión actual de tu aplicación (ej. `"1.0.0"`).
    -   `timezone`: (string) Define la zona horaria por defecto para la aplicación (ej. `"America/Santiago"`, `"UTC"`). Ver [zonas horarias soportadas por PHP](https://www.php.net/manual/es/timezones.php).
    -   `charset`: (string) Juego de caracteres por defecto para la aplicación (ej. `"utf-8"`).

### `session` (object)

-   **Descripción:** Configuración para el manejo de sesiones PHP.
-   **Parámetros:**
    -   `key`: (string) Nombre de la cookie que almacenará el ID de sesión (ej. `"WYS"`).
    -   `lifetime`: (integer) Tiempo de vida de la sesión en segundos (ej. `3600` para 1 hora). Después de este tiempo de inactividad, la sesión expira.
    -   `user_cookie` (object): Configuración específica para la cookie de "recordar usuario" (login persistente).
        -   `domain`: (string) Dominio para el cual la cookie es válida (ej. `"localhost"`, `".tudominio.com"`).
        -   `enable`: (boolean) Habilita (`true`) o deshabilita (`false`) la funcionalidad de "recordar usuario".
        -   `key_encript`: (string) **¡IMPORTANTE!** Clave secreta utilizada para encriptar y desencriptar el contenido de la cookie de "recordar usuario". **Debe ser una cadena larga, aleatoria y secreta. ¡Cámbiala por una propia y mantenla segura!**
        -   `lifetime`: (integer) Tiempo de vida de la cookie si no se marca "recordar" (en segundos).
        -   `lifetime_remember`: (integer) Tiempo de vida de la cookie si se marca "recordar" (en segundos, ej. `2592000` para 30 días).
        -   `secure`: (boolean) Si es `true`, la cookie solo se enviará a través de conexiones HTTPS. Recomendado `true` para producción.
        -   `http_only`: (boolean) Si es `true`, la cookie no será accesible mediante JavaScript, ayudando a mitigar ataques XSS. **Recomendado `true` para mayor seguridad, aunque el valor por defecto en el ejemplo sea `false` por flexibilidad.**

### `api` (object)

-   **Descripción:** Configuraciones relacionadas con la API RESTful.
-   **Parámetros:**
    -   `auth`: (boolean) Indica si las rutas de la API requieren autenticación por defecto. Esto puede ser sobrescrito a nivel de ruta o grupo de rutas.

### `login_attempt` (object)

-   **Descripción:** Configuración para la protección contra ataques de fuerza bruta en el formulario de inicio de sesión.
-   **Parámetros:**
    -   `max`: (integer) Número máximo de intentos fallidos de login permitidos desde una misma IP (o para un mismo usuario, dependiendo de la implementación) antes de aplicar un bloqueo temporal.
    -   `time`: (integer) Tiempo en segundos que durará el bloqueo después de superar el número máximo de intentos fallidos (ej. `120` para 2 minutos).

### `mail` (object)

-   **Descripción:** Configuración para el envío de correos electrónicos, usualmente utilizando Symfony Mailer.
-   **Parámetros:**
    -   `host`: (string) Dirección del servidor SMTP (ej. `"smtp.example.com"`).
    -   `port`: (integer) Puerto del servidor SMTP (comunes: `25`, `465` para SSL, `587` para TLS).
    -   `transport`: (string) Protocolo de transporte (ej. `"smtp"`).
    -   `secure`: (string/null) Tipo de encriptación a usar: `null` (sin encriptación), `"tls"`, o `"ssl"`.
    -   `name_from`: (string) Nombre del remitente que aparecerá por defecto en los correos enviados.
    -   `username`: (string) Nombre de usuario para la autenticación con el servidor SMTP.
    -   `password`: (string) Contraseña para la autenticación con el servidor SMTP.

### `assets` (object)

-   **Descripción:** Define grupos de assets (como 'dashboard', 'landing_page', etc.) para organizar archivos CSS, JavaScript, imágenes, y otros recursos estáticos. Esta configuración es utilizada principalmente por los scripts de construcción (ej. `npm run build:[nombre_del_grupo]`) para procesar y ubicar los assets en sus directorios de destino públicos.
-   **Estructura:**
    -   Cada clave de primer nivel bajo `assets` representa un **grupo de assets** (ej. `dashboard`). Puedes nombrar estos grupos según las secciones de tu aplicación.
    -   Dentro de cada grupo, puedes definir diferentes **tipos de assets**. Los más comunes son `js` (JavaScript) y `css` (hojas de estilo), pero también puedes incluir `img` (imágenes), `store` (para archivos varios como fuentes, JSONs de datos, etc.), u otros que necesites.
        -   Para tipos como `js` y `css`, que a menudo requieren compilación o procesamiento (ej. minificación, concatenación), se suelen especificar dos rutas:
            -   `src`: (string) La ruta al directorio fuente de estos archivos (ej. `src/dashboard/js`).
            -   `dist`: (string) La ruta al directorio de destino donde se colocarán los archivos procesados y listos para producción (ej. `public/dashboard/js`).
        -   Para otros tipos como `img` o `store`, que generalmente solo necesitan ser copiados al directorio público, usualmente solo se define la ruta `dist`:
            -   `dist`: (string) La ruta al directorio de destino (ej. `public/dashboard/img`).
    -   **Ejemplo para un grupo `dashboard`:**
        ```json
        "dashboard": {
            "js": {
                "src": "src/dashboard/js",
                "dist": "public/dashboard/js"
            },
            "css": {
                "src": "src/dashboard/css",
                "dist": "public/dashboard/css"
            },
            "img": {
                "dist": "public/dashboard/img"
            },
            "store": {
                "dist": "public/dashboard/store"
            }
        }
        ```

## Uso de la Configuración en la Aplicación

Los valores definidos en `config.json` son cargados por el framework al inicio y están disponibles de las siguientes maneras:

-   **En PHP**: El archivo `versaWYS/kernel/config/config.php` carga el JSON y lo convierte en un array asociativo PHP. Este array está disponible globalmente como la variable `$config`. Por ejemplo, para obtener el host de la base de datos, se usaría `$config['DB']['DB_HOST']`.

-   **En Plantillas Twig**: El objeto de configuración completo también se pasa a Twig como una variable global llamada `config`. Por lo tanto, dentro de una plantilla Twig, puedes acceder a los valores usando la sintaxis de Twig. Por ejemplo: `{{ config.DB.DB_HOST }}` para el host de la base de datos, o `{{ config.build.name }}` para el nombre de la aplicación.

-   **Desde la Línea de Comandos (CLI)**: La utilidad `ConfigManager` (`versaWYS/kernel/cli/ConfigManager.php`) también proporciona métodos para leer (y en algunos casos modificar) la configuración, como `ConfigManager::getConfig()`.

Para más detalles sobre cómo acceder a la configuración desde PHP, Twig o la CLI, revisa la [Guía de Configuración](./CONFIGURATION.md) y la [Guía de Línea de Comandos (CLI)](./Guia_versaCLI.md).

## ¡Importante! Consideraciones de Seguridad

El archivo `config.json` puede contener información sensible, como credenciales de base de datos, claves de API, etc.

**NUNCA debes versionar (subir a Git, SVN, etc.) este archivo con información sensible real en un repositorio público o compartido.**

Prácticas recomendadas:

1.  **`.gitignore`**: Asegúrate de que la ruta exacta a tu archivo `config.json` (ej. `versaWYS/kernel/config/config.json`) esté listada en tu archivo `.gitignore` para prevenir que sea rastreado por Git.
2.  **Archivo de Ejemplo**: Mantén una copia del archivo `config.json` con valores de ejemplo o placeholders en tu repositorio (ej. `config.example.json` o `config.dist.json`). Los desarrolladores pueden copiar este archivo a `config.json` (que estará ignorado) y rellenar sus propios valores.
3.  **Variables de Entorno (Recomendado para Producción)**: Para entornos de producción, es una práctica mucho más segura cargar configuraciones sensibles desde variables de entorno del servidor en lugar de un archivo versionado. El framework podría necesitar adaptarse para leer estas variables y usarlas como override o fuente principal para ciertos parámetros.
4.  **Acceso Restringido**: Asegúrate de que el archivo `config.json` en el servidor de producción no sea accesible directamente desde la web y tenga permisos de archivo restrictivos.

¿Dudas? Consulta la [Guía de Configuración](./CONFIGURATION.md), la [Guía de Línea de Comandos (CLI)](./Guia_versaCLI.md) o abre un issue en el repositorio.

Proteger esta información es vital para la seguridad de tu aplicación.
