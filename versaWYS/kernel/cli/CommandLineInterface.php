<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class CommandLineInterface
{
    private mixed $args;

    public function __construct(mixed $args)
    {
        $this->args = $args;
    }

    public function run(): void
    {
        array_shift($this->args); // Elimina el nombre del script

        $command = $this->args[0] ?? 'help';

        $modulo = explode(':', $command)[0];
        $params = explode(':', $command);
        $params = array_slice($params, 1);

        echo "\n";
        echo "\n";
        echo "Comando: $command\n";
        echo "Modulo: $modulo\n";
        echo 'Parametros: ' . implode(', ', $params) . "\n";

        match ($modulo) {
            'serve' => exec('php -S localhost:8000'),
            'migrate' => $this->handleMigrate($params),
            'seeder' => $this->handleSeeder($params),
            'config' => $this->handleConfig($params),
            'controller' => $this->handleController($params),
            'route' => $this->handleRoute($params),
            'middleware' => $this->handleMiddleware($params),
            'model' => $this->handleModel($params),
            'RCMD' => $this->handleAtajos($params),
            'versaMODULE' => $this->handleModule($params),
            default => $this->printHelp(),
        };
    }

    private function handleSeeder($params): void
    {
        if ($params[0] === 'make' && !isset($params[1])) {
            $this->printHelp();
            exit();
        }

        if ($params[0] === 'run' && !isset($params[1])) {
            $this->printHelp();
            exit();
        }

        $seederManager = new SeederManager();
        match ($params[0]) {
            'make' => $seederManager->createSeeder($params[1]),
            'runAll' => $seederManager->runAllSeeders(),
            'run' => $seederManager->runSeeder($params[1]),
            default => $this->printHelp(),
        };
    }

    private function handleConfig($params): void
    {
        if (!isset($params[0])) {
            $this->printHelp();
            exit();
        }

        if (!in_array($params[0], ['debug', 'templateCache', 'ClearCache'])) {
            $this->printHelp();
            exit();
        }

        if ($params[0] === 'debug' && !isset($params[1])) {
            $this->printHelp();
            exit();
        }

        if ($params[0] === 'templateCache' && !isset($params[1])) {
            $this->printHelp();
            exit();
        }

        match ($params[0]) {
            'debug' => ConfigManager::setDebug($params[1]),
            'templateCache' => ConfigManager::setTemplateCache($params[1]),
            'ClearCache' => ConfigManager::clearCache(),
            default => $this->printHelp(),
        };
    }

    private function handleModule($params): void
    {
        if (!isset($params[0])) {
            $this->printHelp();
            exit();
        }

        $params = [
            0 => 'make',
            1 => $params[0],
        ];

        $make = function ($params) {
            $this->handleRoute($params);
            $this->handleController($params);
            $this->handleModel($params);
            $this->handleMiddleware($params);
            VersaModuleManager::createModule($params[1]);
        };

        $make($params);
    }

    private function handleAtajos($params): void
    {
        if (!isset($params[0])) {
            $this->printHelp();
            exit();
        }

        $params = [
            0 => 'make',
            1 => $params[0],
        ];

        $make = function ($params) {
            $this->handleRoute($params);
            $this->handleController($params);
            $this->handleModel($params);
            $this->handleMiddleware($params);
        };

        $make($params);
    }

    private function handleModel($params): void
    {
        $params = $this->getParams($params);

        match ($params[0]) {
            'make' => ModelManager::createModel($params[1]),
            'delete' => ModelManager::deleteModel($params[1]),
            default => $this->printHelp(),
        };
    }

    private function handleMiddleware($params): void
    {
        $params = $this->getParams($params);

        match ($params[0]) {
            'make' => MiddlewareManager::createMiddleware($params[1]),
            'delete' => MiddlewareManager::deleteMiddleware($params[1]),
            default => $this->printHelp(),
        };
    }
    private function handleRoute($params): void
    {
        if (!isset($params[0])) {
            $this->printHelp();
            exit();
        }

        if (!in_array($params[0], ['make', 'delete', 'list'])) {
            $this->printHelp();
            exit();
        }

        if ($params[0] === 'make' && !isset($params[1])) {
            $this->printHelp();
            exit();
        }

        match ($params[0]) {
            'make' => RouteManager::createRoute($params[1]),
            'delete' => RouteManager::deleteRoute($params[1]),
            'list' => RouteManager::listRoutes(),
            default => $this->printHelp(),
        };
    }

    private function handleController($params): void
    {
        $params = $this->getParams($params);

        match ($params[0]) {
            'make' => ControllerManager::createController($params[1]),
            'delete' => ControllerManager::deleteController($params[1]),
            default => $this->printHelp(),
        };
    }

    private function handleMigrate($params): void
    {
        if (!isset($params[0])) {
            $this->printHelp();
            exit();
        }

        if (!in_array($params[0], ['up', 'down', 'make', 'rollback', 'refresh'])) {
            $this->printHelp();
            exit();
        }

        if ($params[0] === 'make' && !isset($params[1])) {
            $this->printHelp();
            exit();
        }

        if ($params[0] === 'refresh' && isset($params[1])) {
            if ($params[1] !== 'seeder') {
                $this->printHelp();
                exit();
            }
            $params[0] = 'refresh:seeder';
        }

        $migrate = new MigrationManager();
        match ($params[0]) {
            'up' => $migrate->runUP(),
            'down' => $migrate->runDown($params[1]),
            'make' => $migrate->createMigration($params[1]),
            'rollback' => $migrate->rollback(),
            'refresh' => $migrate->refresh(),
            'refresh:seeder' => $migrate->refresh(true),
            default => $this->printHelp(),
        };
    }

    private function printHelp(): void
    {
        // Colores y estilos ANSI
        $reset = "\033[0m";
        $bold = "\033[1m";
        $underline = "\033[4m";
        $cyan = "\033[36m";
        $yellow = "\033[33m";
        $green = "\033[32m";
        $red = "\033[31m";

        echo "\n";
        echo "{$bold}{$cyan}Uso:{$reset} php versaCLI [comando]\n";
        echo "\n";
        echo "{$bold}{$cyan}Atajos:{$reset}\n";
        echo "  {$yellow}RCMD:[nombre]{$reset} Crear un archivo de ruta, controller, Modelo y Middleware \n";
        echo "  {$yellow}versaMODULE:[nombre]{$reset} Crear un modulo con sus archivos de ruta, controller, Modelo y Middleware \n";
        echo "\n";
        echo "{$bold}{$cyan}Comandos:{$reset}\n";
        echo "{$bold}{$green}-help{$reset} Muestra esta ayuda\n";
        echo "{$bold}{$green}-serve{$reset} Inicia el servidor de desarrollo\n";

        echo "{$bold}{$green}-config{$reset}\n";
        echo "  {$yellow}config:debug:[true/false]{$reset} Activa o desactiva el modo debug\n";
        echo "  {$yellow}config:templateCache:[true/false]{$reset} Activa o desactiva el cache de templates\n";
        echo "  {$yellow}config:ClearCache{$reset} Limpia el cache de templates\n";

        echo "{$bold}{$green}-migrate{$reset}\n";
        echo "  {$yellow}migrate:make:[nombre]{$reset} Crea una nueva migración\n";
        echo "  {$yellow}migrate:up{$reset} Ejecuta las migraciones pendientes\n";
        echo "  {$yellow}migrate:down:[nombre]{$reset} Ejecuta la migra ción indicada\n";
        echo "  {$yellow}migrate:rollback{$reset} Revierte la última migración\n";
        echo "  {$yellow}migrate:refresh{$reset} Revierte todas las migraciones y las vuelve a ejecutar\n";
        echo "  {$yellow}migrate:refresh:seeder{$reset} Revierte todas las migraciones, las vuelve a ejecutar y ejecuta todos los seeders\n";

        echo "{$bold}{$green}-seeder{$reset}\n";
        echo "  {$yellow}seeder:make:[nombre]{$reset} Crea un nuevo seeder\n";
        echo "  {$yellow}seeder:runAll{$reset} Ejecuta todos los seeders\n";
        echo "  {$yellow}seeder:run:[nombre]{$reset} Ejecuta el seeder indicado\n";

        echo "{$bold}{$green}-Controller{$reset}\n";
        echo "  {$yellow}controller:make:[nombre]{$reset} Crea un nuevo controlador, NO AGREGAR SUFIJO 'Controller'\n";
        echo "  {$yellow}controller:delete:[nombre]{$reset} Elimina un controlador, NO AGREGAR SUFIJO 'Controller'\n";

        echo "{$bold}{$green}-Route{$reset}\n";
        echo "  {$yellow}route:make:[nombre]{$reset} Crea un nuevo archivo de ruta\n";
        echo "  {$yellow}route:delete:[nombre]{$reset} Elimina un archivo de ruta\n";
        echo "  {$yellow}route:list{$reset} Muestra todas las rutas registradas, seleccionando el archivo de ruta especifico\n";

        echo "{$bold}{$green}-Middleware{$reset}\n";
        echo "  {$yellow}middleware:make:[nombre]{$reset} Crea un nuevo middleware, NO AGREGAR SUFIJO 'Middleware'\n";
        echo "  {$yellow}middleware:delete:[nombre]{$reset} Elimina un middleware, NO AGREGAR SUFIJO 'Middleware'\n";

        echo "{$bold}{$green}-Model{$reset}\n";
        echo "  {$yellow}model:make:[nombre]{$reset} Crea un nuevo modelo\n";
        echo "  {$yellow}model:delete:[nombre]{$reset} Elimina un modelo\n";

        echo "\n";
        echo "\n";
        // Lista otros comandos aquí
    }

    /**
     * @param $params
     * @return mixed
     */
    private function getParams($params): mixed
    {
        if (!isset($params[0])) {
            $this->printHelp();
            exit();
        }

        if ($params[0] != 'make') {
            $this->printHelp();
            exit();
        }

        if ($params[0] === 'make' && !isset($params[1])) {
            $this->printHelp();
            exit();
        }
        return $params;
    }
}
