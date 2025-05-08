            <?php echo versaWYS\kernel\helpers\Functions::csrf_field(); ?>
            
-   **Uso en Plantillas Twig**:
    Aunque no tenemos un ejemplo directo de una extensión Twig específica para esto en el código revisado, la forma habitual sería hacer que la función `csrf_field()` (o una similar) esté disponible como una función global en Twig durante la configuración del motor de plantillas. Si es así, se usaría de forma similar a esto:

    ```twig
    <form method="post" action="/ruta/sensible">
        {{ csrf_field() | raw }} {# Asumiendo que 'csrf_field' está disponible globalmente en Twig y retorna HTML #}
        {# Otros campos del formulario #}
        <button type="submit">Enviar</button>
    </form>
    ```
    Alternativamente, el token podría pasarse explícitamente a la plantilla y renderizarse:
    ```twig
    <form method="post" action="/ruta/sensible">
        <input type="hidden" name="_csrf_token" value="{{ csrf_token_value }}">
        {# Otros campos del formulario #}
        <button type="submit">Enviar</button>
    </form>
    ```
    Donde `csrf_token_value` sería el token obtenido mediante `$session->get('csrf_token')` en el controlador y pasado a la plantilla.

**3. Validación del Token:**

La validación se realiza típicamente mediante un middleware aplicado a las rutas que manejan peticiones POST, PUT, PATCH o DELETE.

-   El método `versaWYS\kernel\GlobalMiddleWare::validateCSRFToken()` (heredado por middlewares como `app\middleware\AuthMiddleware`) es el responsable.
-   Este método:
    1.  Extrae el token de la petición entrante. Busca el valor enviado bajo el nombre `_csrf_token` (o `csrf_token`).
    2.  Recupera el token esperado de `$_SESSION['csrf_token']`.
    3.  Compara ambos tokens utilizando `versaWYS\kernel\helpers\Functions::validateCSRFToken()`, la cual internamente usa `hash_equals()`. Esta función es crucial ya que previene ataques de temporización (timing attacks) al comparar los hashes de los tokens en un tiempo constante.
    4.  Si los tokens no coinciden, o si el token no se encuentra en la petición, el middleware detiene la ejecución y generalmente retorna una respuesta de error (por ejemplo, un código HTTP 403 Prohibido).

**Consideración Importante sobre el Modo Debug:**

Es vital notar que la validación CSRF en `GlobalMiddleWare::validateCSRFToken()` se omite si la variable global `$debug` está establecida a `true` (`if ($debug) { return true; }`). Esto es conveniente para el desarrollo local, pero **asegúrate de que el modo debug esté desactivado en entornos de producción** para mantener la protección CSRF activa.

## Protección XSS (Cross-Site Scripting)

El Cross-Site Scripting (XSS) es una vulnerabilidad de seguridad que permite a un atacante inyectar scripts maliciosos (comúnmente JavaScript) en páginas web vistas por otros usuarios. Estos scripts se ejecutan en el navegador de la víctima y pueden robar información sensible (como cookies de sesión), modificar el contenido de la página o redirigir al usuario a sitios maliciosos.

Existen principalmente tres tipos de XSS:

1.  **XSS Reflejado (Reflected XSS)**: El script malicioso se inyecta a través de una petición HTTP (por ejemplo, en un parámetro URL) y se refleja de vuelta desde el servidor en la respuesta HTTP. Requiere que la víctima haga clic en un enlace manipulado.
2.  **XSS Almacenado (Stored XSS)**: El script malicioso se almacena permanentemente en el servidor de destino (por ejemplo, en una base de datos, a través de un comentario o un perfil de usuario) y se sirve a cualquier usuario que acceda a la página afectada.
3.  **XSS Basado en DOM (DOM-based XSS)**: La vulnerabilidad reside en el código del lado del cliente. El script malicioso modifica el entorno DOM (Document Object Model) en el navegador de la víctima, haciendo que el código legítimo del lado del cliente se ejecute de manera inesperada.

### Papel de Twig en la Prevención de XSS

