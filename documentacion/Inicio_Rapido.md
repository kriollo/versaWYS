# versaWYS-PHP 0.9.8 - Guía Principal

Bienvenido a la documentación de versaWYS-PHP, un framework PHP versátil, simple, ligero, rápido y fácil de usar.
Esta guía te proporcionará una visión completa del framework, desde sus características principales hasta cómo ponerlo en marcha.

## Tabla de Contenidos

- [Visión General](#visión-general)
- [Características Destacadas](#características-destacadas)
  - [Arquitectura MVC Moderna](#arquitectura-mvc-moderna)
  - [Sistema de Enrutamiento Avanzado](#sistema-de-enrutamiento-avanzado)
  - [ORM Integrado con RedBeanPHP](#orm-integrado-con-redbeanphp)
  - [Motor de Plantillas Twig](#motor-de-plantillas-twig)
  - [Respuestas Flexibles](#respuestas-flexibles)
  - [Manejo de Peticiones y Respuestas](#manejo-de-peticiones-y-respuestas)
  - [Soporte para `form-data` y Carga de Archivos](#soporte-para-form-data-y-carga-de-archivos)
  - [Interfaz de Línea de Comandos (CLI) - `versaCLI`](#interfaz-de-línea-de-comandos-cli---versacli)
  - [Gestión de Sesiones y Cookies](#gestión-de-sesiones-y-cookies)
  - [Envío de Correos Electrónicos](#envío-de-correos-electrónicos)
  - [Configuración Centralizada](#configuración-centralizada)
  - [Gestión de Assets](#gestión-de-assets)
  - [Seguridad Integrada](#seguridad-integrada)
  - [Documentación de Rutas Interactiva](#documentación-de-rutas-interactiva)
- [Requisitos del Sistema](#requisitos-del-sistema)
- [Primeros Pasos](#primeros-pasos)
- [Guías Detalladas](#guías-detalladas)
- [Compilación de Assets (CSS/JS)](#compilación-de-assets-cssjs)
- [Licencia](#licencia)

## Visión General

versaWYS-PHP es un framework PHP MVC (Modelo-Vista-Controlador) moderno y ligero, diseñado para desarrolladores que buscan una base sólida y eficiente para construir aplicaciones web y APIs RESTful. Su filosofía se centra en la simplicidad, la velocidad y la facilidad de uso, permitiendo un desarrollo ágil sin sacrificar la potencia ni la flexibilidad.

Inspirado en la necesidad de un arranque rápido de proyectos y una curva de aprendizaje suave, versaWYS-PHP integra componentes robustos y populares de la comunidad PHP, como RedBeanPHP para un ORM fluido y Twig para un sistema de plantillas elegante y seguro.

## Características Destacadas

versaWYS-PHP se destaca por un conjunto de características diseñadas para optimizar el flujo de trabajo del desarrollador y mejorar la calidad de las aplicaciones:

### Arquitectura MVC Moderna

Organización clara del código en Modelos, Vistas y Controladores, promoviendo la separación de responsabilidades y la mantenibilidad.

### Sistema de Enrutamiento Avanzado

-   **Rutas Amigables (Pretty URLs):** Soporte para URLs limpias y semánticas.
-   **Soporte Completo de Verbos HTTP:** Manejo nativo de GET, POST, PUT, PATCH, y DELETE, esencial para construir APIs RESTful conformes a los estándares.
-   **Parámetros de Ruta Dinámicos:** Define rutas con segmentos variables (ej. `/usuarios/{id}`).
-   **Parámetros de Consulta (Query String):** Fácil acceso a los parámetros enviados en la URL (ej. `/buscar?termino=php`).
-   **Agrupación de Rutas y Middlewares:** Organiza rutas en grupos y aplica middlewares específicos a ellos para la gestión de autenticación, autorización, logging, etc.
-   **Envío de Datos:** Flexibilidad para recibir datos vía `fetch`, `axios`, `AJAX` o formularios HTML tradicionales.
    -   Para formularios, se utiliza el atributo `method="POST"` y `action="url"`.
    -   Soporte para simular verbos PUT, PATCH, DELETE mediante un campo oculto `_method` o la función `{{ method_field('VERBO') | raw }}` en Twig.

*(Para una guía más profunda sobre el enrutamiento, consulta [Enrutamiento.md](./Enrutamiento.md))*

### ORM Integrado con RedBeanPHP

-   **"On-the-fly" ORM:** Crea y modifica esquemas de base de datos automáticamente según sea necesario, ideal para prototipado rápido y desarrollo ágil.
-   **Sintaxis Fluida y Sencilla:** Interactúa con tu base de datos de forma intuitiva.

*(Aprende más sobre el ORM en [ORM_y_BaseDeDatos.md](./ORM_y_BaseDeDatos.md))*

### Motor de Plantillas Twig

-   **Sintaxis Concisa y Potente:** Crea plantillas complejas con facilidad.
-   **Seguridad:** Auto-escapado por defecto para prevenir ataques XSS.
-   **Herencia de Plantillas, Macros e Inclusión:** Reutiliza código HTML de manera eficiente.

### Respuestas Flexibles

Capacidad para generar respuestas en formato JSON (para APIs) o HTML (para aplicaciones web) de forma sencilla.

### Manejo de Peticiones (Requests) y Respuestas (Responses)

Objetos dedicados para gestionar la información entrante y saliente, facilitando el acceso a cabeceras, cuerpo de la petición, cookies, etc.

### Soporte para `form-data` y Carga de Archivos

Gestión integrada y simplificada de la subida de archivos.

### Interfaz de Línea de Comandos (CLI) - `versaCLI`

-   **Generación de Código:** Crea rápidamente controladores, modelos, middlewares, migraciones y archivos de rutas.
-   **Servidor de Desarrollo Incorporado:** Inicia un servidor PHP local con `php versaCLI serve`.
-   **Gestión de Migraciones:** Controla la evolución del esquema de tu base de datos (`migrate:up`, `migrate:down`, `migrate:create`).

*(Consulta la [Interfaz de Línea de Comandos (CLI)](./LineaDeComandos.md) para más detalles)*

### Gestión de Sesiones y Cookies

-   Mecanismos integrados para manejar sesiones de usuario y cookies de forma segura.
-   Configuración detallada para cookies de "recordar usuario" con encriptación.

### Envío de Correos Electrónicos

-   **Múltiples Transportes:** SMTP, Sendmail, etc.
-   **Funcionalidades Avanzadas:** Adjuntar archivos, embeber imágenes, correos en HTML o texto plano, y uso de plantillas Twig para el cuerpo del correo.

### Configuración Centralizada

-   Archivo `config.json` para gestionar todos los parámetros de la aplicación (base de datos, Twig, sesión, email, assets, etc.).
-   Fácil acceso a la configuración desde cualquier parte de la aplicación.
-   Consulta la [Guía de Configuración Detallada](./CONFIGURATION.md).

### Gestión de Assets

-   Define rutas y directorios para tus assets en `config.json`.
-   Helper en Twig `{{ getAssets('grupo', 'tipo', '/archivo.ext') }}` para generar URLs absolutas a los assets.

### Seguridad Integrada

versaWYS-PHP incluye varias medidas para ayudar a proteger tus aplicaciones:

-   **Protección XSS (Cross-Site Scripting):** Gracias al auto-escapado de Twig y funciones de sanitización.
-   **Protección CSRF (Cross-Site Request Forgery):**
    -   Generación y validación de tokens CSRF automáticamente para formularios.
    -   Helper `{{ csrf_field() | raw }}` en Twig para incluir el token en los formularios.
-   **Prevención de Inyección SQL:** El uso de RedBeanPHP como ORM ayuda a mitigar este tipo de ataques al utilizar consultas parametrizadas o mecanismos de escape.
-   **Protección contra Fuerza Bruta en Logins:** Sistema de control de intentos de inicio de sesión fallidos configurable.

*(Aprende más sobre las características de seguridad en [Seguridad.md](./Seguridad.md))*

### Documentación de Rutas Interactiva

Una característica útil para el desarrollo y la depuración es la documentación de rutas generada automáticamente:

1.  Accede a `http://localhost/doc` en tu navegador (o la URL base de tu proyecto + `/doc`).
2.  Visualiza una lista de todos los archivos de rutas de tu aplicación.
3.  Al seleccionar un archivo, se mostrarán todas las rutas definidas en él.
4.  Utiliza el buscador para filtrar rutas específicas.
5.  Haz clic en una ruta para ver detalles como: método HTTP, URL, controlador y método asociado, parámetros de consulta esperados, estructura del cuerpo de la solicitud (si aplica) y middlewares asignados.

*(Detalles en [RutasInteractivas.md](./RutasInteractivas.md))*

## Requisitos del Sistema

-   PHP 8.3 o superior (con la extensión `php-intl` habilitada: `apt install php-intl` o similar).
-   Composer (gestor de dependencias para PHP).
-   MySQL 5.7 o superior (o una base de datos compatible como MariaDB).
-   Servidor Web: Apache 2.4+ (con `mod_rewrite` habilitado) o NGINX.
-   Node.js 20 o superior (para la compilación de assets frontend como CSS/JS).

## Primeros Pasos

Para poner en marcha tu primer proyecto con `versaWYS-PHP`, sigue nuestra [Guía de Instalación Detallada](./INSTALL.md).

Una vez instalado, familiarízate con la [Guía de Configuración](./CONFIGURATION.md) para adaptar el framework a tus necesidades.

## Guías Detalladas

A medida que profundices en versaWYS-PHP, las siguientes guías te serán de gran utilidad:

-   [Enrutamiento](./Enrutamiento.md)
-   [ORM y Base de Datos (RedBeanPHP)](./ORM_y_BaseDeDatos.md)
-   [Interfaz de Línea de Comandos (CLI)](./LineaDeComandos.md)
-   [Seguridad del Framework](./Seguridad.md)
-   [Documentación de Rutas Interactiva](./RutasInteractivas.md)
-   [Estructura de Directorios](./EstructuraDeDirectorios.md)
-   *(Más guías se añadirán aquí)*

## Compilación de Assets (CSS/JS)

Para instrucciones sobre cómo compilar tus archivos CSS (ej. TailwindCSS) y JavaScript, consulta la sección correspondiente en la [Guía de Instalación Detallada](./INSTALL.md#minificación-y-ofuscación-de-archivos-css-y-js).

## Archivo de Configuración

Para una descripción detallada de todas las opciones disponibles en el archivo `versaWYS/kernel/config/config.json`, por favor revisa nuestra [Guía de Configuración](./CONFIGURATION.md).

## Licencia

versaWYS-PHP es un software de código abierto licenciado bajo la [Licencia MIT](https://opensource.org/licenses/MIT).
