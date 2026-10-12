# Principios Básicos de Seguridad en versaWYS-PHP

La seguridad es fundamental en el desarrollo de aplicaciones web. versaWYS-PHP proporciona mecanismos integrados para ayudarte a proteger tu aplicación, pero la seguridad final depende también de las buenas prácticas del desarrollador. Esta guía resume las amenazas más comunes y cómo mitigarlas usando el framework.

---

## 1. Protección CSRF (Cross-Site Request Forgery)

El ataque CSRF busca que un usuario autenticado realice acciones no deseadas en una aplicación. versaWYS-PHP implementa protección CSRF mediante tokens sincronizadores:

- **Generación del token:**
  - Se crea un token único y secreto por sesión (`$_SESSION['csrf_token']`).
  - Usa la función `versaWYS\kernel\helpers\Functions::generateCSRFToken()`.
- **Inclusión del token:**
  - En formularios HTML:
    ```php
    <?php echo versaWYS\kernel\helpers\Functions::csrf_field(); ?>
    ```
  - En Twig:
    ```twig
    <form method="post" action="/ruta/sensible">
        {{ csrf_field() | raw }}
        <!-- Otros campos -->
    </form>
    ```
  - En AJAX: envía el token como dato o cabecera personalizada.
- **Validación:**
  - El middleware `GlobalMiddleWare::validateCSRFToken()` compara el token recibido con el de sesión usando `hash_equals()`.
  - Si no coincide, la solicitud es rechazada.

> **Nota:** En modo debug, la validación CSRF puede estar desactivada. Actívala siempre en producción.

---

## 2. Protección XSS (Cross-Site Scripting)

El XSS permite a un atacante inyectar scripts maliciosos en páginas vistas por otros usuarios. versaWYS-PHP utiliza Twig, que tiene auto-escapado activado por defecto.

- **Ejemplo seguro en Twig:**
  ```twig
  <p>{{ comentario }}</p> {# Escapado automáticamente #}
  ```
- **Precaución:** Si usas `|raw`, asegúrate de que el contenido es seguro y está sanitizado.
- **Buenas prácticas:**
  - Valida y sanitiza siempre los datos de entrada.
  - Sanitiza datos para contextos especiales (atributos, JS, URLs).
  - Considera usar Content Security Policy (CSP) para reforzar la defensa.

---

## 3. Prevención de Inyección SQL

La inyección SQL ocurre cuando datos del usuario se insertan sin control en consultas SQL. versaWYS-PHP utiliza VersaORM, que emplea consultas parametrizadas.

- **Siempre usa métodos del ORM** para operaciones CRUD.
- **Evita SQL crudo**. Si es imprescindible, usa parámetros y/o escapa los datos cuidadosamente.

**Ejemplo seguro:**
```php
// Dentro de un modelo (VersaORMModel)
$user = $this->getRow('SELECT * FROM versa_users WHERE email = ?', [$email]);
```

- Valida tipo, longitud y formato de los datos antes de enviarlos a la base de datos.
- Sanitiza los datos según el contexto.

---

## 4. Otras Buenas Prácticas

- Usa HTTPS en producción.
- No expongas información sensible en mensajes de error.
- Mantén tu archivo `config.json` fuera del control de versiones y restringe su acceso.
- Usa contraseñas seguras y cambia las claves por defecto.
- Actualiza dependencias del framework y librerías externas regularmente.

---

Con estos principios y las herramientas integradas de versaWYS-PHP, puedes reducir drásticamente el riesgo de las vulnerabilidades más comunes en aplicaciones web.

¿Dudas? Consulta la [Guía de Configuración](./CONFIGURATION.md), la [Guía de Enrutamiento](./Enrutamiento.md) o la [Interfaz de Línea de Comandos (CLI)](./LineaDeComandos.md) para más detalles sobre seguridad y buenas prácticas.

## Protecciones que el framework aplica por defecto

- **CSRF automático.** Toda ruta `POST`, `PUT`, `PATCH` o `DELETE` valida el token CSRF sin declararlo. En formularios usa `{{ csrf_field()|raw }}`; `versaFetch` lo envía solo en la cabecera `X-CSRF-Token`. Para un endpoint que autentica de otra forma (por ejemplo un webhook firmado) decláralo con `->withoutCsrf()`:

  ```php
  Router::post('/webhooks/pagos', [PagosController::class, 'recibir'])->withoutCsrf();
  ```

- **El modo debug no relaja la seguridad.** CSRF y controles de rol siguen activos; lo único que cambia es el detalle de los errores.
- **Listados sin SQL del cliente.** `getParamsPaginate()` devuelve la condición con marcadores `?` y sus valores en `bindings`. El cliente solo puede filtrar por las columnas que el controlador autoriza:

  ```php
  $params = $this->getParamsPaginate($request, ['nombre', 'email'], allowedFilters: ['estado']);
  $qb->whereRaw($params['filter'], $params['bindings'])->orderByRaw($params['order']);
  ```

- **Sesión.** Al autenticar se renuevan el id de sesión y el token CSRF; al cerrar sesión el JWT queda revocado aunque alguien conserve la cookie.
- **Subida de archivos.** `File::getType()` devuelve el tipo detectado en el contenido, no el que declara el navegador, y ningún archivo puede guardarse con extensión ejecutable (`.php`, `.phtml`, `.phar`…) ni como archivo oculto. Aun así, valida siempre contra una lista de tipos permitidos antes de mover el archivo.
- **Rate limiting.** El login se limita por IP y por cuenta; las reglas están en `rate_limiting` de `config.json`.
- **Aislamiento por empresa.** Usa siempre `$this->empresaSelected['id']` de la sesión; nunca un id de empresa enviado por el cliente.
