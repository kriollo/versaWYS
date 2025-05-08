# Principios Básicos de Seguridad en versaWYS-PHP

## Introducción

La seguridad es un aspecto fundamental en el desarrollo de cualquier aplicación web. versaWYS-PHP se esfuerza por proporcionar un entorno de desarrollo que facilite la implementación de prácticas de seguridad robustas. Sin embargo, la seguridad es una responsabilidad compartida entre el framework y el desarrollador. Este documento describe las principales amenazas de seguridad web y cómo versaWYS-PHP ayuda a mitigarlas, junto con las mejores prácticas que los desarrolladores deben seguir.

## Protección CSRF (Cross-Site Request Forgery)

La Falsificación de Solicitudes entre Sitios (CSRF o XSRF) es un tipo de ataque que obliga a un usuario final autenticado a ejecutar acciones no deseadas en una aplicación web en la que está actualmente autenticado. Los ataques CSRF se dirigen específicamente a solicitudes que cambian el estado, no al robo de datos, ya que el atacante no puede ver la respuesta a la solicitud falsificada.

**Mecanismo en versaWYS-PHP:**

versaWYS-PHP implementa la protección CSRF mediante el uso de tokens sincronizadores (Synchronizer Token Pattern).

1.  **Generación del Token:**
    *   Cuando se requiere protección (generalmente para cualquier solicitud que no sea GET, HEAD, OPTIONS, TRACE), se genera un token único y secreto asociado a la sesión del usuario.
    *   La clase `versaWYS\kernel\Session` es responsable de generar y almacenar este token en la sesión del usuario (`$_SESSION['csrf_token']`).
    *   La función `versaWYS\kernel\helpers\Functions::generateCSRFToken()` se utiliza para crear un token criptográficamente seguro.

2.  **Inclusión del Token en Formularios y Solicitudes:**
    *   Este token debe incluirse en todos los formularios HTML y solicitudes AJAX que puedan modificar datos.
    *   **Uso en Formularios PHP/HTML directos**:
        Se puede utilizar una función helper para generar el campo oculto del token:
        ```php
        <?php echo versaWYS\kernel\helpers\Functions::csrf_field(); ?>
        ```
    *   **Uso en Plantillas Twig**:
        ```twig
        <form method="post" action="/ruta/sensible">
            {{ csrf_field() | raw }}
            {# Otros campos del formulario #}
            <button type="submit">Enviar</button>
        </form>
        ```
    *   **Solicitudes AJAX**:
        El token puede enviarse como parte de los datos o en una cabecera personalizada.

3.  **Validación del Token:**
    *   El middleware `GlobalMiddleWare::validateCSRFToken()` valida el token recibido contra el almacenado en sesión usando `hash_equals()` para prevenir ataques de temporización.
    *   Si el token no es válido, la solicitud es rechazada.

**Nota:** En modo debug (`$debug = true`), la validación CSRF puede estar desactivada. Asegúrate de que esté activada en producción.

## Protección XSS (Cross-Site Scripting)

El Cross-Site Scripting (XSS) es una vulnerabilidad que permite a un atacante inyectar scripts maliciosos en páginas vistas por otros usuarios. versaWYS-PHP utiliza Twig como motor de plantillas, el cual tiene auto-escapado activado por defecto, previniendo la ejecución de código malicioso al mostrar contenido del usuario.

-   **Tipos de XSS:**
    1. Reflejado
    2. Almacenado
    3. Basado en DOM

**Ejemplo seguro en Twig:**
```twig
<p>{{ comentario }}</p> {# Escapado automáticamente #}
```

**¡Precaución!** Si usas `|raw` en Twig, asegúrate de que el contenido es seguro y está sanitizado.

**Validación y Sanitización:**
- Valida siempre los datos de entrada.
- Sanitiza datos si van a insertarse en contextos especiales (atributos, JS, URLs, etc).
- Considera el uso de Content Security Policy (CSP) para reforzar la defensa.

## Prevención de Inyección SQL

La Inyección SQL ocurre cuando datos del usuario se insertan sin control en consultas SQL, permitiendo manipular la base de datos. versaWYS-PHP utiliza RedBeanPHP como ORM, que emplea consultas parametrizadas y evita la concatenación directa de datos.

-   **Siempre usa métodos del ORM** para operaciones CRUD.
-   **Evita SQL crudo**. Si es imprescindible, usa parámetros y/o escapa los datos cuidadosamente.

**Ejemplo seguro:**
```php
$user = R::findOne('users', ' username = ? AND password_hash = ? ', [ $usuario, $hash ]);
```

**Validación y Sanitización:**
- Valida tipo, longitud y formato de los datos antes de enviarlos a la base de datos.
- Sanitiza los datos según el contexto.

Con estos principios básicos, puedes reducir drásticamente el riesgo de las vulnerabilidades más comunes en aplicaciones web desarrolladas con versaWYS-PHP.
