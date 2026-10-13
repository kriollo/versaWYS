<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;
use Throwable;

class CommandLineInterface
{
    private bool $verbose = false;

    private bool $force = false;

    public function __construct(
        private mixed $args,
    ) {
        // Detectar modo verbose
        $this->verbose = in_array('--verbose', $this->args, strict: true) || in_array('-v', $this->args, strict: true);

        // --force omite las confirmaciones de comandos destructivos (CI / deploy)
        $this->force = in_array('--force', $this->args, strict: true);

        // Remover flags globales de los argumentos
        $this->args = array_filter(
            $this->args,
            static fn ($arg): bool => !in_array($arg, ['--verbose', '-v', '--force'], strict: true),
        );
        $this->args = array_values($this->args); // Re-indexar array
    }

    public function run(): void
    {
        // Configurar manejo de errores para CLI
        $this->setupCLIErrorHandling();

        array_shift($this->args); // Elimina el nombre del script

        $command = $this->args[0] ?? 'help';

        $modulo = explode(':', $command)[0];
        $params = explode(':', $command);
        $params = array_slice($params, 1);

        // Agregar argumentos adicionales que vienen después del comando
        $additionalArgs = array_slice($this->args, 1);
        $params = array_merge($params, $additionalArgs);

        // Filtrar parámetros vacíos (pero mantener "0" que es válido)
        $params = array_filter($params, static fn (string $param): bool => trim($param) !== '');
        // Re-indexar array
        $params = array_values($params);

        // Los parámetros de estos módulos terminan en nombres de archivo/clase: nunca rutas
        $generators = ['migrate', 'seeder', 'route', 'controller', 'model', 'middleware', 'RCMD', 'versaMODULE', 'front', 'cron', 'logs'];
        if (in_array($modulo, $generators, strict: true)) {
            foreach ($params as $param) {
                // Las opciones (--lines=50) las valida cada comando
                if (str_starts_with($param, '--')) {
                    continue;
                }

                if (preg_match('/^[A-Za-z0-9_][A-Za-z0-9_.\-]*$/', $param) !== 1 || str_contains($param, '..')) {
                    CLIErrorHandler::warning("Parámetro inválido: '{$param}'. Solo letras, números, '_', '-' y '.'.");
                    exit(1);
                }
            }
        }

        // Solo mostrar información de debug si es necesario
        if ($this->verbose) {
            CLIErrorHandler::info('Ejecutando comando: ' . $command);
            CLIErrorHandler::info('Módulo: ' . $modulo);
            if ($params !== []) {
                CLIErrorHandler::info('Parámetros: ' . implode(', ', $params));
            }

            echo "\n";
        }

        try {
            match ($modulo) {
                'migrate' => $this->handleMigrate($params),
                'seeder' => $this->handleSeeder($params),
                'config' => $this->handleConfig($params),
                'route' => $this->handleRoute($params),
                'controller' => $this->handleController($params),
                'model' => $this->handleModel($params),
                'middleware' => $this->handleMiddleware($params),
                'RCMD' => $this->handleAtajos($params),
                'versaMODULE' => $this->handleModule($params),
                'front' => $this->handleFront($params),
                'serve' => ServerManager::handleServe($this->verbose, $params),
                'status' => StatusManager::handleStatus($this->verbose),
                'version' => VersionManager::handleVersion(),
                'logs' => LogManager::handleLogs($params, $this->verbose),
                'cache' => CacheManager::handleCacheCommands($params, $this->verbose),
                'cron' => $this->handleCron($params),
                'command' => $this->handleCommand($params),
                'app' => $this->handleAppCommand($params),
                'help' => HelpView::printHelp(),
                default => $this->unknownCommand($command),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Ejecutando comando: ' . $command);
        }
    }

    /**
     * Configura el manejo de errores específico para CLI.
     */
    private function setupCLIErrorHandling(): void
    {
        // Deshabilitar la salida de errores de HTTP en CLI
        if (PHP_SAPI === 'cli') {
            // Configurar manejador de errores fatales
            register_shutdown_function(static function (): void {
                $error = error_get_last();
                if (
                    $error !== null
                    && in_array(
                        $error['type'],
                        [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR],
                        strict: true,
                    )
                ) {
                    CLIErrorHandler::printSeparator();
                    echo CLIErrorHandler::colorize('FATAL ERROR', 'red') . "\n\n";
                    echo CLIErrorHandler::colorize('Archivo: ', 'cyan') . $error['file'] . "\n";
                    echo CLIErrorHandler::colorize('Línea: ', 'cyan') . $error['line'] . "\n";
                    echo CLIErrorHandler::colorize('Mensaje: ', 'cyan') . $error['message'] . "\n";
                }
            });

            // Configurar manejador de excepciones no capturadas
            set_exception_handler(static function (Throwable $e): void {
                CLIErrorHandler::handleGeneralError($e, 'Excepción no capturada');
            });
        }
    }

    private function handleFront(array $params, string $prefix = ''): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('front');

            return;
        }

