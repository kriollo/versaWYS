# Contenedor de dependencias

Los controladores y middlewares se crean con el contenedor del kernel. Es optativo: un controlador sin constructor tipado funciona igual que siempre. Sirve para dos cosas:

- **Pedir lo que se necesita en vez de crearlo.** El código queda más corto y el servicio se puede reemplazar.
- **Probar sin efectos reales**, como enviar correos o llamar a un banco: en la prueba el contenedor entrega un doble.

## En un controlador

```php
class TablerosController extends GlobalControllers
{
    // El contenedor crea el modelo
    public function __construct(private readonly Tableros $modelo)
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    // Los parámetros con una clase vienen del contenedor; los demás son los de la ruta, en orden.
    // int $id recibe el número: /tableros/abc responde 404 sin llegar aquí.
    public function ver(int $id, Request $request): array
    {
        return ['success' => 1, 'tablero' => $this->modelo->buscar($id, $this->idEmpresa)];
    }
}
```

Lo mismo vale en los métodos de un middleware.

## Qué entrega el kernel

| Tipo | Qué es |
|---|---|
| `Request` | La petición actual (la misma que `global $request`) |
| `Session` | La sesión actual |
| `versaTwig` / `Twig\Environment` | Las plantillas |
| `Cookie` | Las cookies |
| `VersaORM\VersaORM` | La conexión a la base de datos |
| `Container` | El contenedor mismo |

Cualquier otra clase con constructor tipado se crea sola, resolviendo sus dependencias.

## Registrar servicios: `app/container.php`

Solo hace falta para interfaces, para objetos únicos por proceso o para valores que no son clases:

```php
return static function (Container $container): void {
    $container->singleton(Pagos::class, static fn(): Pagos => new PagosWebpay(getenv('WEBPAY_KEY') ?: ''));
    $container->bind(Reloj::class, static fn(): Reloj => new RelojDelSistema());
};
```

- `bind`: un objeto nuevo cada vez que se pide.
- `singleton`: uno solo por proceso.
- `instance`: registra uno ya creado.

## En pruebas

```php
$admin = TestClient::boot($raiz);
$admin->container()->instance(MailService::class, $correoFalso);
try {
    $admin->postJson('/admin/configuracion/testMail')->assertOk();
} finally {
    $admin->container()->forget(MailService::class); // de vuelta al servicio real
}
```

`versaWYS/tests/AppTest.php` tiene el ejemplo completo con un `MailService` que registra lo que enviaría.
