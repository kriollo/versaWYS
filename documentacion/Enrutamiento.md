# Guía Detallada de Enrutamiento en versaWYS-PHP

El sistema de enrutamiento de versaWYS-PHP es fundamental para dirigir las peticiones HTTP a la lógica de tu aplicación (controladores y métodos). Se basa en la clase `versaWYS\kernel\Router`.

## Definición de Rutas Básicas

Las rutas se definen utilizando los métodos estáticos de la clase `Router` correspondientes a los verbos HTTP. Se especifica el patrón de la ruta y el callback a ejecutar, que usualmente es un array con la clase del controlador y el nombre del método.

```php
<?php
// En tu archivo de rutas (ej: app/route/web.php)

use versaWYS\kernel\Router;
use App\Controllers\HomeController;
use App\Controllers\UserController;

// Ruta GET
Router::get('/', [HomeController::class, 'index']);

// Ruta POST
Router::post('/users', [UserController::class, 'store']);

// Ruta PUT (para actualizar un recurso existente)
Router::put('/users/{id}', [UserController::class, 'update']);

// Ruta PATCH (para actualizar parcialmente un recurso existente)
Router::patch('/users/{id}', [UserController::class, 'modify']);

// Ruta DELETE
Router::delete('/users/{id}', [UserController::class, 'destroy']);
```

## Parámetros de Ruta Dinámicos

El enrutador permite definir rutas con parámetros dinámicos encerrados entre llaves `{}`. Estos parámetros se capturan y se pasan como argumentos al método del controlador en el orden en que aparecen en la ruta.

**Ejemplo:**

Para una ruta definida como:
```php
Router::get('/blog/{category}/{slug}', [BlogController::class, 'showPost']);
```

El método `showPost` en `BlogController` recibiría `$category` y `$slug`:
```php
<?php

namespace App\Controllers;

class BlogController {
    public function showPost($category, $slug) {
        // Lógica para mostrar el post con la categoría y slug dados
        return "Mostrando post de la categoría: " . htmlspecialchars($category) . " con slug: " . htmlspecialchars($slug);
    }
}
```

## Middlewares

Los middlewares proporcionan un mecanismo conveniente para filtrar las peticiones HTTP que entran a tu aplicación. Por ejemplo, un middleware podría verificar si el usuario está autenticado antes de permitirle el acceso a ciertas rutas.

Los middlewares se definen como clases, a menudo heredando de una clase base como `versaWYS\kernel\GlobalMiddleWare`, y contienen métodos que implementan la lógica de filtrado. Luego, se asocian a una ruta utilizando el método `middleware()` encadenado a la definición de la ruta. Este método acepta un array de middlewares, donde cada middleware es un array `[$claseMiddleware, $metodoEnClase]`.

**Ejemplo de definición de un middleware (`AuthMiddleware`):**

La clase `AuthMiddleware` que vemos a continuación, hereda de `versaWYS\kernel\GlobalMiddleWare` y contiene métodos específicos para la autenticación y autorización.

```php
<?php
// En app/middleware/AuthMiddleware.php

namespace app\middleware;

use versaWYS\kernel\GlobalMiddleWare;
use versaWYS\kernel\Response;

class AuthMiddleware extends GlobalMiddleWare {

    /**
     * Protege una ruta. Si el usuario no está autenticado,
     * lo redirige a la página de login configurada.
     */
    public function protectedRoute(): void
    {
        global $session; // Accede al objeto de sesión global

        if (!$session->checkUserSession()) {
            // Si no está autenticado, redirige (ej. a '/admin/login')
            Response::redirect('/admin/login'); // Ajusta esta ruta según tu configuración
            exit(); // Detiene la ejecución para evitar que continúe al controlador
        }
        // Si la sesión es válida, el middleware no retorna nada explícitamente,
        // permitiendo que la petición continúe al siguiente middleware o al controlador.
    }

    // ... otros métodos como validateParamsLogin, redirectIfSession, etc. ...
}
```

**Aplicando el middleware a una ruta:**