        $allowedActions = ['makeTwig', 'makeVue'];
        $action = $this->validateAction($params[0], $allowedActions, 'front');
        $name = $this->requireParam($params, 1, sprintf('php versaCLI front:%s:nombre_componente', $action));

        $this->verboseInfo(
            sprintf("Iniciando operación de frontend: %s para '%s'", $action, $name)
                . ($prefix !== '' && $prefix !== '0' ? sprintf(" con prefijo '%s'", $prefix) : ''),
        );

        try {
            $frontManager = new FrontManager();
            match ($action) {
                'makeTwig' => $frontManager->createTwig($name, $prefix),
                'makeVue' => $frontManager->createVue($name, $prefix),
                default => $this->showError('Acción de frontend no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Generando frontend: ' . $action);
        }
    }

    private function handleSeeder(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('seeder');

            return;
        }

        $allowedActions = ['make', 'runAll', 'run'];
        $action = $this->validateAction($params[0], $allowedActions, 'seeder');

        if (in_array($action, ['make', 'run'], strict: true)) {
            $name = $this->requireParam($params, 1, sprintf('php versaCLI seeder:%s:nombre_seeder', $action));
        }

        $this->verboseInfo('Iniciando operación de seeder: ' . $action);

        try {
            $seederManager = new SeederManager();
            match ($action) {
                'make' => $seederManager->createSeeder($params[1]),
                'runAll' => $seederManager->runAllSeeders(),
                'run' => $seederManager->runSeeder($params[1]),
                default => $this->showError('Acción de seeder no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Ejecutando seeder: ' . $action);
        }
    }

    private function handleConfig(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('config');

            return;
        }

        $allowedActions = ['debug', 'templateCache', 'ClearCache', 'init'];
        $action = $this->validateAction($params[0], $allowedActions, 'config');

        if (in_array($action, ['debug', 'templateCache'], strict: true)) {
            $value = $this->requireParam($params, 1, sprintf('php versaCLI config:%s:true|false', $action));

            if (!in_array($value, ['true', 'false'], strict: true)) {
                $this->showError(sprintf("El valor para %s debe ser 'true' o 'false'.", $action));

                return;
            }
        }

        $this->verboseInfo('Iniciando operación de configuración: ' . $action);

        try {
            match ($action) {
                'debug' => ConfigManager::setDebug($params[1]),
                'templateCache' => ConfigManager::setTemplateCache($params[1]),
                'ClearCache' => ConfigManager::clearCache(),
                'init' => ConfigManager::initConfig(),
                default => $this->showError('Acción de configuración no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Configurando: ' . $action);
        }
    }

    private function handleModule(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('versaMODULE');

            return;
        }

        $allowedTypes = ['twig', 'vue'];
        $type = $this->validateAction($params[0], $allowedTypes, 'versaMODULE');
        $name = $this->requireParam($params, 1, sprintf('php versaCLI versaMODULE:%s:nombre_modulo', $type));

        $this->verboseInfo(sprintf("Creando módulo completo '%s' de tipo '%s'", $name, $type));

        $moduleParams = [
            0 => 'make',
            1 => $name,
            2 => $type,
        ];

