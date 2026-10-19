# Pruebas de la aplicación

`versaWYS\kernel\Testing\TestClient` prueba un módulo completo sin levantar un servidor. Cada petición recorre lo mismo que una real, en el mismo proceso y en milisegundos:

- cabeceras de seguridad, rutas, middlewares y controlador;
- sesión, CSRF, base de datos y plantillas.

Se comporta como un navegador:

- guarda las cookies entre peticiones, incluida la de sesión;
- en cada POST, PUT, PATCH y DELETE manda el token CSRF de la sesión, como el panel. La protección sigue activa: con `withoutCsrfToken()` se prueba que una ruta lo exige.

## Base de datos de prueba: `TestDatabase`

`versaWYS\kernel\Testing\TestDatabase` crea una SQLite desechable con las migraciones y los seeders del proyecto, arranca `TestClient` con ella y la borra al terminar. Nunca toca la base de desarrollo.

```php
use versaWYS\kernel\Testing\TestDatabase;

beforeAll(fn() => TestDatabase::boot());

it('otra empresa no ve los tableros', function (): void {
    $admin = TestDatabase::login(TestDatabase::user('admin', TestDatabase::empresa()));
    $otra = TestDatabase::login(TestDatabase::user('admin', TestDatabase::empresa()));
    // ...
});
```

| Método | Qué hace |
|---|---|
| `boot()` | Prepara la base (una sola por proceso, compartida por toda la suite) y devuelve un cliente |
| `user($rol = 'admin', $empresa = 1)` | Crea un usuario activo con correo único; devuelve el correo |
| `empresa($nombre)` | Crea una empresa con RUT válido; devuelve su id |
| `login($correo)` | Un cliente con la sesión de ese usuario, por el login real del panel |
| `pdo()` | Conexión directa, para preparar datos o revisar lo guardado |
| `configPath()` | El `config.json` de la prueba, para correr `versaCLI` contra la misma base |

## Datos de prueba: `Factory`

Una prueba necesita datos: diez órdenes para probar la paginación, una orden de otra empresa para probar el aislamiento. Escribir cada `INSERT` a mano en cada prueba es largo y se rompe cuando la tabla suma una columna obligatoria.

Una *factory* son los **valores por defecto de una fila de la tabla, escritos una sola vez**. Cada prueba pide filas y cambia solo lo que le importa:

- `Factory::create('ordenes_compra')`: una orden con los valores por defecto.
- `Factory::create('ordenes_compra', ['estado' => 0])`: una orden anulada; el resto, por defecto.
- `Factory::createMany('ordenes_compra', 20)`: veinte órdenes distintas.

Si mañana la tabla suma una columna obligatoria, se agrega en la factory y todas las pruebas siguen funcionando.

La definición vive en un archivo propio:

```php
// versaWYS/tests/factories/OrdenesCompra.php (php versaCLI factory:make:OrdenesCompra; versaMODULE ya la crea)
Factory::define('ordenes_compra', static fn(int $n): array => [
    'id_empresa' => 1,
    'nombre' => "Orden {$n}",
]);
```

```php
use versaWYS\kernel\Testing\Factory;

$id = Factory::create('ordenes_compra', ['id_empresa' => $empresa]);         // devuelve el id
$ids = Factory::createMany('ordenes_compra', 20, ['id_empresa' => $empresa]);
$datos = Factory::make('ordenes_compra');                                     // sin guardar
```

- **Valores únicos:** `$n` no se repite.
- **Fechas:** `created_at` y `updated_at` se completan solas si la tabla las tiene.
- **Carga automática:** las definiciones de `versaWYS/tests/factories/` se cargan solas.

`versaWYS/tests/factories/Empresas.php` y `versaWYS/tests/EmpresasTest.php` son un ejemplo completo: crean 15 empresas en dos líneas y prueban la búsqueda, la paginación y el filtro por estado del listado.

`TestDatabase::user()` y `TestDatabase::empresa()` hacen lo mismo para las tablas del panel; las factories son para las tablas de tu proyecto.

Todos los usuarios usan la contraseña `TestDatabase::PASSWORD`. Como la base es una sola para toda la suite, cada prueba crea sus propios usuarios y empresas: así no depende de lo que hagan las demás. `php versaCLI test:make:Clientes` crea un archivo de pruebas listo para empezar.

## Un ejemplo

