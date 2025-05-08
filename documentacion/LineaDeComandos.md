# Interfaz de Línea de Comandos (CLI) de versaWYS-PHP

versaWYS-PHP incluye una potente interfaz de línea de comandos (CLI) para ayudar con tareas comunes de desarrollo como la generación de código (controladores, modelos, etc.), gestión de la base de datos (migraciones, seeders), y otras utilidades.

## Invocación

La CLI se invoca ejecutando el script `versaCLI` ubicado en la raíz del proyecto mediante PHP:

```bash
php versaCLI [comando]
```

Si ejecutas `php versaCLI` sin ningún comando o con un comando no reconocido, se mostrará la pantalla de ayuda principal.

## Ayuda General

Para ver la lista completa de comandos disponibles y su uso, puedes ejecutar:

```bash
php versaCLI help
```
O simplemente:
```bash
php versaCLI
```

Esto mostrará una salida similar a la detallada en las siguientes secciones, describiendo cada módulo de comando y sus acciones específicas.

## Comandos Disponibles

A continuación, se detallan los principales comandos agrupados por funcionalidad.

### Atajos para Creación Rápida

Estos comandos permiten generar rápidamente múltiples archivos relacionados para un nuevo recurso o módulo.

*   **`RCMD:<nombre>`**
    *   **Descripción**: Crea un conjunto de archivos básicos para un nuevo recurso: archivo de Ruta, Controlador, Modelo y Middleware.
    *   **Uso**: `php versaCLI RCMD:NombreDelRecurso`
    *   **Ejemplo**: `php versaCLI RCMD:Producto` crearía `ProductoController.php`, `ProductoModel.php`, `ProductoMiddleware.php` y un archivo de rutas (probablemente `productoRoutes.php`).

*   **`versaMODULE:twig:<nombre>`**
    *   **Descripción**: Crea una estructura de módulo completa utilizando Twig para las plantillas. Esto incluye el archivo de Ruta, Controlador, Modelo, Middleware y una plantilla Twig inicial.
    *   **Uso**: `php versaCLI versaMODULE:twig:NombreDelModulo`
    *   **Ejemplo**: `php versaCLI versaMODULE:twig:GestionUsuarios`

*   **`versaMODULE:vue:<nombre>`**
    *   **Descripción**: Crea una estructura de módulo completa utilizando Vue.js para el frontend. Esto incluye el archivo de Ruta, Controlador, Modelo, Middleware y un componente Vue inicial.
    *   **Uso**: `php versaCLI versaMODULE:vue:NombreDelModulo`
    *   **Ejemplo**: `php versaCLI versaMODULE:vue:PanelDashboard`

### Servidor de Desarrollo

*   **`serve`**
    *   **Descripción**: Inicia el servidor de desarrollo PHP incorporado.
    *   **Uso**: `php versaCLI serve`
    *   Por defecto, el servidor se iniciará en `localhost:8000`.

### Gestión de Configuración (`config`)

Estos comandos permiten modificar ciertos aspectos de la configuración del framework.

*   **`config:debug:[true|false]`**
    *   **Descripción**: Activa o desactiva el modo debug de la aplicación. El modo debug generalmente muestra errores detallados y puede habilitar otras herramientas de desarrollo.
    *   **Uso**: 
        ```bash
        php versaCLI config:debug:true
        php versaCLI config:debug:false
        ```

*   **`config:templateCache:[true|false]`**
    *   **Descripción**: Activa o desactiva la caché para las plantillas Twig. Desactivar la caché es útil durante el desarrollo para ver los cambios en las plantillas inmediatamente, mientras que activarla mejora el rendimiento en producción.
    *   **Uso**:
        ```bash
        php versaCLI config:templateCache:true
        php versaCLI config:templateCache:false
        ```

*   **`config:ClearCache`**
    *   **Descripción**: Limpia la caché de las plantillas Twig compiladas. Es útil si has hecho cambios y quieres asegurar que se regeneren las plantillas, o si sospechas que la caché está causando problemas.
    *   **Uso**: `php versaCLI config:ClearCache`

### Gestión de Base de Datos: Migraciones (`migrate`)

Las migraciones permiten versionar y gestionar los cambios en el esquema de tu base de datos de forma programática. Esto facilita la colaboración en equipo y el despliegue de la aplicación en diferentes entornos.

