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
| `required` | El campo existe y no está vacío |
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

Sin `nullable`, un campo que no llega es un error ("El campo X no existe"). Una regla mal escrita también es un error, así que un dato nunca pasa sin validar.

## Datos anidados

Las reglas se aplican a claves de primer nivel. Para validar datos anidados, aplánalos antes, como hace `ConfigSettingsMiddleware`:

```php
$plano = ['mail_port' => $params['mail']['port'] ?? null];
$errores = Functions::validateParams($plano, ['mail_port' => 'required|int:1-65535']);
```
