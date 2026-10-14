<?php

declare(strict_types=1);

namespace versaWYS\kernel;

/**
 * Punto de entrada web. El index.php del proyecto solo carga el autoload de Composer y llama a run().
 */
final class App
{
    public static function run(string $root): void
    {
        // Permitir que el grupo tenga permisos de escritura en todos los archivos/dirs que PHP cree
        umask(0002);

        // El kernel usa rutas relativas a la raíz del proyecto
        chdir($root);

        require __DIR__ . '/config/config.php';
        require __DIR__ . '/init_error_handling.php';
        require __DIR__ . '/init_headers.php';

        (new Router())->resolve();
    }
}
