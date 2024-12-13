# versaWYS-PHP - Un framework PHP versatil, simple, ligero, rápido y fácil de usar.

## Descripción

versaWYS-PHP es un framework PHP versátil, simple, ligero, rápido y fácil de usar, diseñado para desarrolladores que buscan eficiencia y flexibilidad en sus proyectos web. Ofrece un enfoque moderno para la gestión de rutas, tanto para aplicaciones web tradicionales como para APIs REST, integrando poderosas herramientas como RedBean para ORM y Twig para la gestión de plantillas.

## Características

versaWYS-PHP ofrece las siguientes características:

-   Gestión de Rutas: Manejo avanzado de rutas para aplicaciones web y APIs REST.
-   Soporte para Verbos HTTP: Completa compatibilidad con los métodos GET, POST, PUT, PATCH, DELETE, facilitando la creación de APIs robustas.

    -   Puedes enviar directamente usando fecth, axios, ajax, etc.
    -   Puedes enviar usando un formulario.
        -   para utilizar un formulario debes agregar el atributo `method="POST"` y `action="url"` en el formulario.
        -   para enviar un formulario usando el método PUT, PATCH o DELETE debes agregar el campo oculto `<input type="hidden" name="_method" value="PUT">` en el formulario o usar function `{{ method_field('PUT') | raw }}` desde twig.
    -   Soporte para Parámetros de Ruta: Puedes definir parámetros de ruta para crear rutas dinámicas. `/ruta/publicada/{slug}`.
    -   Soporte para Parámetros de Consulta: Puedes definir parámetros de consulta para crear rutas dinámicas. `/ruta/publicada?slug={slug}`.

-   ORM con RedBean: Simplifica las operaciones de base de datos con el elegante ORM RedBean.
-   Gestión de Plantillas con Twig: Renderiza vistas HTML de manera eficiente y segura con Twig.
-   Respuestas en JSON y HTML: Flexibilidad para responder tanto en JSON como en HTML.
-   Soporte para Datos Form-Data y Archivos: Manejo eficiente de formularios y carga de archivos.
-   CLI para generar rapidamente esqueletos de código automáticamente.
    -   php versaCLI
    -   Serve - Config - Controller - Models - Middleware - Migrations - Routes
-   Manejo de Sesiones y Cookies: Manejo de sesiones y cookies desde el núcleo del framework.
-   Envio de correos electrónicos con sinfony\mailer, puedes adjuntar archivo, embeding de imagenes, formato de correo en texto, html templating o usar una plantilla twig.
-   Puedes configurar las rutas de las imagenes o archivos publicos desde el archivo de configuración `config.json`, en el apartado `assets`.
    -   desde la plantilla Twig utilizas la función `{{ getAssets('dashboard', 'img', '/404.svg') }}`, el cual retorna la ruta absoluta de la imagen

## Seguridad

versaWYS-PHP ofrece las siguientes características de seguridad:

-   Protección contra ataques XSS: Protección contra ataques XSS con el método `escape()` de Twig.
-   Protección contra ataques CSRF: Protección contra ataques CSRF desde el núcleo del framework.
    -   Puedes usar el método `{{ csrf_field() | raw }}` de Twig para generar un token.
-   Protección contra ataques SQL Injection: Protección contra ataques SQL Injection con el ORM RedBean.
-   Protección contra ataques de Fuerza Bruta: Protección contra ataques de Fuerza Bruta desde Middleware y control de Intentos.
-   Politicas de contraseña configurables desde el archivo config

## Requisitos

-   PHP 8.3 o superior (apt install php-intl )
-   Composer
-   MySQL 5.7 o superior
-   Apache 2.4 o superior (también puedes usar NGINX)
-   Modulo Apache mod_rewrite
-   node 23

## Inicio Rápido

Para comenzar con `versaWYS-PHP`, sigue estos pasos:

1. Abre una terminal en la carpeta donde guardarás el proyecto
2. Clona el repositorio: `git clone https://github.com/kriollo/versaWYS.git .` o descargar el zip `https://github.com/kriollo/versaWYS/archive/refs/heads/main.zip`
3. Instala las dependencias: `cd versaWYS && composer install`
4. Configura tu base de datos, abre el Editor de Codigo y actualiza `versaWYS/kernel/config/config.json` con tus credenciales.
5. En la terminal ejecuta `php versaCLI migrate:up` para iniciar tu base de datos.
6. Instala las dependencias para la compiliación de los archivos js y css.
    - ejecuta: `node --run versaCompileJS` para generar archivos JS compilados
    - ejecuta: `node --run versaCompileCSS` para generar archivo CSS del proyecto
7. Inicia el servidor de desarrollo: `php versaCLI serve`
    - Credencial por defecto:
        - usuario : admin@wys.cl
        - Password: admin2023

Ahora puedes acceder a tu nueva aplicación `versaWYS-PHP` en `http://localhost:8000`.

## Minificación y ofuscación de archivos CSS y JS en tiempo de desarrollo

-   Abre una terminal y sigue estos pasos: (si usas VSCODE puedes ejecutar la task que está configurada)

-   Para compiliar los archivos css:

    -   `node --run dashboard`

-   Para compiliar los archivos js

    -   `node --run watch`

## Archivo de Configuración `versaWYS\kernel\config\config.json`

    {
        "framework": "versaWYS-PHP 0.9.5",
        "DB": {
            "DB_HOST": "localhost",
            "DB_USER": "local",
            "DB_PASS": "local",
            "DB_NAME": "versawys"
        },
        "twig": {
            "cache": false,
            "compiled_dir": "./app\/templates\/.cache",
            "templates_dir": "./app\/templates",
            "strict_variables": true,
            "auto_escape": true,
            "default_template_404": "versaWYS\/404"
        },
        "build": {
            "debug": true,
            "name": "WYS Soluciones Inform\u00e1ticas",
            "version": "1.0.0",
            "timezone": "America\/Santiago",
            "charset": "utf-8"
        },
        "auth": {
            "inactive_account_days": 30,
            "expiration_days_password": 90,
            "password_policy": {
                "large": 8,
                "uppercase": true,
                "lowercase": true,
                "number": true,
                "special_chars": true
            }
        },
        "session": {
            "key": "WYS",
            "lifetime": 3600,
            "user_cookie": {
                "domain": "localhost",
                "enable": true,
                "key_encript": "X8f3p@9Vde0WvKauzI8C2",
                "lifetime": 3600,
                "lifetime_remember": 2592000,
                "secure": false,
                "http_only": false
            }
        },
        "api": {
            "auth": false
        },
        "login_attempt": {
            "max": 5,
            "time": 120
        },
        "mail": {
            "host": "mail.server.cl",
            "port": 25,
            "transport": "smtp",
            "secure": "tls",
            "name_from": "WYS Soluciones Inform\u00e1ticas",
            "username": "mail@wys.cl",
            "password": "*"
        },
        "assets": {
            "dashboard": {
                "js": {
                    "src": "src\/dashboard\/js",
                    "dist": "public\/dashboard\/js"
                },
                "css": {
                    "src": "src\/dashboard\/css",
                    "dist": "public\/dashboard\/css"
                },
                "img": {
                    "dist": "public\/dashboard\/img"
                },
                "store": {
                    "dist": "public\/dashboard\/store"
                }
            }
        }
    }

## Licencia

versaWYS-PHP es un software de código abierto bajo licencia MIT.
