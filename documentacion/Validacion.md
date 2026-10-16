# Validación de datos

`Functions::validateParams($params, $reglas)` revisa los datos de una petición y devuelve los errores por campo. Nunca modifica los valores: lo que se guarda es lo que envió el cliente, y el escape se hace al mostrar.

## Uso en un middleware

```php
use versaWYS\kernel\helpers\Functions;

public function validarEmpresa(): true|array
{
    global $request;

    $errores = Functions::validateParams($request->getAllParams(), [
        'nombre' => 'required|max:120',
        'email' => 'required|email',
        'rut' => 'required|rut',
        'estado' => 'required|in:activo,inactivo',
        'telefono' => 'nullable|numeric',
    ]);

    if ($errores !== []) {
        return ['success' => 0, 'message' => 'Los datos enviados no son correctos', 'errors' => $errores, 'code' => 422];
    }

    return true;
}
```

El arreglo con `code: 422` corta la petición y responde HTTP 422 con los errores; `true` deja seguir al controlador. El resultado es `['campo' => 'mensaje']`, con un solo mensaje por campo (la primera regla que falla).

## Reglas

Se combinan con `|` y se evalúan en orden.

| Regla | Valida |
|---|---|
| `required` | El campo existe y no está vacío ("El campo X es obligatorio") |
| `nullable` | El campo puede faltar o venir vacío; si viene con valor, se aplican las demás reglas |
| `string` | Texto o número; nunca arreglo, objeto ni booleano |
| `numeric` | Un número, entero o decimal (`"12.5"` incluido) |
| `int` / `integer` | Un entero. `int:1-100` exige un rango, `int:1,2,3` uno de esos valores, `int:positive` mayor que 0 |
| `boolean` | `true`/`false`, `1`/`0`, `"true"`/`"false"`, `"on"`/`"off"` (casillas de formulario) |
| `in:a,b,c` | Uno de los valores listados, comparados como texto |
| `min:N` / `max:N` | Largo mínimo o máximo en **caracteres** ("José" mide 4) |
| `email` | Un correo válido |
| `url` | Una URL válida |
| `rut` | Un RUT chileno válido, con dígito verificador |
| `date` | Una fecha que PHP puede interpretar |
| `time` | Una hora `HH:MM` o `HH:MM:SS` |
| `array` | Un arreglo, o un texto con un arreglo JSON |

Sin `nullable`, un campo que no llega es un error ("El campo X es obligatorio"). Una regla mal escrita también es un error, así que un dato nunca pasa sin validar.

## Datos anidados y arreglos

`Validator::make()` acepta rutas con punto y `*` para cada elemento de un arreglo. Las reglas pueden ir en texto o en arreglo, y un closure es una regla propia: devuelve el mensaje de error, o `null` si el valor es válido.

```php
use versaWYS\kernel\Validator;

$v = Validator::make($request->getAllParams(), [
    'cliente.email' => 'required|email',
    'items' => 'required|array',
    'items.*.cantidad' => ['required', 'int:positive'],
    'items.*.sku' => ['required', static fn(string $campo, mixed $valor): ?string =>
        $productos->existe($valor) ? null : "El producto {$valor} no existe"],   // $productos: tu modelo
], [
    'items.*.cantidad.required' => 'Indica la cantidad',
]);

if ($v->fails()) {
    return ['success' => 0, 'message' => 'Los datos enviados no son correctos', 'errors' => $v->errors(), 'code' => 422];
}
$datos = $v->validated(); // solo los campos con reglas, tal como llegaron
```

Los errores usan la ruta completa: `['items.1.cantidad' => 'Indica la cantidad']`. Un mensaje propio se busca por `campo.regla`, después por la ruta con `*` y al final por el campo solo. `Functions::validateParams()` es `Validator::make(...)->errors()`.

## Form Requests

Para no repetir las reglas en un middleware, se escriben en una clase y el controlador la recibe como parámetro. `php versaCLI request:make:GuardarTablero` la crea. El contenedor la valida antes de llamar al método: si falla, responde 422 con `errors` y el método no se ejecuta.

```php
namespace app\requests;

use versaWYS\kernel\FormRequest;

final class GuardarTablero extends FormRequest
{
    public function rules(): array
    {
        return ['nombre' => 'required|max:120', 'descripcion' => 'nullable|string'];
    }

    public function messages(): array
    {
        return ['nombre.required' => 'Ponle un nombre al tablero'];
    }

    // Opcional: false responde 403
    public function authorize(): bool
    {
        return true;
    }
}
```

```php
public function crear(GuardarTablero $datos): array
{
    $id = $this->modelo->guardar($this->idEmpresa, $datos->validated());
    // $datos->input('nombre') para un campo
}
```

Se combina con los parámetros de la ruta en cualquier orden: `editar(GuardarTablero $datos, int $id)`.

Los permisos (quién puede hacer qué) se explican en [Autorizacion.md](Autorizacion.md).
