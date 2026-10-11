<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;
use versaWYS\kernel\Loggin;

/**
 * Gestor de comandos de aplicación.
 *
 * Ejecuta comandos descubiertos en app/commands/ con el prefijo "app:".
 */
class ApplicationCommandManager
{
    /**
     * Ejecuta un comando de aplicación.
     *
     * @param string $commandName Nombre del comando (sin el prefijo "app:")
     * @param array $params Parámetros del comando
     *
     * @throws Exception Si el comando no existe o falla
     */
    public static function handleAppCommand(string $commandName, array $params): void
    {
        try {
            global $debug;
            // Si no se proporciona comando, mostrar ayuda
            if ($commandName === '' || $commandName === 'help') {
                self::showAppCommandsHelp();

                return;
            }

            // Obtener comandos registrados
            $commands = CommandRegistry::getCachedCommands();

            // Verificar si el comando existe
            if (!isset($commands[$commandName])) {
                CLIErrorHandler::warning("Comando 'app:{$commandName}' no encontrado");
                echo "\n";
                self::showAvailableCommands($commands);
                exit(1);
            }

            $className = $commands[$commandName];

            // Verificar que la clase exista
            if (!class_exists($className)) {
                throw new Exception("La clase del comando '{$className}' no existe");
            }

            // Instanciar y verificar interfaz
            $command = new $className();

            if (!$command instanceof CommandInterface) {
                throw new Exception("El comando '{$className}' no implementa CommandInterface");
            }

            // Detectar flag --help o -h
            if (in_array('--help', $params, true) || in_array('-h', $params, true)) {
                self::showCommandHelp($commandName, $command);

                return;
            }

            // Registrar inicio de ejecución
            if ($debug) {
                Loggin::info("Ejecutando comando: app:{$commandName}", 'cli', [
                    'params' => $params,
                ]);
            }

            CLIErrorHandler::info("Ejecutando: app:{$commandName}");
            echo "\n";

            // Ejecutar comando
            $exitCode = $command->execute($params);

            // Manejar código de salida
            if ($exitCode === 0) {
                echo "\n";
                CLIErrorHandler::success('Comando ejecutado exitosamente');
            } else {
                echo "\n";
                CLIErrorHandler::warning("Comando terminó con código de salida: {$exitCode}");
                exit($exitCode);
            }
        } catch (Exception $e) {
            Loggin::error(
                "Error ejecutando comando app:{$commandName}: " . $e->getMessage(),
                'ApplicationCommandManager',
                [
                    'command' => $commandName,
                    'params' => $params,
                    'trace' => $e->getTraceAsString(),
                ],
            );

            CLIErrorHandler::handleGeneralError($e, "Comando: app:{$commandName}");
            exit(1);
        }
    }

    /**
     * Muestra la ayuda general de comandos de aplicación.
     */
    private static function showAppCommandsHelp(): void
    {
        echo CLIErrorHandler::colorize("Comandos de Aplicación\n", 'bold');
        echo CLIErrorHandler::colorize(str_repeat('═', 60) . "\n", 'cyan');
        echo "\n";
        echo "Los comandos de aplicación se ejecutan con el prefijo 'app:'\n";
        echo "\n";
        echo CLIErrorHandler::colorize("Uso:\n", 'green');
        echo "  php versaCLI app:<comando> [parámetros...]\n";
        echo "\n";
        echo CLIErrorHandler::colorize("Ejemplos:\n", 'green');
        echo "  php versaCLI app:video:process 123\n";
        echo "  php versaCLI app:email:send user@example.com\n";
        echo "\n";

        $commands = CommandRegistry::getCachedCommands();
        self::showAvailableCommands($commands);
    }

    /**
     * Muestra la lista de comandos disponibles.
     *
     * @param array $commands Array de comandos [nombre => clase]
     */
    private static function showAvailableCommands(array $commands): void
    {
        if (count($commands) === 0) {
            echo CLIErrorHandler::colorize("No hay comandos de aplicación disponibles.\n", 'yellow');
            echo "Crea comandos en app/commands/ que implementen CommandInterface.\n";

            return;
        }

        echo CLIErrorHandler::colorize("Comandos Disponibles:\n", 'green');
        echo "\n";

        $maxLength = 0;
        $commandsInfo = [];

        // Obtener información de todos los comandos
        foreach ($commands as $name => $class) {
            $info = CommandRegistry::getCommandInfo($name);
            if ($info !== null) {
                $commandsInfo[$name] = $info;
                $maxLength = max($maxLength, strlen($name));
            }
        }

        // Mostrar comandos con sus descripciones
        foreach ($commandsInfo as $name => $info) {
            $padding = str_repeat(' ', $maxLength - strlen($name) + 2);
            echo CLIErrorHandler::colorize("  app:{$name}", 'cyan');
            echo $padding;
            echo $info['description'] . "\n";
        }

        echo "\n";
        echo "Para ver ayuda de un comando específico:\n";
        echo "  php versaCLI app:<comando> --help\n";
    }

    /**
     * Muestra la ayuda detallada de un comando específico.
     *
     * @param string $commandName Nombre del comando
     * @param CommandInterface $command Instancia del comando
     */
    private static function showCommandHelp(string $commandName, CommandInterface $command): void
    {
        echo "\n";
        echo CLIErrorHandler::colorize(str_repeat('━', 62) . "\n", 'cyan');
        echo '  📖 ' . CLIErrorHandler::colorize("Ayuda: app:{$commandName}\n", 'green');
        echo CLIErrorHandler::colorize(str_repeat('━', 62) . "\n", 'cyan');
        echo "\n";

        // Mostrar descripción
        echo CLIErrorHandler::colorize("Descripción:\n", 'yellow');
        echo '  ' . $command->getDescription() . "\n";
        echo "\n";

        // Mostrar ayuda detallada
        $help = $command->getHelp();
        if ($help !== $command->getDescription()) {
            // Si getHelp() retorna algo diferente a la descripción, mostrarlo
            $lines = explode("\n", $help);
            foreach ($lines as $line) {
                echo $line . "\n";
            }
        } else {
            // Información básica de uso
            echo CLIErrorHandler::colorize("Uso:\n", 'yellow');
            echo "  php versaCLI app:{$commandName} [parámetros...]\n";
            echo "\n";
        }

        echo "\n";
    }

    /**
     * Refresca el cache de comandos.
     */
    public static function refreshCache(): void
    {
        CLIErrorHandler::info('Refrescando cache de comandos...');

        $commands = CommandRegistry::getCachedCommands(forceRefresh: true);

        CLIErrorHandler::success('Cache actualizado. Comandos encontrados: ' . count($commands));

        if (count($commands) > 0) {
            echo "\n";
            self::showAvailableCommands($commands);
        }
    }
}
