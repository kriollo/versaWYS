<?php

declare(strict_types=1);

namespace app\cron;

use versaWYS\kernel\Loggin;

/**
 * Borra los archivos temporales (assets.dashboard.temp, por defecto public/temp) con más de un día:
 * exportaciones, archivos a medio subir y similares que nadie va a volver a pedir.
 */
class LimpiarTemporales
{
    private const int MAX_AGE_SECONDS = 86_400;

    public function run(): void
    {
        global $config;

        $dir = rtrim((string) ($config['assets']['dashboard']['temp']['dist'] ?? 'public/temp'), '/');
        if (!is_dir($dir)) {
            echo "Sin carpeta de temporales ({$dir}): nada que limpiar\n";
            Loggin::info('Temporales: no existe la carpeta', 'schedule', ['dir' => $dir]);

            return;
        }

        $limit = time() - self::MAX_AGE_SECONDS;
        $deleted = 0;
        $bytes = 0;
        $files = glob($dir . '/*');
        foreach ($files === false ? [] : $files as $file) {
            // Solo archivos (no subcarpetas) y nunca el .gitignore o .htaccess de la carpeta
            if (!is_file($file) || str_starts_with(basename($file), '.') || filemtime($file) > $limit) {
                continue;
            }

            $size = (int) filesize($file);
            if (unlink($file)) {
                $deleted++;
                $bytes += $size;
            }
        }

        echo "Temporales: {$deleted} archivo(s) borrados\n";
        Loggin::info('Temporales limpiados', 'schedule', ['dir' => $dir, 'archivos' => $deleted, 'bytes' => $bytes]);
    }
}
