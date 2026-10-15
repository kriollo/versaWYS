# Autorización

Dos preguntas distintas:

- **¿Este registro es de la empresa del usuario?** → `Tenant`.
- **¿Este usuario puede hacer esta acción?** → `Gate`.

El acceso a cada pantalla sigue siendo por perfil (`GlobalMiddleware::onlyUserWithAccess`, lo escribe `route:make`).

## Aislamiento por empresa: `Tenant`

```php
use versaWYS\kernel\Tenant;

public function editar(GuardarTablero $datos, int $id): array
{
    $tablero = $this->modelo->buscar($id);
    Tenant::ensure($tablero);   // 404 y fin si no existe o es de otra empresa

    $this->modelo->guardar(Tenant::id(), $datos->validated(), $id);
```

- `Tenant::id()`: la empresa seleccionada por el usuario en sesión (0 sin sesión).
- `Tenant::owns($fila)`: si la fila (arreglo, modelo de VersaORM u objeto) tiene `id_empresa` igual a esa empresa. Otra columna: `owns($fila, 'empresa_id')`.
- `Tenant::ensure($fila)`: responde 404, no 403, para no confirmarle a otra empresa que el registro existe.

Filtrar las consultas por `Tenant::id()` sigue siendo necesario en los listados; `ensure` cubre el acceso por id.

## Permisos por acción: `Gate`

Se definen en un solo lugar, `app/container.php`:

```php
use versaWYS\kernel\Gate;

return static function (Container $container): void {
    // Un superadmin puede todo; null deja decidir a cada habilidad
    Gate::before(static fn(array $usuario): ?bool => $usuario['role'] === 'superadmin' ? true : null);

    Gate::define('notas.eliminar', static fn(array $usuario, array $nota): bool => (int) $nota['id_usuario'] === (int) $usuario['id']);

    // O una clase de política, creada con el contenedor
    Gate::define('facturas.anular', [PoliticaFacturas::class, 'anular']);
};
```

En el controlador:

```php
Gate::authorize('notas.eliminar', $nota);           // 403 y fin si no puede

if (Gate::allows('facturas.anular', $factura)) { ... }
```

Sin usuario en sesión, o con una habilidad que no se definió, la respuesta es no (y la habilidad no definida queda en el log `security`). `Gate::check($usuario, 'habilidad', ...)` decide para otro usuario.

## En las pruebas

```php
$olga->deleteJson("/admin/tableros/api/notas/{$nota}")->assertStatus(404);
```

Ver [Pruebas.md](Pruebas.md).