        try {
            $this->verboseInfo('Generando ruta...');
            $this->handleRoute($moduleParams);

            $this->verboseInfo('Generando controlador...');
            $this->handleController($moduleParams, $type);

            $this->verboseInfo('Generando modelo...');
            $this->handleModel($moduleParams);

            $this->verboseInfo('Generando middleware...');
            $this->handleMiddleware($moduleParams);

            $this->verboseInfo('Generando frontend...');
            $this->handleFront([
                0 => $type === 'twig' ? 'makeTwig' : 'makeVue',
                1 => $name,
            ], 'dash');

            $this->showSuccess(sprintf("Módulo '%s' de tipo '%s' creado exitosamente.", $name, $type));
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Creando módulo: ' . $name);
        }
    }

    private function handleAtajos(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('RCMD');

            return;
        }

        $moduleName = $params[0];
        $this->verboseInfo(sprintf("Generando módulo completo '%s' con RCMD", $moduleName));

        $moduleParams = [
            0 => 'make',
            1 => $moduleName,
        ];

        try {
            $this->verboseInfo(sprintf("Generando ruta para módulo '%s'...", $moduleName));
            $this->handleRoute($moduleParams);

            $this->verboseInfo(sprintf("Generando controlador para módulo '%s'...", $moduleName));
            $this->handleController($moduleParams);

            $this->verboseInfo(sprintf("Generando modelo para módulo '%s'...", $moduleName));
            $this->handleModel($moduleParams);

            $this->verboseInfo(sprintf("Generando middleware para módulo '%s'...", $moduleName));
            $this->handleMiddleware($moduleParams);

            $this->showSuccess(sprintf(
                "Módulo '%s' creado exitosamente con todos los archivos necesarios.",
                $moduleName,
            ));
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Generando módulo: ' . $moduleName);
        }
    }

    private function handleModel(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('model');
            return;
        }

        $params = $this->getParams($params);

        if ($params === null) {
            return;
        }

        $action = $params[0];
        $name = $params[1];

        $this->verboseInfo(sprintf("Iniciando operación de modelo: %s para '%s'", $action, $name));

        try {
            match ($action) {
                'make' => ModelManager::createModel($name),
                'delete' => ModelManager::deleteModel($name),
                default => $this->showError('Acción de modelo no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Gestionando modelo: ' . $action);
        }
    }

    private function handleMiddleware(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('middleware');
            return;
        }

        $params = $this->getParams($params);

        if ($params === null) {
            return;
        }

        $action = $params[0];
        $name = $params[1];

        $this->verboseInfo(sprintf("Iniciando operación de middleware: %s para '%s'", $action, $name));

        try {
            match ($action) {
                'make' => MiddlewareManager::createMiddleware($name),
                'delete' => MiddlewareManager::deleteMiddleware($name),
                default => $this->showError('Acción de middleware no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Gestionando middleware: ' . $action);
        }
    }

    private function handleRoute(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('route');

            return;
        }

        $allowedActions = ['make', 'delete', 'list', 'check', 'compile', 'cache', 'clear-cache'];
        $action = $this->validateAction($params[0], $allowedActions, 'route');

        if (in_array($action, ['make', 'delete'], strict: true)) {
            $name = $this->requireParam($params, 1, sprintf('php versaCLI route:%s:nombre_ruta', $action));
        }

        $this->verboseInfo('Iniciando operación de ruta: ' . $action);

        try {
            match ($action) {
                'make' => RouteManager::createRoute($params[1]),
                'delete' => RouteManager::deleteRoute($params[1]),
                'list' => in_array('--all', $params, strict: true) ? RouteManager::listAllRoutes() : RouteManager::listRoutes(),
                'check' => RouteManager::checkRoutes(in_array('--strict', $params, strict: true)),
                'compile' => RouteManager::compileRoutes(),
                'cache' => RouteManager::cacheStatus(),
                'clear-cache' => RouteManager::clearCache(),
                default => $this->showError('Acción de ruta no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Gestionando rutas: ' . $action);
        }
    }

    private function handleController(array $params, string $front = 'twig'): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('controller');
            return;
        }

        $params = $this->getParams($params);

        if ($params === null) {
            return;
        }

        $action = $params[0];
        $name = $params[1];

        $this->verboseInfo(sprintf(
            "Iniciando operación de controlador: %s para '%s' (tipo: %s)",
            $action,
            $name,
            $front,
        ));

        try {
            match ($action) {
                'make' => ControllerManager::createController($name, $front),
                'delete' => ControllerManager::deleteController($name),
                default => $this->showError('Acción de controlador no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Gestionando controlador: ' . $action);
        }
    }

    private function handleMigrate(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('migrate');

            return;
        }

        $allowedActions = ['up', 'down', 'make', 'rollback', 'refresh', 'dropAll', 'status'];
        $action = $this->validateAction($params[0], $allowedActions, 'migrate');

        if ($action === 'make') {
            $name = $this->requireParam($params, 1, 'php versaCLI migrate:make:nombre_tabla');
        }

        if ($action === 'refresh' && isset($params[1])) {
            if ($params[1] !== 'seeder') {
                $this->showError("Para migrate:refresh solo se acepta el parámetro 'seeder'.");
                echo CLIErrorHandler::colorize('Uso: ', 'cyan') . "php versaCLI migrate:refresh:seeder\n";

                return;
            }

            $action = 'refresh:seeder';
        }

        if (
            in_array($action, ['refresh', 'refresh:seeder', 'dropAll'], strict: true)
            && !$this->force
            && !CLIHelper::confirm("migrate:{$action} ELIMINA datos de la base de datos. ¿Continuar?")
        ) {
            CLIErrorHandler::info('Operación cancelada. Use --force para omitir esta confirmación.');

            return;
        }

        $this->verboseInfo('Iniciando operación de migración: ' . $action);

        try {
            $migrate = new MigrationManager();
            match ($action) {
                'up' => $migrate->runUP(),
                'down' => $migrate->runDown($params[1] ?? ''),
                'make' => $migrate->createMigration($params[1]),
                'rollback' => $migrate->rollback(),
                'refresh' => $migrate->refresh(false, $this->verbose),
                'refresh:seeder' => $migrate->refresh(true, $this->verbose),
                'dropAll' => $migrate->dropAll(),
                'status' => $migrate->status(),
                default => $this->showError('Acción de migración no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleMigrationError($exception, $action);
        }
    }

    private function getParams(array $params): mixed
    {
        if ($params === []) {
            // Try to determine which help to show based on caller?
            // getParams is generic, so we can't easily know the module here without passing it.
            // However, getParams is called by handleModel, handleMiddleware, handleController.
            // We can let them handle the empty check OR pass the module name.
            // Let's modify getParams signature in a separate step or let the caller check first.
            // For now, let's keep getParams as is but remove the error? No, that would break validation.
            // Better: callers check for empty params BEFORE calling getParams.
            // But getParams checks for empty params.
            // So we should modify callers to check empty params first.
            // I will remove this replacement from here and do it in the callers refactoring below.
            // Wait, I can't undo here. I will just update getParams to accept a module name or handle it differently.
            // Actually, simplest is to check in caller.
            return null;
        }

        $action = $params[0];
        $allowedActions = ['make', 'delete'];

        if (!in_array($action, $allowedActions, strict: true)) {
            $this->showError(sprintf("Acción '%s' no reconocida.", $action));
            echo CLIErrorHandler::colorize('Acciones disponibles: ', 'cyan') . implode(', ', $allowedActions) . "\n";
            HelpView::printHelp();

            return null;
        }

        if (in_array($action, ['make', 'delete'], strict: true) && !isset($params[1])) {
            $actionText = $action === 'make' ? 'crear' : 'eliminar';
            $this->showError(sprintf('Se requiere especificar el nombre del elemento a %s.', $actionText));
            echo CLIErrorHandler::colorize('Ejemplo: ', 'cyan') . ($action . ' usuarios
');
            HelpView::printHelp();

            return null;
        }

        return $params;
    }

    /**
     * Muestra un mensaje solo si está en modo verbose.
     */
    private function verboseInfo(string $message): void
    {
        if ($this->verbose) {
            CLIErrorHandler::info($message);
        }
    }

    /**
     * Muestra un mensaje de éxito.
     */
    private function showSuccess(string $message): void
    {
        CLIErrorHandler::success($message);
    }

    private function unknownCommand(string $command): void
    {
        $this->showError("Comando no reconocido: '{$command}'.");
        HelpView::printHelp();
    }

    /**
     * Muestra un mensaje de error y marca la ejecución como fallida.
     */
    private function showError(string $message, int $code = 1): void
    {
        // No termina el proceso: quien llama puede mostrar a continuación el uso correcto
        CLIErrorHandler::warning($message);
        CLIErrorHandler::$failed = true;
    }

    /**
     * Valida que un parámetro requerido esté presente.
     */
    private function requireParam(array $params, int $index, string $commandHelp = ''): string
    {
        if (!isset($params[$index]) || in_array(trim($params[$index]), ['', '0'], true)) {
            $this->showError(sprintf('Parámetro requerido faltante o vacío en la posición %d.', $index));
            if ($commandHelp !== '' && $commandHelp !== '0') {
                echo "\n" . CLIErrorHandler::colorize('Uso: ', 'cyan') . $commandHelp . "\n";
            }

            HelpView::printHelp();
            exit(1);
        }

        return trim($params[$index]);
    }

    /**
     * Valida que una acción esté en la lista de acciones permitidas.
     */
    private function validateAction(string $action, array $allowedActions, string $command): string
    {
        if (!in_array($action, $allowedActions, strict: true)) {
            $this->showError(sprintf("Acción '%s' no válida para %s.", $action, $command));
            echo CLIErrorHandler::colorize('Acciones permitidas: ', 'cyan') . implode(', ', $allowedActions) . "\n";
            exit(1);
        }

        return $action;
    }

    private function handleCron(array $params): void
    {
        if ($params === []) {
            HelpView::printModuleHelp('cron');

            return;
        }

        $allowedActions = ['make', 'list', 'run'];
        $action = $this->validateAction($params[0], $allowedActions, 'cron');
        $name = '';

        if (in_array($action, ['make', 'run'], strict: true)) {
            $name = $this->requireParam($params, 1, sprintf('php versaCLI cron:%s:NombreTarea', $action));
        }

        $this->verboseInfo('Iniciando tarea programada: ' . $action);

        try {
            match ($action) {
                'make' => CronManager::createCron($name),
                'list' => CronManager::listCrons(),
                'run' => CronManager::runCron($name),
                default => $this->showError("Acción de cron '{$action}' no reconocida."),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Ejecutando cron: ' . $action);
        }
    }

    /**
     * Maneja comandos de aplicación con el prefijo app:.
     */
    private function handleAppCommand(array $params): void
    {
        // Los params vienen como: ["video", "process", "123"]
        // Necesitamos determinar cuáles partes son el comando y cuáles son parámetros

        // Obtener lista de comandos registrados para determinar el match correcto
        $commands = CommandRegistry::getCachedCommands();

        $commandName = '';
        $commandParams = [];

        // Intentar match más largo primero (ej: video:process antes que video)
        for ($i = count($params); $i > 0; --$i) {
            $testCommand = implode(':', array_slice($params, 0, $i));
            if (isset($commands[$testCommand])) {
                $commandName = $testCommand;
                $commandParams = array_slice($params, $i);
                break;
            }
        }

        ApplicationCommandManager::handleAppCommand($commandName, $commandParams);
    }

    /**
     * Maneja comandos relacionados con la gestión de comandos de aplicación.
     */
    private function handleCommand(array $params): void
    {
        if ($params === []) {
            echo "Uso: php versaCLI command:<acción>\n";
            echo "\n";
            echo "Acciones disponibles:\n";
            echo "  make:[nombre]      Genera un nuevo comando desde plantilla\n";
            echo "  list               Lista todos los comandos de aplicación descubiertos\n";
            echo "  refresh            Refresca el cache de comandos\n";
            echo "  clear-cache        Limpia el cache de comandos\n";
            echo "\n";
            echo "Ejemplos:\n";
            echo "  php versaCLI command:make:email:send\n";
            echo "  php versaCLI command:make:notification:push\n";
            echo "  php versaCLI command:make:report:generate:monthly\n";
            return;
        }

        $action = $params[0] ?? 'help';

        try {
            match ($action) {
                'make' => $this->executeMakeCommand($params),
                'list' => ApplicationCommandManager::refreshCache(),
                'refresh' => ApplicationCommandManager::refreshCache(),
                'clear-cache' => CommandRegistry::clearCache(),
                default => CLIErrorHandler::warning("Acción desconocida: {$action}"),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, "Comando: command:{$action}");
        }
    }

    /**
     * Ejecuta el comando de generación de plantillas.
     */
    private function executeMakeCommand(array $params): void
    {
        $commandName = $this->requireParam($params, 1, 'php versaCLI command:make:nombre-comando');
        $description = $params[2] ?? '';

        $generator = new CommandMakeCommand();
        $exitCode = $generator->execute([$commandName, $description]);

        if ($exitCode !== 0) {
            exit($exitCode);
        }
    }
}