*   **`migrate:make:<nombre>`**
    *   **Descripción**: Crea un nuevo archivo de migración. El nombre debe ser descriptivo de los cambios que realizará (ej. `CrearTablaUsuarios`, `AñadirColumnaEmailATablaUsuarios`).
    *   **Uso**: `php versaCLI migrate:make:NombreDeLaMigracion`
    *   **Ejemplo**: `php versaCLI migrate:make:CrearTablaProductos`
    *   Esto generará un archivo en el directorio de migraciones (usualmente `app/migrations/`) con una plantilla para los métodos `up()` (aplicar cambios) y `down()` (revertir cambios).

*   **`migrate:up`**
    *   **Descripción**: Ejecuta todas las migraciones pendientes que aún no se han aplicado a la base de datos.
    *   **Uso**: `php versaCLI migrate:up`

*   **`migrate:down:<nombre>`**
    *   **Descripción**: Revierte una migración específica. Debes proporcionar el nombre completo del archivo de migración (incluyendo el timestamp, si lo tiene) o el nombre de la clase de la migración.
    *   **Uso**: `php versaCLI migrate:down:NombreDeLaMigracionAEjecutarDown`
    *   **Nota**: Usar con precaución, ya que revertir migraciones puede implicar pérdida de datos si no se maneja correctamente.

*   **`migrate:rollback`**
    *   **Descripción**: Revierte la última migración que se ejecutó (el último lote de migraciones).
    *   **Uso**: `php versaCLI migrate:rollback`

*   **`migrate:refresh`**
    *   **Descripción**: Revierte todas las migraciones existentes en la base de datos y luego las vuelve a ejecutar todas desde el principio. Es útil para reconstruir el esquema de la base de datos desde cero.
    *   **Uso**: `php versaCLI migrate:refresh`
    *   **Advertencia**: Esto eliminará todos los datos de las tablas gestionadas por las migraciones.

*   **`migrate:refresh:seeder`**
    *   **Descripción**: Similar a `migrate:refresh`, pero después de reconstruir el esquema de la base de datos, también ejecuta todos los seeders para poblar la base de datos con datos iniciales.
    *   **Uso**: `php versaCLI migrate:refresh:seeder`
    *   **Advertencia**: Esto eliminará todos los datos de las tablas gestionadas por las migraciones antes de repoblarla.

### Gestión de Base de Datos: Seeders (`seeder`)

Los seeders se utilizan para poblar tu base de datos con datos iniciales o de prueba. Son especialmente útiles después de ejecutar migraciones para tener un estado consistente de la base de datos.

*   **`seeder:make:<nombre>`**
    *   **Descripción**: Crea un nuevo archivo seeder. El nombre debe ser descriptivo de los datos que va a insertar (ej. `UsuariosSeeder`, `ProductosDefaultSeeder`).
    *   **Uso**: `php versaCLI seeder:make:NombreDelSeeder`
    *   **Ejemplo**: `php versaCLI seeder:make:RolesPermisosSeeder`
    *   Esto generará un archivo en el directorio de seeders (usualmente `app/seeders/`) con una plantilla para el método `run()`, donde definirás la lógica de inserción de datos.

*   **`seeder:runAll`**
    *   **Descripción**: Ejecuta todos los seeders definidos en el proyecto.
    *   **Uso**: `php versaCLI seeder:runAll`

*   **`seeder:run:<nombre>`**
    *   **Descripción**: Ejecuta un seeder específico. Debes proporcionar el nombre de la clase del seeder.
    *   **Uso**: `php versaCLI seeder:run:NombreDelSeeder`
    *   **Ejemplo**: `php versaCLI seeder:run:UsuariosSeeder`

### Generación de Código: Controladores (`controller`)

Los controladores son responsables de manejar las solicitudes entrantes, interactuar con los modelos y seleccionar las vistas adecuadas para enviar una respuesta.

*   **`controller:make:<nombre>`**
    *   **Descripción**: Crea un nuevo archivo de controlador en el directorio `app/controllers/`. No es necesario agregar el sufijo `Controller` al nombre, ya que la CLI lo hará automáticamente.
    *   **Uso**: `php versaCLI controller:make:NombreDelControlador`
    *   **Ejemplo**: `php versaCLI controller:make:Usuario` creará `UsuarioController.php`.

*   **`controller:delete:<nombre>`**
    *   **Descripción**: Elimina un archivo de controlador existente. No es necesario agregar el sufijo `Controller`.
    *   **Uso**: `php versaCLI controller:delete:NombreDelControlador`
    *   **Ejemplo**: `php versaCLI controller:delete:Usuario` eliminará `UsuarioController.php`.