versaWYS-PHP utiliza [Twig](https://twig.symfony.com/) como su motor de plantillas. Una de las características de seguridad más importantes de Twig es el **auto-escapado (auto-escaping)**.

Por defecto, Twig escapa automáticamente cualquier variable que se imprima en una plantilla HTML. Esto significa que caracteres especiales como `<`, `>`, `&`, `'`, `"` se convierten en sus respectivas entidades HTML (`&lt;`, `&gt;`, `&amp;`, `&#039;`, `&quot;`). Este comportamiento previene que el navegador interprete el contenido de la variable como código HTML o JavaScript ejecutable.

**Ejemplo:**

Si tienes una variable en tu controlador PHP:
```php
$nombreUsuario = '<script>alert("XSS");</script>';
```

Y en tu plantilla Twig la imprimes así:
```twig
<p>Bienvenido, {{ nombreUsuario }}</p>
```

Twig, por defecto, renderizará esto como:
```html
<p>Bienvenido, &lt;script&gt;alert(&quot;XSS&quot;);&lt;/script&gt;</p>
```
El script no se ejecutará porque los caracteres `<` y `>` han sido escapados, y el navegador los mostrará como texto literal en lugar de interpretarlos como etiquetas de script.

**Controlando el Escapado:**

Aunque el auto-escapado es una protección poderosa, a veces necesitas renderizar HTML que confías que es seguro. Twig te permite marcar datos como seguros usando el filtro `|raw`:

```twig
{# CUIDADO: Solo usa raw si confías plenamente en la fuente del HTML. #}
{{ contenidoHtmlSeguro | raw }}
```

Usar `|raw` desactiva el escapado para esa variable específica. Debes ser extremadamente cuidadoso al usar `|raw` y asegurarte de que el contenido que estás imprimiendo ha sido sanitizado adecuadamente o proviene de una fuente completamente confiable.

La configuración de Twig en `versaWYS-PHP` (generalmente en `config/config.json` o donde se inicializa Twig) suele tener el auto-escapado habilitado por defecto (`"auto_escape": true`).

**(A continuación, se investigarán configuraciones adicionales y funciones específicas de versaWYS-PHP para la prevención de XSS.)**

### Confirmación de Configuración en versaWYS-PHP

Como se ha verificado en el archivo `versaWYS/kernel/config/config.json`, la configuración de Twig incluye:

```json
"twig": {
    "cache": false,
    "compiled_dir": "./app/templates/.cache",
    "templates_dir": "./app/templates",
    "strict_variables": true,
    "auto_escape": true, // <-- Auto-escapado habilitado por defecto
    "default_template_404": "versaWYS/404"
},
```
Esta es la principal línea de defensa contra XSS al renderizar datos en las plantillas.

### Consideraciones Adicionales

-   **Uso del Filtro `|raw`**: Recuerda que si utilizas el filtro `{{ miVariable | raw }}` en Twig, estás desactivando la protección de auto-escapado para `miVariable`. Solo debes hacer esto si confías plenamente en la fuente de `miVariable` y sabes que es HTML seguro, o si la has sanitizado previamente de forma manual y rigurosa.

-   **Decodificación de Entidades en `Request.php`**: El método `versaWYS\kernel\Request::processJsonValue()` decodifica entidades HTML (`html_entity_decode`) de los valores JSON entrantes. Esto significa que si un atacante envía JSON con entidades HTML maliciosas (ej: `{"comment":"&lt;script&gt;alert('XSS')&lt;/script&gt;"}`), el valor PHP resultante contendrá los caracteres literales (`<script>alert('XSS')</script>`). Es crucial que si estos datos se vuelven a mostrar en una plantilla Twig, el auto-escapado de Twig esté activo para neutralizar el script.

-   **Funciones de Sanitización Específicas**: Fuera del auto-escapado de Twig, versaWYS-PHP no parece proporcionar funciones helper de sanitización de HTML de propósito general (como envoltorios para `htmlspecialchars` o `strip_tags` para uso antes de pasar datos a Twig). Si necesitas una lógica de sanitización más compleja (por ejemplo, permitir un subconjunto seguro de HTML), deberás implementarla utilizando las funciones estándar de PHP o bibliotecas de terceros, y luego posiblemente usar el filtro `|raw` con precaución sobre el resultado ya sanitizado.

-   **Escapado para Otros Contextos**: 
    *   La función `versaWYS\kernel\RedBeanCnn::scape()` está diseñada para escapar datos antes de interactuar con la base de datos (prevención de Inyección SQL), no para el contexto HTML.
    *   La función `versaWYS\kernel\File::sanitizeFileName()` ayuda a asegurar los nombres de archivo, lo cual es importante para la seguridad del sistema de archivos y puede tener beneficios secundarios para XSS si los nombres de archivo se muestran, pero Twig seguiría siendo la defensa principal para la visualización.

En resumen, confía en el auto-escapado de Twig como tu principal defensa contra XSS. Sé muy cauteloso si necesitas desactivarlo con `|raw` y comprende que serás responsable de la sanitización en esos casos.

## Prevención de Inyección SQL

La Inyección SQL es una técnica de ataque que aprovecha vulnerabilidades en la forma en que una aplicación web interactúa con su base de datos. Si las entradas del usuario no se sanitizan o escapan adecuadamente, un atacante puede "inyectar" código SQL malicioso en las consultas que la aplicación envía a la base de datos.

Esto puede permitir al atacante:

-   Leer datos sensibles a los que no debería tener acceso (por ejemplo, datos de otros usuarios).
-   Modificar o eliminar datos.
-   Obtener control administrativo sobre el servidor de base de datos.
-   En algunos casos, ejecutar comandos a nivel del sistema operativo del servidor.

**Ejemplo Clásico de Inyección SQL (sin protección):**

Supongamos una consulta PHP para autenticar un usuario:

```php
$username = $_POST['username']; // Entrada del usuario: admin'
$password = $_POST['password']; // Entrada del usuario: password

$sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
// Consulta resultante si username es 'admin':
// SELECT * FROM users WHERE username = 'admin' AND password = 'password'

// Pero si un atacante introduce en el campo username: admin' OR '1'='1
// Y en el campo password: algo' OR '1'='1
// La consulta se convertiría en:
// SELECT * FROM users WHERE username = 'admin' OR '1'='1' AND password = 'algo' OR '1'='1'
// Esto podría permitir el acceso sin conocer la contraseña.
```

### Papel del ORM (RedBeanPHP) en la Prevención

versaWYS-PHP utiliza RedBeanPHP, un ORM que facilita la interacción con la base de datos. Una de las ventajas de seguridad más significativas de usar un ORM como RedBeanPHP es que **promueve el uso de consultas parametrizadas (o prepared statements) de forma abstracta**.

**Consultas Parametrizadas:**

Las consultas parametrizadas separan la estructura de la consulta SQL de los datos que se insertan en ella. En lugar de concatenar directamente la entrada del usuario en la cadena SQL, se utilizan marcadores de posición (placeholders). La base de datos luego compila la estructura de la consulta primero y después inserta los datos de forma segura, tratándolos como datos literales y no como código SQL ejecutable.

Cuando utilizas los métodos estándar de RedBeanPHP para buscar, guardar, o eliminar beans (objetos que representan filas de la tabla), RedBeanPHP generalmente maneja la creación de estas consultas parametrizadas por debajo, protegiéndote de la mayoría de los vectores comunes de inyección SQL.

**Ejemplo con RedBeanPHP (conceptual):**

```php
// En lugar de construir SQL manualmente...
$user = R::findOne('users', ' username = ? AND password_hash = ? ', [ $userInputUsername, $hashedUserInputPassword ]);

if ($user) {
    // Usuario encontrado y contraseña verificada (asumiendo que password_hash ya está hasheado)
}
```
En este caso, `$userInputUsername` y `$hashedUserInputPassword` se tratan como datos, no como parte de la instrucción SQL. RedBeanPHP (o la capa PDO subyacente) se encarga de escapar o manejar correctamente estos valores para prevenir inyecciones.

**(A continuación, se discutirán las prácticas específicas con RedBeanPHP en versaWYS-PHP y el uso de métodos como `RedBeanCnn::scape()`.)**

### Prácticas Específicas con RedBeanPHP en versaWYS-PHP

1.  **Priorizar los Métodos del ORM**: La forma más segura de interactuar con la base de datos en versaWYS-PHP es utilizando los métodos que RedBeanPHP proporciona para las operaciones CRUD (Crear, Leer, Actualizar, Eliminar) y búsquedas. Ejemplos:
    *   `R::dispense('nombredelbean')`: Para crear un nuevo bean (fila).
    *   `R::store($bean)`: Para guardar o actualizar un bean.
    *   `R::trash($bean)`: Para eliminar un bean.
    *   `R::load('nombredelbean', $id)`: Para cargar un bean por su ID.
    *   `R::findOne('nombredelbean', ' SQL query con placeholders ', [ $valor1, $valor2 ])`: Para buscar un único bean.
    *   `R::findAll('nombredelbean', ' SQL query con placeholders ', [ $valor1, $valor2 ])`: Para buscar múltiples beans.
    *   `R::findAndCount('nombredelbean', ' SQL query con placeholders ', [ $valor1, $valor2 ])`: Para buscar y contar beans.

    Estos métodos están diseñados para utilizar consultas parametrizadas internamente, lo que reduce drásticamente el riesgo de inyección SQL.

2.  **Uso Correcto de Placeholders en Consultas**: Cuando necesites condiciones más complejas en tus búsquedas con `R::findOne()`, `R::findAll()`, `R::findAndCount()`, etc., siempre utiliza los marcadores de posición (`?`) y pasa los valores como un array separado:

    ```php
    // BUENA PRÁCTICA: Uso de placeholders
    $status = 'activo';
    $email = 'usuario@ejemplo.com';
    $users = R::findAll('user', ' status = ? AND email = ? ', [ $status, $email ]);

    // MALA PRÁCTICA: Concatenar directamente variables (¡VULNERABLE!)
    // $users = R::findAll('user', " status = '$status' AND email = '$email' "); // NO HACER ESTO
    ```

3.  **El Método `RedBeanCnn::scape()`**:
    La clase `versaWYS\kernel\RedBeanCnn` (o una similar que gestione la conexión de RedBeanPHP) puede contener un método como `scape()`:

    ```php
    public function scape($e)
    {
        if (null === $e) {
            return '';
        }

        if (is_numeric($e) && $e <= 2147483647) { 
            if (explode('.', $e)[0] != $e) { 
                return (float) $e;
            }
            return (int) $e;
        }

        return (string) trim(
            str_replace(
                ['\\', "\x00", '\n', '\r', "'", '"', "\x1a"],
                ['\\\\', '\\0', '\\n', '\\r', "\\'", '\"', '\\Z'],
                $e
            )
        );
    }
    ```
    **Análisis de `scape()`**:
    *   **Para números**: Intenta convertirlos a `float` o `int`. Esto es una forma de sanitización por tipo, que es bueno para campos numéricos.
    *   **Para cadenas**: Escapa caracteres especiales (`\`, `\x00`, `\n`, `\r`, `'`, `"`, `\x1a`). Esta es una forma de escapado manual.

    **¿Cuándo usar `scape()`?**
    *   Este método **NO es un sustituto** de las consultas parametrizadas que RedBeanPHP usa internamente con métodos como `R::findOne()`, `R::store()`, etc.
    *   Su uso debería ser **extremadamente limitado**. Podría considerarse como una utilidad de último recurso si te ves *forzado* a construir una pequeña parte de una cadena SQL manualmente (lo cual se desaconseja fuertemente). 
    *   **La preferencia absoluta es siempre confiar en los mecanismos de parametrización del ORM.**
    *   Si utilizas `R::exec()` o `R::getAll()` para ejecutar SQL crudo, y necesitas incorporar datos del usuario en esa consulta cruda, *debes* asegurarte de que cada pieza de dato esté debidamente escapada. `scape()` podría ser una herramienta para ello, pero usar consultas parametrizadas con PDO directamente sería aún más seguro si RedBeanPHP no cubre el caso de uso con sus abstracciones.

4.  **Evitar SQL Crudo Siempre que Sea Posible**:
    Funciones como `R::exec($sql)` (para INSERT, UPDATE, DELETE crudos) o `R::getAll($sql, $bindings)` (para SELECT crudos) permiten ejecutar cualquier consulta SQL. 
    *   Si usas `R::getAll()`, **siempre** proporciona los bindings (valores para los placeholders) como un array separado. RedBeanPHP entonces usará PDO para realizar la parametrización:
        ```php
        $status = 'pendiente';
        $results = R::getAll('SELECT * FROM orders WHERE status = ?', [ $status ]); // SEGURO
        ```
    *   Evita construir la cadena `$sql` para `R::exec()` o `R::getAll()` concatenando directamente entradas del usuario sin un escapado o parametrización rigurosa.

5.  **Validación y Sanitización de Entradas**: 
    Aunque el ORM ayuda enormemente, sigue siendo crucial validar y sanitizar todas las entradas del usuario en tu aplicación PHP antes de que incluso lleguen al ORM.
    *   **Validación**: Asegúrate de que los datos tienen el tipo esperado (número, cadena, email), longitud, formato, etc.
    *   **Sanitización**: Elimina o modifica caracteres no deseados o potencialmente peligrosos, según el contexto.

6.  **Principio de Mínimo Privilegio**: 
    Configura el usuario de la base de datos que utiliza tu aplicación PHP con los permisos mínimos necesarios. Por ejemplo, si una parte de la aplicación solo necesita leer datos, el usuario de BD no debería tener permisos de escritura (INSERT, UPDATE, DELETE) sobre esas tablas.

### En Resumen para Inyección SQL:

-   **Confía en RedBeanPHP**: Utiliza sus métodos estándar (`findOne`, `findAll`, `store`, `trash`, etc.) y sus mecanismos de binding de parámetros (`?`) tanto como sea posible.
-   **Evita SQL Crudo**: Si es inevitable, usa `R::getAll()` con bindings o PDO directamente con consultas parametrizadas.
-   **El método `scape()` es un utilitario secundario**: No es la primera línea de defensa.
-   **Valida tus entradas**: Siempre.

## Protección contra Ataques de Fuerza Bruta en Inicios de Sesión

Un ataque de fuerza bruta es un método utilizado por los atacantes para obtener acceso no autorizado a una cuenta intentando sistemáticamente todas las combinaciones posibles de contraseñas hasta encontrar la correcta. Los formularios de inicio de sesión son un objetivo común para estos ataques.

versaWYS-PHP implementa un mecanismo para mitigar los ataques de fuerza bruta en sus procesos de autenticación.

### Mecanismo de Protección

La protección se basa en el seguimiento de los intentos de inicio de sesión fallidos asociados a una dirección de correo electrónico. La lógica principal reside en la clase `versaWYS\kernel\Session` y se aplica a través de un middleware, típicamente `app\middleware\AuthMiddleware`.

1.  **Conteo de Intentos**: 
    *   Cuando un usuario intenta iniciar sesión, el método `app\middleware\AuthMiddleware::validateAttemps()` es invocado.
    *   Este método utiliza `versaWYS\kernel\Session::setAttemptsSession()` para registrar e incrementar el contador de intentos fallidos para el correo electrónico proporcionado. Estos datos se almacenan en la variable de sesión `$_SESSION['login_user_attempts']`.

2.  **Verificación de Bloqueo**: 
    *   Después de registrar el intento, `AuthMiddleware::validateAttemps()` llama a `versaWYS\kernel\Session::maximumAttempts()`.
    *   Este método compara el número de intentos acumulados con un umbral máximo configurable. También verifica si ya existe un tiempo de bloqueo activo.

3.  **Configuración de Umbrales**: 
    Los parámetros críticos para esta protección se definen en el archivo de configuración global (generalmente `versaWYS/kernel/config/config.json`):
    ```json
    "login_attempt": {
        "max": 5,     // Número máximo de intentos fallidos permitidos
        "time": 120   // Duración del bloqueo en segundos (ej: 120 segundos = 2 minutos)
    }
    ```
    *   `max`: Si el número de intentos fallidos alcanza este valor, la cuenta se bloquea.
    *   `time`: La duración en segundos durante la cual la cuenta permanecerá bloqueada.

4.  **Bloqueo de Cuenta**: 
    *   Si `Session::maximumAttempts()` determina que se ha excedido el número máximo de intentos y el período de bloqueo aún no ha comenzado, establece una marca de tiempo futura para el desbloqueo (`$attempts[$email]['time'] = time() + $config['login_attempt']['time'];`).
    *   Si el usuario intenta iniciar sesión mientras está dentro del período de bloqueo (`time() < $attempts[$email]['time']`), `AuthMiddleware::validateAttemps()` devuelve una respuesta de error, impidiendo el intento de autenticación. Esta respuesta incluye detalles como el número de intentos y el tiempo restante de bloqueo.
    ```php
    // En AuthMiddleware::validateAttemps() si está bloqueado:
    return [
        'success' => 0,
        'message' => 'Ha superado el número máximo de intentos',
        'errors' => [
            'error' => 'Vuelva a intentarlo en un momento...',
            'attemps' => "intentos: {contador_intentos}",
            'time' => "tiempo de espera {tiempo_restante}",
        ],
        'code' => 401,
    ];
    ```

5.  **Restablecimiento de Intentos**: 
    *   Si un usuario intenta iniciar sesión *después* de que el período de bloqueo haya expirado, `Session::maximumAttempts()` llama a `Session::restoreAttempts()`. Esto restablece el contador de intentos a `0` y el tiempo de bloqueo a `null` para ese correo electrónico, permitiendo un nuevo ciclo de intentos de inicio de sesión.
    *   Un inicio de sesión exitoso también debería idealmente llamar a `Session::restoreAttempts()` para el email correspondiente, para que los intentos fallidos anteriores no cuenten contra futuros intentos legítimos si el usuario olvida su contraseña de nuevo.

### Recomendaciones Adicionales

-   **Ajustar la Configuración**: Los valores de `max` y `time` en `login_attempt` deben equilibrar la seguridad con la usabilidad. Un bloqueo demasiado corto o demasiados intentos permitidos pueden no ser lo suficientemente disuasorios, mientras que un bloqueo muy largo o muy pocos intentos pueden frustrar a los usuarios legítimos.
-   **CAPTCHA**: Para una protección aún más robusta, considera integrar un CAPTCHA (como Google reCAPTCHA) después de un número menor de intentos fallidos, o siempre si el tráfico sospechoso es alto. Esto ayuda a diferenciar entre humanos y bots. versaWYS-PHP no parece incluir una integración de CAPTCHA por defecto, por lo que esto requeriría una implementación personalizada.
-   **Notificaciones**: Considera notificar a los administradores o incluso al usuario (a través de un canal secundario, si es posible) sobre repetidos intentos fallidos de inicio de sesión, ya que esto podría indicar un ataque dirigido.
-   **Auditoría y Monitoreo**: Mantén registros (logs) de los intentos de inicio de sesión fallidos y bloqueos. Esto puede ayudar a identificar patrones de ataque y ajustar las políticas de seguridad.

## Manejo Seguro de Contraseñas

El almacenamiento y la verificación seguros de las contraseñas de los usuarios son fundamentales para proteger las cuentas contra accesos no autorizados. versaWYS-PHP implementa prácticas robustas para el manejo de contraseñas.

### Hashing de Contraseñas

Las contraseñas nunca deben almacenarse en texto plano en la base de datos. En su lugar, se deben almacenar como "hashes" criptográficos. Un hash es el resultado de una función matemática unidireccional que transforma la contraseña en una cadena de caracteres de longitud fija. Es computacionalmente inviable revertir este proceso para obtener la contraseña original a partir del hash.

versaWYS-PHP utiliza las funciones nativas de PHP recomendadas para el hashing de contraseñas:

1.  **Creación y Actualización de Contraseñas**:
    *   Cuando un usuario se registra o cambia su contraseña (ya sea por sí mismo o por un administrador), la contraseña proporcionada en texto plano es procesada por la función `versaWYS\kernel\helpers\Functions::hash(string $password): string`.
    *   Internamente, esta función utiliza `password_hash($password, PASSWORD_BCRYPT)`.
        ```php
        // En versaWYS\kernel\helpers\Functions.php
        public static function hash(string $password): string
        {
            return password_hash($password, PASSWORD_BCRYPT);
        }
        ```
    *   `PASSWORD_BCRYPT` es un algoritmo de hashing fuerte y lento deliberadamente, lo que lo hace resistente a ataques de fuerza bruta contra los hashes. `password_hash()` también se encarga de generar una "sal" (salt) criptográficamente segura de forma automática y la incluye como parte del hash resultante. Esto significa que dos usuarios con la misma contraseña tendrán hashes almacenados diferentes.
    *   Los controladores (`UsersController` y `DashBoardController`) son responsables de llamar a `Functions::hash()` sobre la contraseña en texto plano *antes* de pasarla a los métodos del modelo (`app\models\Users`) para su almacenamiento en la base de datos. Por ejemplo, en `UsersController::registerUser()`:
        ```php
        // En app\controllers\UsersController.php -> registerUser()
        $params['password'] = Functions::hash($params['password']);
        $result = (new models\Users())->create($params);
        ```

2.  **Verificación de Contraseñas (Inicio de Sesión)**:
    *   Durante el proceso de inicio de sesión, cuando un usuario introduce su contraseña, esta no se hashea directamente para compararla con el hash almacenado. En su lugar, se utiliza la función `versaWYS\kernel\helpers\Functions::verifyHash(string $password, string $hash): bool`.
    *   Esta función es un envoltorio para `password_verify($password, $hash)`.
        ```php
        // En versaWYS\kernel\helpers\Functions.php
        public static function verifyHash(string $password, string $hash): bool
        {
            return password_verify($password, $hash);
        }
        ```
    *   `password_verify()` toma la contraseña en texto plano proporcionada por el usuario y el hash almacenado en la base de datos. Extrae la sal del hash almacenado, aplica el mismo algoritmo de hashing (BCRYPT en este caso) a la contraseña proporcionada usando esa sal, y luego compara el resultado con el hash almacenado de forma segura para evitar ataques de temporización.
    *   Este proceso se realiza en `DashBoardController::autentication()`:
        ```php
        // En app\controllers\DashBoardController.php -> autentication()
        if (
            $user === [] ||
            !Functions::verifyHash($params['password'], $user['password']) || // Verificación del hash
            $user['status'] === 0
        ) {
            // ... error de autenticación
        }
        ```

### Política de Contraseñas

Además del hashing seguro, versaWYS-PHP permite definir políticas de complejidad para las contraseñas. Esto ayuda a los usuarios a elegir contraseñas más fuertes.

*   La configuración de la política de contraseñas se encuentra en el archivo de configuración global (generalmente `versaWYS/kernel/config/config.json`) bajo la clave `auth.password_policy`.
    ```json
    "auth": {
        // ... otras configuraciones de autenticación ...
        "password_policy": {
            "large": 8,         // Longitud mínima
            "uppercase": true,  // Requerir al menos una mayúscula
            "lowercase": true,  // Requerir al menos una minúscula
            "number": true,     // Requerir al menos un número
            "special_chars": true // Requerir al menos un carácter especial
        },
        "expiration_days_password": 30 // Días para la expiración de la contraseña
    }
    ```
*   Estas políticas son verificadas por el método `versaWYS\kernel\GlobalMiddleWare::validatePolicyPassword($password)` antes de que la contraseña sea hasheada durante los procesos de creación o cambio de contraseña.
*   El framework también incluye una característica de expiración de contraseñas (`expiration_days_password`), que obliga a los usuarios a cambiar sus contraseñas periódicamente.

### Recomendaciones

-   **Mantener PHP Actualizado**: La fortaleza de `password_hash()` y `PASSWORD_BCRYPT` depende de la versión de PHP. Mantener PHP actualizado asegura que se utilizan las implementaciones más seguras.
-   **Coste de BCRYPT**: `password_hash()` con `PASSWORD_BCRYPT` tiene una opción de `cost` (costo). Aunque versaWYS-PHP usa el costo por defecto (actualmente 10), este podría ajustarse si es necesario. Un costo más alto aumenta el tiempo necesario para generar el hash, haciéndolo más resistente a la fuerza bruta, pero también consume más recursos del servidor. El valor por defecto suele ser un buen equilibrio.
-   **No Implementar Hashing Personalizado**: Siempre se debe preferir el uso de `password_hash()` y `password_verify()` sobre cualquier implementación de hashing personalizada, ya que estas funciones están diseñadas por expertos en criptografía y se actualizan para hacer frente a nuevas amenazas.

## Configuración de Cookies Seguras y Cabeceras HTTP

La configuración adecuada de las cookies y el uso de cabeceras HTTP de seguridad son capas adicionales cruciales para proteger una aplicación web contra diversos ataques.

### Cookies Seguras

Las cookies, especialmente aquellas utilizadas para gestionar sesiones (como la cookie `tknHash` en versaWYS-PHP), deben configurarse con atributos que mejoren su seguridad.

**Manejo de Cookies en versaWYS-PHP:**

El framework gestiona las cookies a través de la clase `versaWYS\kernel\Cookie` y la configuración se encuentra principalmente en `versaWYS\kernel\config\config.json` bajo la sección `session.user_cookie`.

La cookie de sesión principal, llamada `tknHash`, almacena un JSON Web Token (JWT) que contiene la información de la sesión del usuario.

**Atributos de Seguridad para `tknHash` (Cookie de Sesión):**

1.  **HttpOnly**:
    *   **Configuración Actual**: El método `Session::setUserSession()` establece la cookie `tknHash` utilizando el valor de `http_only` de `$config['session']['user_cookie']['http_only']`.
    *   **Valor por Defecto en `config.json`**: `"http_only": false`.
    *   **Riesgo**: Si `HttpOnly` es `false` (valor por defecto), la cookie puede ser accedida por scripts del lado del cliente (JavaScript). Esto la hace vulnerable al robo de sesión a través de ataques de Cross-Site Scripting (XSS). Un atacante que logre inyectar JS podría leer la cookie y secuestrar la sesión del usuario.
    *   **Recomendación**: **Establecer `http_only` a `true` en `config.json` para producción.** Esto instruye al navegador a no permitir el acceso a la cookie desde JavaScript.

2.  **Secure**:
    *   **Configuración Actual**: El método `Session::setUserSession()` establece la cookie `tknHash` utilizando el valor de `secure` de `$config['session']['user_cookie']['secure']`.
    *   **Valor por Defecto en `config.json`**: `"secure": false`.
    *   **Riesgo**: Si `Secure` es `false` (valor por defecto), la cookie se transmitirá tanto por conexiones HTTP como HTTPS. Si la aplicación está disponible (incluso parcialmente) sobre HTTP, un atacante en la misma red (Man-in-the-Middle) podría interceptar la cookie.
    *   **Recomendación**: **Establecer `secure` a `true` en `config.json` para producción si la aplicación se sirve exclusivamente sobre HTTPS.** Esto asegura que la cookie solo se envíe a través de conexiones cifradas.

3.  **SameSite**:
    *   **Configuración Actual**: El atributo `SameSite` (valores comunes: `Lax`, `Strict`, `None`) no se establece explícitamente para la cookie `tknHash`. La clase `Cookie::set()` no incluye la opción para definir este atributo al llamar a `setcookie()` de PHP.
    *   **Riesgo**: La ausencia de un atributo `SameSite` explícito deja la decisión al comportamiento por defecto del navegador, que ha ido cambiando. No especificar `SameSite` puede hacer que la aplicación sea más vulnerable a ataques de Cross-Site Request Forgery (CSRF) en ciertos escenarios, ya que la cookie podría enviarse con solicitudes de origen cruzado.
    *   **Recomendación**: **Modificar `Cookie::set()` para permitir la configuración del atributo `SameSite` y establecerlo a `Lax` o `Strict` para la cookie de sesión.**
        *   `SameSite=Lax`: La cookie se envía con solicitudes de origen cruzado de navegación de nivel superior (ej. al hacer clic en un enlace), pero no con solicitudes iniciadas por terceros (ej. cargar una imagen o iframe, o con `XMLHttpRequest`). Es un buen equilibrio entre seguridad y usabilidad.
        *   `SameSite=Strict`: La cookie solo se envía con solicitudes del mismo sitio. Ofrece la mayor protección contra CSRF pero puede afectar la experiencia del usuario si se navega al sitio desde un enlace externo.

**Valor de la Cookie de Sesión (`tknHash`):**

*   El valor de la cookie es un JWT firmado con `HS256` utilizando la clave definida en `$config['session']['key']`.
*   **Encriptación del Contenido**: La configuración `config.json` incluye una `"key_encript"` dentro de `session.user_cookie`, y la documentación sugiere que esta clave es para "encriptar/desencriptar el contenido de la cookie". Sin embargo, el análisis del código actual de `Session.php` indica que el JWT en la cookie `tknHash` está *firmado* pero no *encriptado* con esta clave. La firma protege la integridad y autenticidad del token, pero su payload (contenido) es visible (codificado en Base64). Si la confidencialidad del payload del JWT es crítica, se debería considerar la encriptación del JWT (JWE) además de la firma (JWS).

**Ejemplo de configuración recomendada en `config.json` (para producción sobre HTTPS):**
```json
"session": {
    // ...
    "user_cookie": {
        "domain": ".tudominio.com", // Ajustar al dominio de producción
        "enable": true,
        "key_encript": "UNA_CLAVE_MUY_LARGA_Y_SECRETA_GENERADA_ALEATORIAMENTE", // Cambiar y proteger
        "lifetime": 3600,           // Ejemplo: 1 hora para sesión activa
        "lifetime_remember": 2592000, // Ejemplo: 30 días para "recordarme"
        "secure": true,             // ¡IMPORTANTE PARA HTTPS!
        "http_only": true           // ¡IMPORTANTE!
        // "samesite": "Lax"       // (Requeriría modificación en Cookie::set())
    }
}

```

## Protección contra Fuerza Bruta en Logins

Un ataque de fuerza bruta contra un formulario de inicio de sesión consiste en intentar sistemáticamente múltiples combinaciones de nombre de usuario y contraseña hasta encontrar una válida. Es un vector de ataque común para comprometer cuentas de usuario.

**Mecanismo de Protección en versaWYS-PHP:**

El framework versaWYS-PHP incluye un mecanismo básico de protección contra ataques de fuerza bruta en los inicios de sesión. Este sistema se basa en el seguimiento de intentos fallidos por dirección de correo electrónico y un bloqueo temporal de la cuenta si se supera un umbral definido.

**Funcionamiento Detallado:**

1.  **Seguimiento de Intentos:** Cuando un usuario intenta iniciar sesión, el `app\middleware\AuthMiddleware::validateAttemps()` interactúa con la clase `versaWYS\kernel\Session` para gestionar los intentos.
    *   Se utiliza una variable de sesión (`$_SESSION['login_user_attempts']`) para almacenar un array. Las claves de este array son las direcciones de email y los valores son objetos que contienen:
        *   `attempts`: El número de intentos fallidos consecutivos para ese email.
        *   `time`: Un timestamp que indica cuándo finalizará el bloqueo si la cuenta está actualmente bloqueada.
2.  **Incremento de Intentos:** Por cada intento fallido para un email, el contador `attempts` asociado a ese email se incrementa (`Session::setAttemptsSession()`).
3.  **Verificación del Límite:** El sistema verifica si el contador `attempts` ha alcanzado el máximo permitido (`Session::maximumAttempts()`). Este máximo es configurable.
    *   Si se alcanza el límite, se registra un timestamp de bloqueo (`time`) para ese email. Este timestamp se calcula sumando el tiempo actual más una duración de bloqueo configurable.
    *   Mientras el tiempo actual sea menor que el timestamp de bloqueo, cualquier nuevo intento de inicio de sesión para ese email será denegado.
4.  **Desbloqueo:** Una vez que el tiempo de bloqueo ha transcurrido, si el usuario intenta iniciar sesión nuevamente y el intento es válido, o si el tiempo de bloqueo simplemente ha pasado, los contadores de intentos para ese email se restablecen (`Session::restoreAttempts()`), permitiendo nuevos intentos.

**Configuración:**

La configuración de este mecanismo se encuentra en el archivo `versaWYS/kernel/config/config.json`, dentro de la sección `login_attempt`:

```json
"login_attempt": {
    "max": 5,
    "time": 120
},
```

*   `"max"`: Define el número máximo de intentos de inicio de sesión fallidos permitidos antes de que se active el bloqueo. Por defecto, es **5 intentos**.
*   `"time"`: Define la duración del bloqueo en **segundos**. Por defecto, es **120 segundos** (2 minutos).

Los desarrolladores pueden ajustar estos valores según las necesidades de seguridad de su aplicación.

**Limitaciones y Consideraciones Adicionales:**

Si bien el mecanismo existente proporciona una capa base de protección, los desarrolladores deben ser conscientes de sus limitaciones y considerar medidas adicionales para una seguridad más robusta:

1.  **Alcance del Bloqueo:**
    *   El bloqueo se aplica por **dirección de email**. No hay un bloqueo inherente basado en la dirección IP a nivel de esta funcionalidad. Un atacante podría seguir intentando atacar diferentes cuentas de email desde la misma IP.
    *   Al estar basado en la sesión del servidor, no previene que un atacante intente desde múltiples IPs o limpie sus identificadores de sesión si no hay otros mecanismos de rastreo.

2.  **Ausencia de CAPTCHA:**
    *   El sistema no integra CAPTCHAs para distinguir entre humanos y bots después de varios intentos fallidos. La introducción de un CAPTCHA (ej. Google reCAPTCHA) después de 2-3 intentos fallidos podría reducir significativamente los ataques automatizados.

3.  **Bloqueo No Progresivo:**
    *   La duración del bloqueo es fija según la configuración (`login_attempt.time`). No aumenta progresivamente con múltiples series de intentos fallidos para la misma cuenta o IP.

4.  **Notificaciones:**
    *   El sistema no incluye notificaciones automáticas al usuario o a los administradores sobre intentos de inicio de sesión fallidos o bloqueos de cuenta.

**Recomendaciones para Fortalecer la Protección:**

Para mejorar la defensa contra ataques de fuerza bruta, los desarrolladores deberían considerar implementar:

*   **Limitación de Tasa por IP:** Adicionalmente al bloqueo por email, implementar un sistema de limitación de tasa basado en IP (ej. con Fail2ban a nivel de servidor, o lógica en un middleware global que rastree IPs en una base de datos o caché como Redis).
*   **Integración de CAPTCHA:** Introducir un CAPTCHA después de un umbral bajo de intentos fallidos.
*   **Bloqueo Progresivo/Exponencial:** Aumentar la duración del bloqueo si se detectan repetidos ciclos de intentos fallidos para la misma cuenta o IP.
*   **Alertas y Notificaciones:** Notificar a los usuarios (por un canal seguro, si es posible) sobre múltiples intentos fallidos en su cuenta. Alertar a los administradores sobre actividades sospechosas de fuerza bruta.
*   **Autenticación de Dos Factores (2FA):** Fomentar o exigir 2FA es una de las defensas más efectivas, ya que comprometer solo la contraseña no sería suficiente para el acceso.
*   **Políticas de Contraseñas Fuertes:** Recordar la importancia de políticas de contraseñas robustas.
*   **Web Application Firewall (WAF):** Un WAF puede ayudar a detectar y bloquear patrones de ataque a nivel de red.

Al comprender la funcionalidad integrada y sus limitaciones, los desarrolladores pueden tomar decisiones informadas para complementar la protección contra fuerza bruta en sus aplicaciones construidas con versaWYS-PHP.

## Consideraciones para la Subida de Archivos
