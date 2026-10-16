<?php

declare(strict_types=1);

use versaWYS\kernel\Schedule;

/*
 * Tareas programadas (ver documentacion/Tareas_Programadas.md). En el servidor basta una línea en
 * el crontab:
 *
 *   * * * * * php /ruta/del/proyecto/versaCLI app:schedule:run >> /dev/null 2>&1
 *
 * `php versaCLI app:schedule:list` muestra cada tarea con su próxima ejecución y el resultado de la última.
 */
return static function (Schedule $schedule): void {
    // Una clase de app/cron (php versaCLI cron:make:LimpiarTemporales), cada noche:
    // $schedule->cron(\app\cron\LimpiarTemporales::class)->dailyAt('03:00');

    // La cola de trabajos, si el proyecto usa Queue: cada minuto y sin solaparse
    // $schedule->command('app:queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
};
