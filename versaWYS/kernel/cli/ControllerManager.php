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

        CLIErrorHandler::info("Creando controlador {$controllerName}...");
        $controllersFile = self::$path . ($controllerName . '.php');

        if (file_exists($controllersFile)) {
            if (!CLIHelper::confirm("El Controlador {$controllersFile} ya existe. Desea sobreescribirlo?")) {
                CLIErrorHandler::info('Operación cancelada.');
                return;
            }
            unlink($controllersFile);
        }

        $front = match ($front) {
            'twig'
                => "//metodo para renderizar desde twig por defecto\n    public function index()\n    {\n        return \$this->template->render('dashboard/{$controllerNameLimpio}/index');\n    }",
            'vue'
                => "//metodo para renderizar desde twig y VUE\n    public function index()\n    {\n            return \$this->template->render('dashboard/loader', [\n                'm' => 'dashboard/js/{$controllerNameLimpio}/dash{$controllerNameUC}',\n            ]);\n    }",
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

        CLIHelper::ensureDirectory(dirname($controllersFile));

        if (file_put_contents($controllersFile, $template)) {
            chmod($controllersFile, 0o775);
            CLIErrorHandler::success("Controller {$controllersFile} creada.");
        } else {
            CLIErrorHandler::warning("No se pudo crear el archivo: {$controllersFile}");
        }
    }

    public static function deleteController(string $controllerName): void
    {
        $controllerName = ucfirst($controllerName) . 'Controller';

        CLIErrorHandler::info("Eliminando controlador {$controllerName}...");
        $controllersFile = self::$path . ($controllerName . '.php');

        if (!file_exists($controllersFile)) {
            CLIErrorHandler::warning("El Controlador {$controllersFile} no existe.");
            return;
        }

        if (unlink($controllersFile)) {
            CLIErrorHandler::success("Controller {$controllersFile} eliminada.");
        } else {
            CLIErrorHandler::warning("No se pudo eliminar: {$controllersFile}");
        }
    }
}
