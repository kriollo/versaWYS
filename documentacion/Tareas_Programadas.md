# Tareas programadas

Las tareas que se repiten (limpiezas, recordatorios, reportes, la cola de trabajos) se definen en código, en `app/schedule.php`. El servidor necesita **una sola línea** en el crontab, sin importar cuántas tareas haya:

```cron
* * * * * /usr/bin/php /var/www/mi-proyecto/current/versaCLI app:schedule:run >> /var/www/mi-proyecto/current/versaWYS/logs/cron.log 2>&1
```

- **La línea exacta para tu servidor** la muestran `php versaCLI app:schedule:list` y el panel (Configuración → Tareas programadas), con la ruta completa de su PHP.
- **Usa la ruta completa de PHP.** Cron corre con un `PATH` mínimo (`/usr/bin:/bin`). Un PHP instalado en otro lugar, como cPanel (`/opt/cpanel/ea-php83/...`) o mise y asdf en desarrollo, no lo encuentra, y el comando falla sin avisar.
- **La salida va a `versaWYS/logs/cron.log`**, no a `/dev/null`, para que si algo falla quede registrado.

Cada minuto, `app:schedule:run` lanza las tareas que corresponden, cada una en su propio proceso. Así una tarea lenta no atrasa a las demás.

## Definir tareas

```php
// app/schedule.php
use app\cron\CompressVideos;
use app\cron\AgendaRecordatorios;
use versaWYS\kernel\Schedule;

return static function (Schedule $schedule): void {
    $schedule->cron(CompressVideos::class)->everyMinutes(5)->withoutOverlapping();
    $schedule->cron(AgendaRecordatorios::class)->dailyAt('08:00');
    $schedule->command('app:queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
};
```

- **`cron(Clase::class)`**: una clase de `app/cron` con un método `run()`, la misma que crea `php versaCLI cron:make:<Nombre>`. Se crea con el contenedor, así que su constructor puede pedir servicios.
- **`command('...')`**: un comando de `versaCLI` con sus opciones.

## Frecuencia

| Método | Expresión cron |
|---|---|
| `everyMinute()` | `* * * * *` (por defecto) |
| `everyMinutes(5)` | `*/5 * * * *`. El número debe dividir a 60. |
| `hourly()` / `hourlyAt(15)` | `0 * * * *` / `15 * * * *` |
| `daily()` / `dailyAt('03:30')` | `0 0 * * *` / `30 3 * * *` |
| `weeklyOn(1, '09:00')` | Los lunes a las 9 (0 = domingo … 6 = sábado) |
| `monthlyOn(1, '03:00')` | El día 1 de cada mes a las 3 |
| `cron('0 8 * * 1-5')` | Cualquier expresión cron de 5 campos (lunes a viernes a las 8) |

Las horas son las de `build.timezone`. Una expresión inválida falla al definirla, con un mensaje claro, no a las 3 de la mañana.

## Sin solaparse

`withoutOverlapping()` evita que una tarea corra dos veces a la vez. Si la ejecución anterior sigue en curso, esa vuelta se omite y queda registrada en el log `schedule`. Úsalo en tareas que pueden tardar más que su intervalo, como comprimir videos o procesar la cola.

## ¿Está corriendo?

`app:schedule:list` y el panel indican si el crontab está llamando al programador: cada pasada deja su marca, y si pasan más de 2 minutos sin una, avisan con la línea a usar. Si agregaste la línea y sigue sin correr, revisa `versaWYS/logs/cron.log`. Un `php: command not found` significa que falta la ruta completa de PHP.

## Ver y probar

```bash
php versaCLI app:schedule:list          # cada tarea con su id, su frecuencia, la próxima ejecución y la última
php versaCLI app:schedule:exec <id>     # ejecuta una tarea ahora mismo, para probarla
```

El resultado de cada ejecución (inicio, duración y código de salida o error) queda en `versaWYS/cache/schedule/` y en el log `schedule`.

## Migrar los crons sueltos

Si el crontab del servidor tiene una línea por cada `cron:run:<Nombre>`:

1. Agrega cada tarea en `app/schedule.php` con la misma frecuencia que tenía en el crontab (`cron('...')` acepta la expresión tal cual).
2. Reemplaza esas líneas del crontab por la línea de `app:schedule:run`.
3. Revisa con `app:schedule:list` que estén todas y que la próxima ejecución sea la esperada.
