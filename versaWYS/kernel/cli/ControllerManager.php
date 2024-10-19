<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class ControllerManager
{
    private static string $path = 'app/controllers/';
    public static function createController(string $controllerName, $front = 'twig'): void
    {
        $controllerNameLimpio = $controllerName;
        $controllerNameUC = ucfirst($controllerName);
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

        $front = match ($front) {
            'twig'
                => "//metodo para renderizar desde twig por defecto\n    public function index()\n    {\n        return \$this->template->render('dashboard/$controllerNameLimpio/index');\n    }",
            'vue'
                => "//metodo para renderizar desde twig y VUE\n    public function index()\n    {\n            return \$this->template->render('dashboard/loader', [\n                'm' => 'dashboard/js/$controllerNameLimpio/dash$controllerNameUC',\n            ]);\n    }",
            default => '',
        };

        $template = <<<'EOT'
<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\Response;

//Controlador de la vista $controllerName
class $controllerName extends GlobalControllers {
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    $front
}
EOT;
        $template = str_replace('$front', $front, $template);
        $template = str_replace('$controllerNameUC', $controllerNameUC, $template);

        $template = str_replace('$controllerName', $controllerName, $template);
        $template = str_replace('$controllerNameLimpio', $controllerNameLimpio, $template);

        file_put_contents($controllersFile, $template);
        echo "Controler $controllersFile creada.\n";
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
        echo "Controler $controllersFile eliminada.\n";
    }
}
