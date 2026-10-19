# Principios Básicos de Seguridad en versaWYS-PHP

versaWYS aplica varias protecciones por defecto, pero la seguridad final depende también de cómo se escribe cada controlador y cada plantilla. Esta guía resume qué hace el framework y qué queda en manos del proyecto.

La regla general: **la entrada se guarda tal como llega y se escapa al mostrarla**. Desde el kernel 3.0, `getAllParams()` no reescribe los datos; Twig y Vue escapan por defecto y el SQL va siempre con parámetros.

---

## 1. CSRF

- **Automático.** Toda ruta `POST`, `PUT`, `PATCH` o `DELETE` valida el token CSRF sin declararlo. `GET` y `QUERY` no lo exigen porque no cambian estado.
- **En formularios:** `{{ csrf_field()|raw }}`. **En AJAX:** `versaFetch` lo envía en la cabecera `X-CSRF-Token`.
- **Excepción:** un endpoint que autentica de otra forma (por ejemplo un webhook firmado) se declara con `->withoutCsrf()`:

  ```php
  Router::post('/webhooks/pagos', [PagosController::class, 'recibir'])->withoutCsrf();
  ```

- **El modo debug no relaja la seguridad.** CSRF y controles de rol siguen activos; lo único que cambia es el detalle de los errores.

---

## 2. XSS

- **Twig escapa por defecto:** `{{ comentario }}` es seguro.
- **`|raw` solo con contenido que no escribe un usuario** (por ejemplo `csrf_field()` o un SVG pasado por `sanitizeSvg`). Nunca con nombres, descripciones o textos guardados desde un formulario.
- **Datos en atributos:** `value="{{ datos|json_encode|e('html_attr') }}"`. No `json_encode|raw` dentro de comillas: una comilla en el dato rompe el atributo.
- **JSON dentro de `<script>`** (por ejemplo JSON-LD): `json_encode` con `JSON_HEX_TAG`, para que un `</script>` en el dato no cierre la etiqueta.
- **Vue:** `{{ }}` escapa. `v-html` solo con HTML propio o pasado por `sanitizeSvgContent()`.
- **CSP con nonce** (`csp.nonce: true`): un script inyectado no corre aunque el escape falle en algún punto.
  - Cada `<script>` de las plantillas lleva `nonce="{{ csp_nonce() }}"`.
  - No se usan manejadores inline (`onclick=`, `onchange=`); en su lugar, `data-history-back`, `data-auto-submit` y `data-reload`.
