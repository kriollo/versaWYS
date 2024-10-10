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

        if (!in_array($params[0], ['up', 'down', 'new'])) {
            $this->printHelp();
            exit();
        }

        if ($params[0] === 'new' && !isset($params[1])) {
            $this->printHelp();
            exit();
        }

        $migrate = new MigrationManager();
        match ($params[0]) {
            'up' => $migrate->runUP(),
            'down' => $migrate->runDown($params[1]),
            'new' => $migrate->createMigration($params[1]),
            default => $this->printHelp(),
        };
    }

    private function printHelp(): void
    {
        // TODO: Agregar comando a RCMD como por ejemplo: php versaCLI RCMD:nombre --crud Crea las rutas necesarias para un crud, tambien los metodos del controlador y las vistas
        echo "\n";
        echo "\n";
        echo "Uso: php versaCLI [comando]\n";
        echo "\n";
        echo "Atajos:\n";
        echo "  RCMD:[nombre] Crear un archivo de ruta, controller, Modelo y Middleware \n";
        echo "  versaMODULE:[nombre] Crear un modulo con sus archivos de ruta, controller, Modelo y Middleware \n";
        echo "\n";
        echo "Comandos:\n";
        echo "-help Muestra esta ayuda\n";
        echo "-serve Inicia el servidor de desarrollo\n";
        echo "-config.\n";
        echo "  config:debug:[true/false] Activa o desactiva el modo debug\n";
        echo "  config:templateCache:[true/false] Activa o desactiva el cache de templates\n";
        echo "  config:ClearCache Limpia el cache de templates\n";
        echo "-migrate.\n";
        echo "  migrate:new:[nombre] Crea una nueva migración\n";
        echo "  migrate:up Ejecuta las migraciones pendientes\n";
        echo "  migrate:down:[nombre]  Ejecuta la migración indicada\n";
        echo "-Controller.\n";
        echo "  controller:make:[nombre] Crea un nuevo controlador, NO AGREGAR SUFIJO 'Controller'\n";
        echo "  controller:delete:[nombre] Elimina un controlador, NO AGREGAR SUFIJO 'Controller'\n";
        echo "-Route.\n";
        echo "  route:make:[nombre] Crea un nuevo archivo de ruta\n";
        echo "  route:delete:[nombre] Elimina un archivo de ruta\n";
        echo "  route:list Muestra todas las rutas registradas, seleccionando el archivo de ruta especifico\n";
        echo "-Middleware.\n";
        echo "  middleware:make:[nombre] Crea un nuevo middleware, NO AGREGAR SUFIJO 'Middleware'\n";
        echo "  middleware:delete:[nombre] Elimina un middleware, NO AGREGAR SUFIJO 'Middleware'\n";
        echo "-Model.\n";
        echo "  model:make:[nombre] Crea un nuevo modelo\n";
        echo "  model:delete:[nombre] Elimina un modelo\n";

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
