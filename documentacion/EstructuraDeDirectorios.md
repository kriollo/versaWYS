# Estructura de Directorios de versaWYS-PHP

Comprender la estructura de directorios de un framework es fundamental para trabajar eficientemente con él. A continuación, se detalla la organización de las carpetas y archivos principales en un proyecto versaWYS-PHP y el propósito de cada uno.

## Directorios de Nivel Superior

Estos son los directorios principales que encontrarás en la raíz de un proyecto versaWYS-PHP:

* **`index.php`**: Este es el archivo de entrada de la aplicación. Es el único punto de entrada para todas las solicitudes HTTP a tu aplicación (front controller).

*   **`app/`**: Este es el corazón de tu aplicación. Contiene la lógica específica de tu proyecto, incluyendo modelos, vistas, controladores, rutas, middleware, migraciones y seeders. La mayoría del trabajo de desarrollo se realiza dentro de esta carpeta.

*   **`public/`**: Esta carpeta contiene todos los assets y archivos de la interfaz de usuario, como imágenes, hojas de estilo CSS compiladas, y archivos JavaScript del lado del cliente. Es el único directorio que debería ser accesible directamente desde el navegador web.

*   **`versaWYS/`**: Contiene el código fuente del núcleo del framework versaWYS-PHP. Generalmente, no necesitarás modificar archivos dentro de esta carpeta a menos que estés contribuyendo al desarrollo del propio framework. Incluye subdirectorios como `kernel/` que aloja los componentes centrales del framework y `vendor/` que es gestionada por Composer, el manejador de dependencias para PHP. Contiene todas las bibliotecas y paquetes de terceros de los que depende tu proyecto y el framework versaWYS-PHP. No debes modificar directamente los archivos en esta carpeta, ya que se sobrescribirán con las actualizaciones de Composer.

*   **`documentacion/`**: Aquí es donde reside toda la documentación del proyecto, incluyendo la que estás leyendo ahora. Contiene archivos Markdown que explican cómo usar el framework, su API, y otros aspectos relevantes.

*   **`versaCLI`**: (Archivo) Es el script ejecutable para la interfaz de línea de comandos de versaWYS-PHP. Se utiliza para tareas como generar código, ejecutar migraciones, etc.


## Detalle del Directorio `app/`

El directorio `app/` es donde reside la mayor parte de tu código de aplicación. Su estructura típica es la siguiente:

*   **`.htaccess`**: (Archivo) Un archivo de configuración específico para el servidor web Apache. Puede contener directivas para controlar el acceso al directorio `app/` o reglas de reescritura, aunque la reescritura principal generalmente se maneja en `public/.htaccess`.

*   **`controllers/`**: Contiene las clases de tus controladores. Los controladores son responsables de recibir las solicitudes, interactuar con los modelos para obtener o modificar datos, y luego pasar esos datos a las plantillas para generar la respuesta HTTP.
    *   Ejemplo: `UsuarioController.php`, `ProductoController.php`.

*   **`middleware/`**: Almacena las clases de middleware. El middleware proporciona un mecanismo para filtrar las solicitudes HTTP que ingresan a tu aplicación. Cada capa de middleware puede inspeccionar o modificar la solicitud antes de que llegue al controlador, o la respuesta antes de que se envíe al cliente.
    *   Ejemplo: `AuthMiddleware.php`, `AdminMiddleware.php`.

*   **`migrations/`**: Aquí se guardan todos los archivos de migración de la base de datos. Cada archivo de migración contiene métodos para aplicar (`up`) y revertir (`down`) cambios en el esquema de tu base de datos, permitiendo un control de versiones del mismo.
    *   Ejemplo: `2023_10_27_100000_create_users_table.php`.

*   **`models/`**: Contiene las clases de tus modelos. Los modelos representan la lógica de negocio y la interacción con la base de datos. En versaWYS-PHP, estos modelos a menudo extienden o interactúan con las funcionalidades ORM proporcionadas (por ejemplo, RedBeanPHP).
    *   Ejemplo: `Usuario.php`, `Producto.php`.

*   **`routes/`**: En esta carpeta se definen los archivos que contienen las rutas de tu aplicación. Estos archivos mapean las URIs (endpoints) y los métodos HTTP (GET, POST, etc.) a acciones específicas de los controladores.
    *   Ejemplo: `web.php`, `api.php`, o archivos más específicos como `usuariosRoutes.php`.

*   **`seeders/`**: Contiene las clases de seeders. Los seeders se utilizan para poblar la base de datos con datos iniciales o de prueba, lo cual es útil para el desarrollo y las pruebas.
    *   Ejemplo: `DatabaseSeeder.php`, `UsuariosTableSeeder.php`.

*   **`templates/`** (a veces llamado `views/`): Este directorio almacena las plantillas de presentación de tu aplicación. En versaWYS-PHP, es común encontrar aquí archivos de plantillas Twig (`.twig`). Estas plantillas son responsables de generar el HTML que se envía al navegador.
    *   Ejemplo: `layout.twig`, `home.twig`, `productos/lista.twig`.

---

Esta estructura promueve una organización clara del código y facilita la localización de los diferentes componentes de la aplicación.