1.  **Ejemplo usando un método del propio `AuthMiddleware` (`protectedRoute`):**

    Este es un caso común para proteger rutas que solo usuarios autenticados pueden ver.

    ```php
    <?php
    // En tu archivo de rutas (ej: app/routes/DashboardRoutes.php)

    use versaWYS\kernel\Router;
    use app\controllers\DashboardController; // Asegúrate que el namespace sea correcto
    use app\middleware\AuthMiddleware;

    Router::get('/dashboard', [DashboardController::class, 'index'])
          ->middleware([[AuthMiddleware::class, 'protectedRoute']]);
    ```

2.  **Ejemplo usando un método heredado de `GlobalMiddleWare` (`onlyDebug`):**

    El archivo `app/routes/versaRoutes.php` muestra cómo se utiliza un método (`onlyDebug`) que `AuthMiddleware` hereda de `GlobalMiddleWare`. Este método restringe el acceso a una ruta solo si la aplicación está en modo debug.

    ```php
    <?php
    // En app/routes/versaRoutes.php

    declare(strict_types=1);

    namespace app\routes;

    use app\middleware\AuthMiddleware;
    use versaWYS\kernel\Router;
    use versaWYS\kernel\versaController; // Asumiendo que este controlador existe en el kernel

    // description: Define la ruta para la documentación de Versa.
    Router::get('/doc', [versaController::class, 'versaRoute'])
          ->middleware([[AuthMiddleware::class, 'onlyDebug']]);
    ```
    La clase `AuthMiddleware` puede usar `onlyDebug` porque lo hereda de `GlobalMiddleWare`.

**Múltiples Middlewares:**

Se pueden aplicar múltiples middlewares a una misma ruta, simplemente añadiéndolos al array. Se ejecutarán en el orden en que se definen.

```php
<?php
// Ejemplo con múltiples middlewares

use app\controllers\AdminController; // Asegúrate que el namespace sea correcto
use app\middleware\AdminCheckMiddleware; // Un hipotético middleware para verificar roles de admin

Router::get('/admin/settings', [AdminController::class, 'settings'])
      ->middleware([
          [AuthMiddleware::class, 'protectedRoute'], 
          [AdminCheckMiddleware::class, 'verifyAdmin'] // Middleware para verificar si es admin
      ]);
```

## Organización de Archivos de Rutas

Por lo general, las aplicaciones tienden a crecer y tener muchas rutas. En lugar de definir todas las rutas en un solo archivo, es una buena práctica organizarlas en múltiples archivos dentro de la carpeta `app/routes/`. 

Por ejemplo, podrías tener archivos como:
- `web.php` para rutas generales de la web.
- `api.php` para rutas de tu API.
- `auth.php` para rutas de autenticación.
- O, como en tu proyecto, archivos específicos por módulo o funcionalidad (`DashboardRoutes.php`, `ModulesRoutes.php`, `UsersRoutes.php`, etc.).

Estos archivos de rutas luego necesitan ser cargados o incluidos por el framework para que las rutas que contienen sean registradas. Usualmente, hay un archivo principal (a menudo en `app/` o `public/`) que se encarga de incluir estos archivos de rutas, o el propio `Router` o el `kernel` del framework podría tener un mecanismo para escanear y cargar archivos de un directorio específico.

## Organización de Rutas: Agrupación y Nombres

Es común en muchos frameworks agrupar rutas bajo un prefijo común (ej. `/admin/...`) o aplicar un conjunto de middlewares a un grupo entero de rutas. También es frecuente poder asignar un nombre único a una ruta para luego generar URLs a partir de ese nombre.

En la versión actual del `Router` de versaWYS-PHP:

*   **Agrupación de Rutas (Prefijos)**: No existe un método nativo como `Router::group(['prefix' => 'admin'], function() { ... })`. Si deseas organizar tus rutas con prefijos, deberás incluirlos manualmente en cada definición de ruta:
    ```php
    Router::get('/admin/users', [AdminUsersController::class, 'index']);
    Router::get('/admin/posts', [AdminPostsController::class, 'index']);
    ```

*   **Agrupación de Middlewares**: De forma similar, no hay una forma nativa de aplicar un middleware a un grupo de rutas con una sola declaración. Los middlewares deben aplicarse a cada ruta individualmente usando el método `->middleware()`.
    Si varias rutas comparten el mismo conjunto de middlewares, tendrás que encadenar la llamada `->middleware([...])` a cada una de ellas.

