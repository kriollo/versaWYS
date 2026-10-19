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

## Estados de la cuenta

`checkSession` (de `AuthMiddleware`) revisa en cada petición, no solo al iniciar sesión. Si el admin desactiva una cuenta o suspende una empresa, quien ya estaba dentro sale en su siguiente petición:

| Estado | Al iniciar sesión | Con la sesión abierta |
|---|---|---|
| **Cuenta desactivada** (`versa_users.status = 0`) | Rechazado, con el mismo mensaje que una clave incorrecta. | Se cierra. Una página vuelve al login con «Tu cuenta está desactivada»; la API responde 403. |
| **Empresa suspendida** (`empresas.estado = 0`) | Rechazado: «La empresa está suspendida». | Se cierra igual, con ese motivo. No afecta al superadministrador, que administra las empresas. |
| **Contraseña vencida** (`expiration_pass` en el pasado) | Entra. | Solo puede usar su perfil y el cambio de contraseña: las páginas llevan a `/perfiluser` y la API responde 403 con `password_expired: true`. Al cambiarla, recupera el acceso. |

El cierre revoca el token de la sesión, igual que el logout.

### Un bloqueo propio

Para otro estado (un plan vencido, términos sin aceptar), extiende `checkSession` en tu `AuthMiddleware`: primero las revisiones del kernel y después la tuya. Así queda en todas las rutas que ya usan `checkSession`.

```php
<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\GlobalMiddleware;
use versaWYS\kernel\Output;
use versaWYS\kernel\Response;

class AuthMiddleware extends GlobalMiddleware
{
    public function checkSession(): bool|array
    {
        $sesion = parent::checkSession();
        if ($sesion !== true) {
            return $sesion;
        }

        global $request;

        $empresa = self::getLoadedUserData()['empresaSelected'] ?? [];
        $enPlanes = str_starts_with((string) parse_url($request->getUrl(), PHP_URL_PATH), '/admin/planes');
        if (($empresa['plan_vence'] ?? '9999-12-31') < date('Y-m-d') && !$enPlanes) {
            // Como la contraseña vencida: la página lleva a donde se resuelve y la API no responde datos
            if (!$request->isApiCall()) {
                Response::redirect('/admin/planes');
                Output::halt();
            }

            return ['success' => 0, 'plan_vencido' => true, 'message' => 'El plan de la empresa venció', 'code' => 402];
        }

        return true;
    }
}
```

- **Deja libre la pantalla que lo resuelve** (`/admin/planes`): si no, la redirección no termina nunca.
- **Bloquea también la API**, no solo las páginas: si no, el bloqueo se salta llamando a la API directamente.
- **Pruébalo con sesión abierta**, no solo en el login (ver `versaWYS/tests/CuentasTest.php`).

## En las pruebas

```php
$olga->deleteJson("/admin/tableros/api/notas/{$nota}")->assertStatus(404);
```

Ver [Pruebas.md](Pruebas.md).
