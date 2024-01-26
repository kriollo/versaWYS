<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class MiddlewareManager
{
    private static string $path = 'app/middleware/';
    public static function createMiddleware(string $middlewareName): void
    {
        $middlewareName = ucfirst($middlewareName) . 'Middleware';

        echo "Creando middleware $middlewareName...\n";
        $middlewareFile = self::$path . "{$middlewareName}.php";

        if (file_exists($middlewareFile)) {
            echo "El Middleware {$middlewareFile} ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";
                exit;
            }
            unlink($middlewareFile);
        }
        $template = <<<'EOT'
                        <?php

                        declare(strict_types=1);

                        namespace app\middleware;

                        use versaWYS\kernel\helpers\Functions;

                        class $middlewareName {}
                        EOT;

        $template = str_replace('$middlewareName', $middlewareName, $template);
        file_put_contents($middlewareFile, $template);
        echo "Ruta {$middlewareFile} creada.\n";
    }
    public static function deleteMiddleware(string $middlewareName): void
    {
        $middlewareName = ucfirst($middlewareName) . 'Middleware';

        echo "Eliminando middleware $middlewareName...\n";
        $middlewareFile = self::$path . "{$middlewareName}.php";

        if (!file_exists($middlewareFile)) {
            echo "El Middleware {$middlewareFile} no existe.\n";
            exit;
        }

        unlink($middlewareFile);
        echo "Ruta {$middlewareFile} eliminada.\n";
    }
}