*   **Nombrado de Rutas y Generación de URLs**: El sistema de enrutamiento actual no incluye una funcionalidad para asignar nombres a las rutas (ej. `->name('profile.show')`) ni para generar URLs a partir de dichos nombres. Cuando necesites generar una URL para una ruta específica en tu aplicación (por ejemplo, en plantillas o redirecciones), deberás construir la URL manualmente utilizando el path completo de la ruta.

Aunque estas características de agrupación y nombrado no estén automatizadas, puedes mantener tus archivos de rutas organizados utilizando comentarios y una estructura lógica para simular agrupaciones y facilitar la comprensión.

## Resolución de Rutas y Flujo de Petición

El método `Router::resolve()` es el encargado de gestionar la petición entrante:

1.  Normaliza la URL de la petición.
2.  Identifica el método HTTP (GET, POST, etc.).
3.  Busca una coincidencia entre la URL y las rutas definidas para ese método.
4.  Si se encuentra una ruta:
    a.  Extrae los parámetros dinámicos de la URL.
    b.  Ejecuta los middlewares asociados a la ruta en el orden en que fueron definidos. Si un middleware retorna una respuesta (ej. un error JSON o una redirección), el flujo se detiene y esa respuesta se envía al cliente.
    c.  Si todos los middlewares permiten el paso, se instancia el controlador y se llama al método especificado, pasándole los parámetros de la ruta.
    d.  La respuesta del controlador (ya sea una cadena HTML, un array/objeto para JSON, etc.) es manejada por la clase `Response`.
5.  Si no se encuentra una ruta que coincida, se genera una respuesta de error 404 (Not Found).

## Interacción con `Request` y `Response`

-   La clase `versaWYS\kernel\Request` encapsula la información de la petición HTTP entrante (URL, método, cabeceras, cuerpo, parámetros de consulta, etc.) y es accesible globalmente o puede ser inyectada como dependencia donde sea necesario (por ejemplo, en middlewares o controladores).
-   La clase `versaWYS\kernel\Response` se utiliza para construir y enviar la respuesta HTTP al cliente. Proporciona métodos para enviar HTML, JSON, redirecciones, y establecer códigos de estado HTTP.

### La Clase `Request` (`versaWYS\kernel\Request`)

La clase `Request` encapsula toda la información de la petición HTTP entrante. Una instancia de esta clase suele estar disponible globalmente (a menudo como `$request`) dentro de tus controladores y middlewares. Proporciona métodos para acceder a:

*   **Parámetros de la Ruta**: Como se vio anteriormente, estos se pasan directamente como argumentos a los métodos de tu controlador.
*   **Parámetros de Consulta (Query String)**: Para una URL como `/search?term=versawys`, puedes obtener `term`.
*   **Datos de Formularios (POST)**: Campos enviados desde formularios HTML.
*   **Cuerpo JSON**: Datos enviados como JSON (común en APIs).
*   **Archivos Subidos**: Información sobre archivos enviados.
*   **Cabeceras HTTP**.
*   **Método HTTP**, **URL**, **IP del Cliente**, etc.

**Obtención de Datos de Entrada:**

```php
<?php

namespace App\Controllers;

use versaWYS\kernel\Request; // O accede a la instancia global $request

class UserController {

    public function store(Request $requestInstance) { // $requestInstance sería la instancia global
        // Obtener un solo parámetro (de query, POST, o JSON decodificado)
        $name = $requestInstance->get('name');
        $email = $requestInstance->get('email');

        // Obtener todos los parámetros
        $allParams = $requestInstance->getAllParams();

        // Si esperas un cuerpo JSON
        if (str_contains(strtolower($requestInstance->getContentType() ?? ''), 'application/json')) {
            $jsonData = $requestInstance->getAllParams(); // Ya parseado por el Request si es JSON
        }

        // Acceder a archivos subidos
        // Para un solo archivo <input type="file" name="avatar">
        $avatarFile = $requestInstance->file('avatar'); // Retorna un objeto File (versaWYS\kernel\File)
        if ($avatarFile && $avatarFile->isValid()) {
            $avatarFile->moveTo('/path/to/storage');
        }

        // Para múltiples archivos <input type="file" name="photos[]" multiple>
        $photoFiles = $requestInstance->getAllFiles('photos'); // Retorna un array de objetos File
        foreach ($photoFiles as $photo) {
            if ($photo->isValid()) {
                $photo->moveTo('/path/to/gallery');
            }
        }

        // Obtener una cabecera específica
        $userAgent = $requestInstance->getHeader('User-Agent');

        // Obtener el método HTTP real
        $method = $requestInstance->getMethod();

        // Obtener el cuerpo crudo de la petición
        $rawBody = $requestInstance->getRawBody();

        // ... procesar datos y responder ...
        return ['message' => 'Usuario procesado', 'name' => $name];
    }
}
```

