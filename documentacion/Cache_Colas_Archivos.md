# Caché, colas y archivos

Tres servicios del kernel con un almacén simple por defecto (disco y base de datos) y sin dependencias nuevas.

## Caché: `Cache`

```php
use versaWYS\kernel\Cache;

$resumen = Cache::remember("resumen_{$idEmpresa}", 300, fn() => $modelo->resumen($idEmpresa));

Cache::forget("resumen_{$idEmpresa}");   // cuando cambian los datos
```

`get($clave, $porDefecto)`, `set($clave, $valor, $segundos)` (0 = no vence), `has`, `remember`, `forget` y `flush`.

- Guarda valores que JSON puede representar: escalares y arreglos. Un modelo se guarda como `->export()`.
- La clave incluye la empresa cuando el dato es de una empresa: la caché es una sola para toda la aplicación.

| `config.json` | |
|---|---|
| `cache.driver` | `file` (por defecto) o `redis` (usa la conexión de `redis`) |
| `cache.dir` | carpeta de los archivos, por defecto `versaWYS/cache/data/` |

## Colas: `Queue`

Lo que no tiene que hacer esperar al usuario (correos, reportes, llamadas a otros servicios) se encola y lo ejecuta un proceso aparte. `php versaCLI job:make:EnviarBienvenida` crea la clase del trabajo.

```php
namespace app\jobs;

use app\services\MailService;
use versaWYS\kernel\Job;

final class EnviarBienvenida implements Job
{
    public function __construct(private readonly MailService $mail) {}

    public function handle(array $data): void
    {
        $this->mail->enviarTemplate(['email' => $data['email']], 'Bienvenido', 'mail/bienvenida', $data);
    }
}
```

```php
Queue::push(EnviarBienvenida::class, ['email' => $email]);
Queue::push(RecordarPago::class, ['id' => $id], delay: 3600, tries: 5);
```

- Los datos se guardan como JSON: pasa ids y textos, no objetos. El trabajo vuelve a leer lo que necesite.
- Si `handle()` lanza una excepción, se reintenta: espera 1 minuto × intento, hasta `tries` (3 por defecto). Agotados, queda fallido.
- El trabajo se crea con el contenedor: su constructor recibe servicios igual que un controlador.

**La tabla.** La migración `m20261015120000_versa_jobs` crea `versa_jobs` con `php versaCLI migrate:up`. Un proyecto existente copia ese archivo a su `app/migrations/`.

**El worker.**

| Comando | Para qué |
|---|---|
| `php versaCLI app:queue:work` | Procesa sin fin; se deja corriendo con systemd o supervisor |
| `php versaCLI app:queue:work --stop-when-empty` | Procesa hasta vaciar la cola; sirve en un cron cada minuto |
| `php versaCLI app:queue:work correos` | Otra cola: `Queue::push(..., queue: 'correos')` |
| `php versaCLI app:queue:failed` | Trabajos fallidos con su último error |
| `php versaCLI app:queue:retry 12` / `all` | Los vuelve a encolar |

Lo más simple es procesarla como una [tarea programada](./Tareas_Programadas.md), en `app/schedule.php`:

```php
$schedule->command('app:queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
```

Un trabajo tomado por un worker que murió se vuelve a tomar a los 10 minutos.

**En las pruebas**, `queue.driver: "sync"` ejecuta el trabajo en el momento del `push`.

## Archivos: `Storage`

Archivos de la aplicación, fuera de lo que sirve el servidor web (`versaWYS/storage`).

```php
use versaWYS\kernel\Storage;

Storage::put("facturas/{$idEmpresa}/{$folio}.pdf", $pdf);
$pdf = Storage::get("facturas/{$idEmpresa}/{$folio}.pdf");   // null si no existe
Storage::exists($ruta); Storage::delete($ruta); Storage::files("facturas/{$idEmpresa}");
```

- Las rutas son relativas a `storage.root` y nunca salen de ahí: `..`, rutas absolutas y bytes nulos lanzan `InvalidArgumentException`. Aun así, no armes la ruta con un nombre que mandó el usuario sin validarlo.
- Para entregar un archivo, primero revisa el permiso (`Tenant::ensure`, `Gate::authorize`) y después `readfile(Storage::path($ruta))`.
- La escritura es atómica: nadie lee un archivo a medio escribir.