### Generación de Código: Archivos de Rutas (`route`)

Los archivos de rutas definen los endpoints de tu aplicación y los asocian con los métodos de los controladores correspondientes.

*   **`route:make:<nombre>`**
    *   **Descripción**: Crea un nuevo archivo de definición de rutas en el directorio `app/routes/`. Es una buena práctica que el nombre termine en `Routes` (ej. `usuarioRoutes`).
    *   **Uso**: `php versaCLI route:make:NombreDelArchivoDeRutas`
    *   **Ejemplo**: `php versaCLI route:make:productoRoutes` creará `productoRoutes.php`.

*   **`route:delete:<nombre>`**
    *   **Descripción**: Elimina un archivo de definición de rutas.
    *   **Uso**: `php versaCLI route:delete:NombreDelArchivoDeRutas`
    *   **Ejemplo**: `php versaCLI route:delete:productoRoutes` eliminará `productoRoutes.php`.

*   **`route:list`**
    *   **Descripción**: Muestra todas las rutas registradas en la aplicación. Puede que necesite que selecciones un archivo de rutas específico si están modularizadas.
    *   **Uso**: `php versaCLI route:list`

### Generación de Código: Middleware (`middleware`)

El middleware proporciona un mecanismo conveniente para filtrar las solicitudes HTTP que ingresan a tu aplicación. Por ejemplo, para verificar la autenticación de un usuario.

*   **`middleware:make:<nombre>`**
    *   **Descripción**: Crea un nuevo archivo de middleware en el directorio `app/middleware/`. No es necesario agregar el sufijo `Middleware` al nombre, ya que la CLI lo hará automáticamente.
    *   **Uso**: `php versaCLI middleware:make:NombreDelMiddleware`
    *   **Ejemplo**: `php versaCLI middleware:make:Autenticacion` creará `AutenticacionMiddleware.php`.

*   **`middleware:delete:<nombre>`**
    *   **Descripción**: Elimina un archivo de middleware existente. No es necesario agregar el sufijo `Middleware`.
    *   **Uso**: `php versaCLI middleware:delete:NombreDelMiddleware`
    *   **Ejemplo**: `php versaCLI middleware:delete:Autenticacion` eliminará `AutenticacionMiddleware.php`.

### Generación de Código: Modelos (`model`)

Los modelos son responsables de interactuar con la base de datos, encapsulando la lógica de negocio y la manipulación de datos.

*   **`model:make:<nombre>`**
    *   **Descripción**: Crea un nuevo archivo de modelo en el directorio `app/models/`. La CLI podría añadir automáticamente la extensión `.php`.
    *   **Uso**: `php versaCLI model:make:NombreDelModelo`
    *   **Ejemplo**: `php versaCLI model:make:Producto` creará `Producto.php`.

*   **`model:delete:<nombre>`**
    *   **Descripción**: Elimina un archivo de modelo existente.
    *   **Uso**: `php versaCLI model:delete:NombreDelModelo`
    *   **Ejemplo**: `php versaCLI model:delete:Producto` eliminará `Producto.php`.

### Generación de Código: Frontend (`front`)

Estos comandos ayudan a generar archivos para el lado del cliente, como plantillas de vista o componentes.

*   **`front:makeTwig:<nombre>`**
    *   **Descripción**: Crea un nuevo archivo de plantilla Twig. La ubicación exacta puede depender de la estructura de directorios para las vistas (ej. `app/views/` o similar).
    *   **Uso**: `php versaCLI front:makeTwig:ruta/nombreDelTemplate`
    *   **Ejemplo**: `php versaCLI front:makeTwig:productos/lista` podría crear `app/views/productos/lista.twig`.

*   **`front:makeVue:<nombre>`**
    *   **Descripción**: Crea un nuevo archivo de componente Vue. La ubicación exacta puede depender de la estructura de directorios para los componentes Vue (ej. `app/vue/components/` o `resources/js/components/`).
    *   **Uso**: `php versaCLI front:makeVue:ruta/NombreDelComponente`
    *   **Ejemplo**: `php versaCLI front:makeVue:dashboard/UserProfile` podría crear `app/vue/components/dashboard/UserProfile.vue`.

---

Con esto, la documentación básica de la interfaz de línea de comandos de versaWYS-PHP debería estar bastante completa, cubriendo todos los comandos listados en la ayuda de `CommandLineInterface.php`.