**Simulación de Verbos HTTP (Method Spoofing):**

Los formularios HTML tradicionalmente solo soportan los métodos GET y POST. Para utilizar otros verbos HTTP como PUT, PATCH o DELETE a través de un formulario, versaWYS-PHP permite la "simulación de método".

Puedes incluir un campo oculto llamado `_method` en tu formulario con el verbo HTTP que deseas simular:

```html
<form action="/users/1" method="POST">
    <input type="hidden" name="_method" value="PUT">
    <!-- Otros campos del formulario -->
    <button type="submit">Actualizar Usuario</button>
</form>
```

La clase `Request` detectará automáticamente el campo `_method` y tratará la petición como si fuera del tipo especificado (en este caso, `PUT`). El `Router` entonces buscará una coincidencia con `Router::put('/users/{id}', ...)`.

### La Clase `Response` (`versaWYS\kernel\Response`)

La clase `Response` se utiliza para construir y enviar la respuesta HTTP al cliente. Proporciona métodos para enviar HTML, JSON, redirecciones, y establecer códigos de estado HTTP. (Se detallará más en una sección futura o si exploramos `Response.php`)

## Próximamente (Pendiente de revisar `app/routes` y `app/middleware`)

-   Ejemplos específicos extraídos de `app/routes` y `app/middleware`.
-   Agrupación de rutas (si está soportado por `Router.php` o cómo se organiza en `app/routes`).
-   Nombrado de rutas y generación de URLs (si está soportado).
-   Manejo avanzado de datos de entrada (desde `Request`).
-   Simulación de verbos HTTP (si el framework lo implementa para formularios HTML).
-   Mejores prácticas y ejemplos avanzados basados en el código de tu aplicación.

## Ejemplos Prácticos de `app/routes/DashboardRoutes.php`

Para ilustrar mejor cómo se definen las rutas y se aplican los middlewares en un contexto real dentro de versaWYS-PHP, veamos algunos ejemplos tomados directamente del archivo `app/routes/DashboardRoutes.php`.

**1. Ruta GET con Middleware de Sesión:**

Esta ruta define el acceso al panel de administración principal. Utiliza el middleware `AuthMiddleware::checkSession` para asegurar que solo los usuarios con una sesión activa puedan acceder.

```php
<?php
// En app/routes/DashboardRoutes.php

use app\controllers\DashBoardController;
use app\middleware\AuthMiddleware;
use versaWYS\kernel\Router;

// ... otras rutas ...

Router::get('/admin/dashboard', [DashBoardController::class, 'dashboard'])
      ->middleware([
          [AuthMiddleware::class, 'checkSession'],
      ]);
```

**2. Ruta POST con Cadena de Middlewares para Autenticación:**

Esta ruta maneja el proceso de autenticación del login. Aplica una cadena de tres middlewares de la clase `AuthMiddleware` para validar el token CSRF, los parámetros de login y los intentos de acceso antes de llegar al método `autentication` del `DashBoardController`.

```php
<?php
// En app/routes/DashboardRoutes.php

use app\controllers\DashBoardController;
use app\middleware\AuthMiddleware;
use versaWYS\kernel\Router;

// ... otras rutas ...

Router::post('/admin/login/autentication', [DashBoardController::class, 'autentication'])
      ->middleware([
          [AuthMiddleware::class, 'validateCSRFToken'],
          [AuthMiddleware::class, 'validateParamsLogin'],
          [AuthMiddleware::class, 'validateAttemps'],
      ]);
```

Estos ejemplos demuestran el uso práctico del sistema de enrutamiento y middlewares, mostrando cómo se estructuran las definiciones de rutas en archivos específicos dentro del directorio `app/routes/` y cómo se integran los middlewares para la seguridad y validación.