- **Sin `eval`** (`csp.unsafe_eval: false`): ningún componente Vue usa `template:` en texto, solo `.vue` compilados o funciones `render`.
- `versaWYS/tests/csp-check.mjs` recorre el panel en Chrome y lista cada bloqueo de CSP. Ver [Configuración](./CONFIGURATION.md#verificar-la-csp-en-el-navegador).

---

## 3. Inyección SQL

- **Usa el query builder o parámetros.** Nunca concatenes datos de la petición en el SQL:

  ```php
  // Dentro de un modelo (VersaORMModel)
  $user = $this->qb()->where('email', '=', $email)->first();
  $filas = $this->exec('SELECT id, nombre FROM empresas WHERE estado = ?', [$estado]);
  ```

- **Listados paginados.** `getParamsPaginate()` devuelve la condición con marcadores `?` y sus valores en `bindings`. El cliente solo puede filtrar por las columnas que el controlador declara en `allowedFilters`; el resto se ignora:

  ```php
  $params = $this->getParamsPaginate($request, ['nombre', 'email'], allowedFilters: ['estado' => 'empresas.estado']);
  $qb->whereRaw($params['filter'], $params['bindings'])->orderByRaw($params['order']);
  ```

  Las claves de `allowedFilters` deben ser exactamente las que envía el frontend (`estado=true` o `empresas.estado=true`).
- **Orden:** `order` solo acepta `columna [asc|desc]`.
- **Ids de la ruta o de la sesión**, por ejemplo `$this->empresaSelected['id']`, también van como parámetro o con cast a `int`.

---

## 4. Tipos de la entrada (type juggling)

Con JSON, el cliente elige el tipo de cada campo: puede mandar `true`, `0`, `null` o un arreglo donde el código espera un texto. PHP convierte tipos al comparar y al hacer un cast, y eso abre huecos:

| Código | Con lo que manda un atacante |
|---|---|
| `if ($token == $esperado)` | `0 == "abc"` era verdadero hasta PHP 7; `"0e1" == "0e2"` sigue siéndolo (hashes «mágicos») |
| `in_array($rol, $roles)` | sin `strict`, compara con `==` |
| `(int) $request->get('id')` | `"1e3"` es 1000 y `"0x1A"` es 0: otra URL llega al mismo registro |
| `(string) $request->get('nombre')` | un arreglo se vuelve el texto `"Array"` |
| `hash_equals($a, $request->get('token'))` | un arreglo o un número lanzan `TypeError`: un 500 en vez de un 403 |

Lo que hace el framework y lo que conviene hacer:

- **Compara siempre con `===`, `in_array(..., true)` y `hash_equals()`** para secretos. mago lo exige (`composer mago:lint`).
- **Lee la entrada con su tipo:** `$request->string('nombre')` y `$request->integer('cantidad')` devuelven el valor por defecto si llega otro tipo. Sobre un arreglo ya leído, `Functions::text($params['nombre'] ?? null)`.
- **Declara el tipo de los parámetros de ruta:** con `int $id`, el contenedor solo acepta dígitos y cualquier otra cosa (`1e3`, `0x1A`, `1.5`) responde 404. Con `int|string $id` y un cast después, esa protección se pierde.
- **Las reglas de validación** (`min`, `max`, `email`, `int`) rechazan un arreglo en vez de medirlo como texto.
- **El token CSRF** solo cuenta si es texto: cualquier otro tipo es un 403, nunca un 500.

`versaWYS/tests/TiposTest.php` prueba los puntos sensibles del panel con `true`, `0`, `null` y arreglos: ninguno acepta la petición ni responde 500. Para un módulo propio, copia ese patrón.

---

## 5. Autenticación y sesión

- **Contraseñas** con `Functions::hash()` (`PASSWORD_DEFAULT`). En el login, si `Functions::needsRehash()` indica un algoritmo o costo anterior, se guarda un hash nuevo: es el único momento en que se tiene la contraseña.
- **Al autenticar** se renuevan el id de sesión y el token CSRF; al cerrar sesión el JWT queda revocado aunque alguien conserve la cookie.
- **Rate limiting:** el login se limita por IP y por cuenta (reglas en `rate_limiting`).
- **Aislamiento por empresa:** usa siempre la empresa de la sesión (`Tenant::id()` o `$this->empresaSelected['id']`), nunca un id de empresa enviado por el cliente. Al buscar por id, `Tenant::ensure($fila)` responde 404 si la fila es de otra empresa.
- **Permisos por acción:** `Gate::authorize('habilidad', $fila)` responde 403. Una habilidad no definida niega. Ver [Autorización](./Autorizacion.md).
- **Validación:** una [Form Request](./Validacion.md#form-requests) impide que el controlador corra con datos inválidos.

---

## 6. Configuración del servidor

| Clave | Para qué |
|---|---|
| `build.base_url` | URL pública con que se arman los enlaces de los correos (recuperar contraseña). Vacía, se deduce de la cabecera `Host`, que un atacante puede falsificar para enviar el enlace a su dominio. **Fíjala en producción.** |
| `trusted_proxies` | IPs o rangos de tus proxies (balanceador, Cloudflare). Solo desde ellos se aceptan `X-Forwarded-For`, `-Proto` y `-Host`; sin esto, cualquiera falsifica su IP y esquiva el rate limiting. |
| `cors.allowed_origins` | Orígenes externos que pueden llamar a la API con credenciales. Vacío por defecto; nunca `*`. |
| `csp.nonce`, `csp.unsafe_eval` | Ver la sección 2. Las instalaciones nuevas nacen con `nonce: true` y `unsafe_eval: false`. |
| `session.user_cookie.secure` | `true` en producción (solo HTTPS). |
| `mail.verify_peer` | Verifica el certificado del SMTP. `false` solo para un servidor interno autofirmado. |

---

## 7. Despliegue

- **`config.json` nunca en git.** Tiene las claves de sesión y la base de datos. Los ajustes sin secretos (CSP, CORS) van en `versaWYS/config.compartida.json`, que se aplica en cada despliegue.
- **`php versaCLI app:check:deploy --production`** revisa en el servidor:
  - la configuración (debug, cookies, CORS);
  - credenciales escritas en el código;
  - archivos sensibles versionados.

  Con `--url=https://tu-sitio` pide además `.env`, `.git/config`, `config.json`, `vendor/` y `logs/` al sitio real, y falla si alguno responde.
- **Subida de archivos:** `File::getType()` devuelve el tipo detectado en el contenido, no el que declara el navegador, y ningún archivo se guarda con extensión ejecutable ni como oculto. Valida igual contra una lista de tipos permitidos.
- **Archivos privados** (facturas, respaldos): guárdalos con `Storage`, en `versaWYS/storage`, fuera de lo que sirve la web, y entrégalos después de revisar el permiso.
- Mantén el kernel al día: `composer update versawys/kernel`. Dependabot abre el PR en cada versión nueva.

---

## 8. Secretos guardados y enlaces firmados

**API keys y tokens de terceros en la base de datos** se guardan cifrados con `Crypt` (AES-256-GCM): si alguien obtiene un respaldo de la base, no obtiene las claves.

```php
use versaWYS\kernel\Crypt;

$guardado = Crypt::encrypt($apiKey);   // también acepta números y arreglos
$apiKey = Crypt::decrypt($guardado);   // excepción si el valor fue alterado o es de otra clave
```

- La clave es `crypt.key` en `config.json` (32 bytes en base64: `php -r "echo base64_encode(random_bytes(32));"`). Si no existe, se deriva de `session.key`.
- **Cambiar cualquiera de las dos hace ilegible lo ya cifrado.** Respáldala junto con la base.
- Para migrar valores guardados en texto plano: `Crypt::isEncrypted($valor)` dice si ya están cifrados.

**Enlaces que vencen** (descargar una factura sin iniciar sesión, confirmar desde un correo) con `SignedUrl`:

```php
use versaWYS\kernel\SignedUrl;

$url = SignedUrl::make("/facturas/{$id}/pdf", 3600);   // vence en una hora
$enlace = $request->getBaseUrl() . $url;               // absoluto, para el correo

Router::get('/facturas/{id}/pdf', [FacturasController::class, 'pdf'])
    ->middleware([[SignedUrl::class, 'check']]);       // 403 si venció o fue alterado
```

La firma cubre la ruta, los parámetros y el vencimiento: cambiar cualquiera la invalida. Quien tenga el enlace entra mientras no venza, así que usa vencimientos cortos.

¿Dudas? Consulta la [Guía de Configuración](./CONFIGURATION.md), la [Guía de Enrutamiento](./Enrutamiento.md) o la [Interfaz de Línea de Comandos](./LineaDeComandos.md).