```php
use versaWYS\kernel\Testing\TestClient;

it('crea un tablero y lo lista', function (): void {
    $app = TestClient::boot(dirname(__DIR__, 2), '/ruta/al/config.testing.json');
    $app->login('admin@ejemplo.cl', 'clave');

    $id = $app->postJson('/admin/tableros/api/tableros', ['nombre' => 'Demo'])
        ->assertStatus(201)
        ->json('id');

    $app->getJson('/admin/tableros/api/tableros')
        ->assertOk()
        ->assertJsonPath('data.0.id', $id);
});

it('valida los datos', function (): void {
    $app = TestClient::boot(dirname(__DIR__, 2));
    $app->login('admin@ejemplo.cl', 'clave');

    $app->postJson('/admin/tableros/api/tableros', [])
        ->assertStatus(422)
        ->assertJsonPath('errors.nombre', 'El campo nombre es obligatorio');
});
```

`TestClient::boot()` arranca la aplicación **una vez por proceso**: configuración, rutas, Twig y base de datos. Las llamadas siguientes devuelven un navegador nuevo, sin cookies, sobre la misma aplicación.

El segundo argumento es la ruta de otro `config.json` (se pasa como `VERSAWYS_CONFIG`). Lo más simple es no pasarlo y usar `TestDatabase`, como `versaWYS/tests/AppTest.php` del esqueleto.

## Peticiones

| Método | Para |
|---|---|
| `get($uri, $query)` | Una página, como el navegador |
| `getJson($uri, $query)` | Una API GET que responde JSON |
| `post($uri, $datos)` | Un formulario: agrega `_csrf_token` |
| `postJson`, `putJson`, `patchJson`, `deleteJson` | APIs con cuerpo JSON, como `versaFetch`: el token va en `X-CSRF-Token` |
| `login($correo, $clave)` | Inicia sesión por el formulario real del panel |
| `withHeaders([...])` | Cabeceras extra para las peticiones siguientes |
| `withoutCsrfToken()` | Las peticiones siguientes no mandan el token |

## Otra configuración: `TestClient::withConfig`

Para probar cómo se comporta la aplicación con otros valores de `config.json`, sin otro archivo:

```php
use versaWYS\kernel\Testing\TestClient;

it('con hide_default, /admin deja de existir', function (): void {
    TestClient::withConfig(['admin' => ['custom_url' => 'gestion', 'hide_default' => true]], function (): void {
        TestDatabase::boot()->get('/admin/login')->assertStatus(404);
    });
});
```

Solo cambian las claves indicadas, y al terminar la configuración vuelve a quedar como estaba, aunque la prueba falle. Lo que se lee al arrancar (la conexión a la base, Twig) no cambia. `versaWYS/tests/ConfiguracionTest.php` prueba así la URL del panel, CORS, la CSP, el websocket y los enlaces firmados.

## Aserciones

`assertStatus`, `assertOk`, `assertRedirect($destino)`, `assertHeader($nombre, $valor)`, `assertSee($texto)`, `assertDontSee`, `assertJson([...])` (contiene esos pares), `assertJsonPath('data.0.nombre', 'x')`.

Para el resto, `$respuesta->status`, `->body`, `->header('Location')` y `->json('ruta.al.valor')`.

## Qué conviene probar en cada módulo

- **Que un usuario de otra empresa no ve ni modifica los datos:** dos `login()` con usuarios de empresas distintas. Es el error que más daño hace y el que menos se nota a mano.
- Que la validación responde 422 con los errores por campo, y no 500.
- Que las rutas que modifican datos exigen CSRF.
- Que un usuario sin el permiso no entra: `onlyUserWithAccess`, `onlyAdmin`, `onlySuperAdmin`, y que `Gate::authorize` responde 403.
- Que un `int $id` con texto (`/tableros/abc`) responde 404.
- Si el módulo bloquea algo (un plan vencido, una cuenta suspendida): que también bloquea la API, y con la sesión ya abierta, no solo al iniciar sesión.

Con `queue.driver: "sync"` en el `config.json` de la prueba, `Queue::push` ejecuta el trabajo en el acto y la prueba puede revisar su efecto. Para probar un trabajo que no debe ejecutarse, deja `database` y revisa la tabla `versa_jobs`.

`versaTodo` (la app de ejemplo hecha con el framework) tiene una suite completa de un módulo en `versaWYS/tests/TablerosTest.php`. Usa una SQLite desechable con las migraciones y seeders del proyecto.
