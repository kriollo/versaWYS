<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class ControllerManager
{
    private static string $path = 'app/controllers/';
    public static function createController(string $controllerName): void
    {
        $controllerName = ucfirst($controllerName) . 'Controller';

        echo "Creando controlador $controllerName...\n";
        $controllersFile = self::$path . "$controllerName.php";

        if (file_exists($controllersFile)) {
            echo "El Controllador $controllersFile ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen('php://stdin', 'r');
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";
                exit();
            }
            unlink($controllersFile);
        }
        $template = <<<'EOT'
<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\Response;

class $controllerName extends GlobalControllers {
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    public function index()
    {
        return $this->template->render('dashboard/$controllerName/index');
    }
}
EOT;

        $template = str_replace('$controllerName', $controllerName, $template);

        file_put_contents($controllersFile, $template);
        echo "Migración $controllersFile creada.\n";
    }
    public static function deleteController(string $controllerName): void
    {
        $controllerName = ucfirst($controllerName) . 'Controller';

        echo "Eliminando controlador $controllerName...\n";
        $controllersFile = self::$path . "$controllerName.php";

        if (!file_exists($controllersFile)) {
            echo "El Controllador $controllersFile no existe.\n";
            exit();
        }

        unlink($controllersFile);
        echo "Migración $controllersFile eliminada.\n";
    }
}
