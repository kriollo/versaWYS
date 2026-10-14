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

## 4. Autenticación y sesión

- **Contraseñas** con `Functions::hash()` (`PASSWORD_DEFAULT`). En el login, si `Functions::needsRehash()` indica un algoritmo o costo anterior, se guarda un hash nuevo: es el único momento en que se tiene la contraseña.
- **Al autenticar** se renuevan el id de sesión y el token CSRF; al cerrar sesión el JWT queda revocado aunque alguien conserve la cookie.
- **Rate limiting:** el login se limita por IP y por cuenta (reglas en `rate_limiting`).
- **Aislamiento por empresa:** usa siempre `$this->empresaSelected['id']` de la sesión; nunca un id de empresa enviado por el cliente.

---

## 5. Configuración del servidor

| Clave | Para qué |
|---|---|
| `build.base_url` | URL pública con que se arman los enlaces de los correos (recuperar contraseña). Vacía, se deduce de la cabecera `Host`, que un atacante puede falsificar para enviar el enlace a su dominio. **Fíjala en producción.** |
| `trusted_proxies` | IPs o rangos de tus proxies (balanceador, Cloudflare). Solo desde ellos se aceptan `X-Forwarded-For`, `-Proto` y `-Host`; sin esto, cualquiera falsifica su IP y esquiva el rate limiting. |
| `cors.allowed_origins` | Orígenes externos que pueden llamar a la API con credenciales. Vacío por defecto; nunca `*`. |
| `csp.nonce`, `csp.unsafe_eval` | Ver la sección 2. Las instalaciones nuevas nacen con `nonce: true` y `unsafe_eval: false`. |
| `session.user_cookie.secure` | `true` en producción (solo HTTPS). |
| `mail.verify_peer` | Verifica el certificado del SMTP. `false` solo para un servidor interno autofirmado. |

---

## 6. Despliegue

- **`config.json` nunca en git.** Tiene las claves de sesión y la base de datos. Los ajustes sin secretos (CSP, CORS) van en `versaWYS/config.compartida.json`, que se aplica en cada despliegue.
- **`php versaCLI app:check:deploy --production`** revisa en el servidor:
  - la configuración (debug, cookies, CORS);
  - credenciales escritas en el código;
  - archivos sensibles versionados.

  Con `--url=https://tu-sitio` pide además `.env`, `.git/config`, `config.json`, `vendor/` y `logs/` al sitio real, y falla si alguno responde.
- **Subida de archivos:** `File::getType()` devuelve el tipo detectado en el contenido, no el que declara el navegador, y ningún archivo se guarda con extensión ejecutable ni como oculto. Valida igual contra una lista de tipos permitidos.
- Mantén el kernel al día: `composer update versawys/kernel`. Dependabot abre el PR en cada versión nueva.

¿Dudas? Consulta la [Guía de Configuración](./CONFIGURATION.md), la [Guía de Enrutamiento](./Enrutamiento.md) o la [Interfaz de Línea de Comandos](./LineaDeComandos.md).
