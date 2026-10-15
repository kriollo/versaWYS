# Pruebas de la aplicación

`versaWYS\kernel\Testing\TestClient` prueba un módulo completo sin levantar un servidor. Cada petición recorre lo mismo que una real, en el mismo proceso y en milisegundos:

- cabeceras de seguridad, rutas, middlewares y controlador;
- sesión, CSRF, base de datos y plantillas.

Se comporta como un navegador:

- guarda las cookies entre peticiones, incluida la de sesión;
- en cada POST, PUT, PATCH y DELETE manda el token CSRF de la sesión, como el panel. La protección sigue activa: con `withoutCsrfToken()` se prueba que una ruta lo exige.

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

El segundo argumento es la ruta de otro `config.json` (se pasa como `VERSAWYS_CONFIG`). Úsalo para que las pruebas usen una base de datos propia y nunca la de desarrollo. `versaWYS/tests/AppTest.php` del esqueleto prepara una SQLite desechable con las migraciones y los seeders.

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

## Aserciones

`assertStatus`, `assertOk`, `assertRedirect($destino)`, `assertHeader($nombre, $valor)`, `assertSee($texto)`, `assertDontSee`, `assertJson([...])` (contiene esos pares), `assertJsonPath('data.0.nombre', 'x')`.

Para el resto, `$respuesta->status`, `->body`, `->header('Location')` y `->json('ruta.al.valor')`.

## Qué conviene probar en cada módulo

- **Que un usuario de otra empresa no ve ni modifica los datos:** dos `login()` con usuarios de empresas distintas. Es el error que más daño hace y el que menos se nota a mano.
- Que la validación responde 422 con los errores por campo, y no 500.
- Que las rutas que modifican datos exigen CSRF.
- Que un usuario sin el permiso no entra: `onlyUserWithAccess`, `onlyAdmin`, `onlySuperAdmin`.
