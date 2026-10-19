<?php

declare(strict_types=1);

use versaWYS\kernel\Schedule;

/*
 * Tareas programadas (ver documentacion/Tareas_Programadas.md). En el servidor basta una línea en
 * el crontab:
 *
 *   * * * * * /usr/bin/php /ruta/del/proyecto/versaCLI app:schedule:run >> /ruta/del/proyecto/versaWYS/logs/cron.log 2>&1
 *
 * Con la ruta completa de PHP: cron no lo encuentra en su PATH. `php versaCLI app:schedule:list` muestra la
 * línea exacta para este servidor, cada tarea con su próxima ejecución y el resultado de la última.
 */
return static function (Schedule $schedule): void {
    // Borra los archivos temporales con más de un día. Cada minuto para verla correr en el panel;
    // en un proyecto real basta una vez al día: ->dailyAt('03:00')
    $schedule
        ->cron(\app\cron\LimpiarTemporales::class)
        ->everyMinute()
        ->withoutOverlapping();

    // La cola de trabajos (correos de recuperación de contraseña, app/jobs): cada minuto y sin solaparse
    $schedule->command('app:queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
};
