# versaWYS-PHP 0.9.8 - Un framework PHP versátil, simple, ligero, rápido y fácil de usar.

## Descripción

versaWYS-PHP es un framework PHP diseñado para desarrolladores que buscan eficiencia y flexibilidad en sus proyectos web. Ofrece un enfoque moderno para la gestión de rutas, tanto para aplicaciones web tradicionales como para APIs REST, integrando herramientas poderosas como RedBean para ORM y Twig para la gestión de plantillas.

## Características

versaWYS-PHP ofrece las siguientes características:

- **Gestión de Rutas:** Manejo avanzado de rutas para aplicaciones web y APIs REST.
- **Soporte para Verbos HTTP:** Compatibilidad completa con los métodos GET, POST, PUT, PATCH y DELETE, facilitando la creación de APIs robustas.
  - Puedes enviar directamente usando fetch, axios, ajax, etc.
  - Puedes enviar usando un formulario.
    - Para utilizar un formulario debes agregar el atributo `method="POST"` y `action="url"` en el formulario.
    - Para enviar un formulario usando el método PUT, PATCH o DELETE debes agregar el campo oculto `<input type="hidden" name="_method" value="PUT">` en el formulario o usar la función `{{ method_field('PUT') | raw }}` desde Twig.
  - Soporte para Parámetros de Ruta: Puedes definir parámetros de ruta para crear rutas dinámicas, por ejemplo, `/ruta/publicada/{slug}`.
  - Soporte para Parámetros de Consulta: Puedes definir parámetros de consulta para crear rutas dinámicas, por ejemplo, `/ruta/publicada?slug={slug}`.
- **ORM con RedBean:** Simplifica las operaciones de base de datos con el elegante ORM RedBean.
- **Gestión de Plantillas con Twig:** Renderiza vistas HTML de manera eficiente y segura con Twig.
- **Respuestas en JSON y HTML:** Flexibilidad para responder tanto en JSON como en HTML.
- **Soporte para Datos Form-Data y Archivos:** Manejo eficiente de formularios y carga de archivos.
- **CLI para Generar Código Rápidamente:** Genera esqueletos de código automáticamente.
  - `php versaCLI`
  - Serve - Config - Controller - Models - Middleware - Migrations - Routes
- **Manejo de Sesiones y Cookies:** Gestión de sesiones y cookies desde el núcleo del framework.
- **Envío de Correos Electrónicos:** Envío de correos electrónicos con Symfony\Mailer, permitiendo adjuntar archivos, embebido de imágenes, formato de correo en texto, HTML templating o usar una plantilla Twig.
- **Configuración de Rutas de Imágenes o Archivos Públicos:** Configura las rutas desde el archivo de configuración `config.json`, en el apartado `assets`.
  - Desde la plantilla Twig utiliza la función `{{ getAssets('dashboard', 'img', '/404.svg') }}`, que retorna la ruta absoluta de la imagen.

## Seguridad

versaWYS-PHP ofrece las siguientes características de seguridad:

- **Protección contra Ataques XSS:** Protección contra ataques XSS con el método `escape()` de Twig.
- **Protección contra Ataques CSRF:** Protección contra ataques CSRF desde el núcleo del framework.
  - Puedes usar el método `{{ csrf_field() | raw }}` de Twig para generar un token.
- **Protección contra Ataques SQL Injection:** Protección contra ataques SQL Injection con el ORM RedBean.
- **Protección contra Ataques de Fuerza Bruta:** Protección contra ataques de fuerza bruta desde Middleware y control de intentos.

## Documentación de Rutas

Puedes revisar la documentación de las rutas directamente desde la web, ingresando a `http://localhost/doc`.

### Visualización de Rutas

La documentación de las rutas ahora se puede visualizar de manera interactiva. Para acceder a esta funcionalidad:

1. Navega a `http://localhost/doc`.
2. Verás una lista de archivos de rutas disponibles.
3. Haz clic en cualquier archivo de ruta para ver las rutas definidas en ese archivo.
4. Puedes buscar rutas utilizando el campo de búsqueda proporcionado.
5. Al hacer clic en una ruta específica, se desplegará información detallada sobre la ruta, incluyendo el método HTTP, la URL, la clase y el método asociado, los parámetros de consulta, el cuerpo de la solicitud y los middlewares aplicados.

Esta funcionalidad facilita la comprensión y el mantenimiento de las rutas definidas en tu aplicación.

## Requisitos

- PHP 8.3 o superior (apt install php-intl)
- Composer
- MySQL 5.7 o superior
- Apache 2.4 o superior (también puedes usar NGINX)
- Módulo Apache mod_rewrite
- Node 20 o superior

## Inicio Rápido

Para comenzar con `versaWYS-PHP`, sigue estos pasos:

1. Abre una terminal en la carpeta donde guardarás el proyecto.
2. Clona el repositorio: `git clone https://github.com/kriollo/solfonpro-tqw.git .` o descarga el zip `https://github.com/kriollo/solfonpro-tqw/archive/refs/heads/main.zip`.
3. Instala las dependencias: `cd versaWYS && composer install`.
4. Configura tu base de datos, abre el editor de código y actualiza `versaWYS/kernel/config/config.json` con tus credenciales.
5. En la terminal, ejecuta `php versaCLI migrate:up` para iniciar tu base de datos.
6. Instala las dependencias para la compilación de los archivos JS y CSS.
   - Ejecuta: `node --run versaCompileJS` para generar archivos JS compilados.
   - Ejecuta: `node --run versaCompileCSS` para generar el archivo CSS del proyecto.
7. Inicia el servidor de desarrollo: `php versaCLI serve`.
   - Credenciales por defecto:
     - Usuario: admin@wys.cl
     - Contraseña: admin2023

Ahora puedes acceder a tu nueva aplicación `versaWYS-PHP` en `http://localhost:8000`.

## Minificación y Ofuscación de Archivos CSS y JS en Tiempo de Desarrollo

- Abre una terminal y sigue estos pasos: (si usas VSCODE puedes ejecutar la tarea que está configurada)

- Para compilar los archivos CSS:
  - `node --run dashboard`

- Para compilar los archivos JS:
  - `node --run watch`

## Archivo de Configuración `versaWYS\kernel\config\config.json`

```json
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
        "compiled_dir": "./app/templates/.cache",
        "templates_dir": "./app/templates",
        "strict_variables": true,
        "auto_escape": true,
        "default_template_404": "versaWYS/404"
    },
    "build": {
        "debug": true,
        "name": "WYS Soluciones Informáticas",
        "version": "1.0.0",
        "timezone": "America/Santiago",
        "charset": "utf-8"
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
        "name_from": "WYS Soluciones Informáticas",
        "username": "mail@wys.cl",
        "password": "*"
    },
    "assets": {
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
    }
}
```

## Licencia

versaWYS-PHP es un software de código abierto bajo licencia MIT.
