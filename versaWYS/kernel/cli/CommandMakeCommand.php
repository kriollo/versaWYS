<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

/**
 * Comando para generar nuevos comandos desde plantilla.
 *
 * Permite crear rápidamente nuevos comandos con la estructura correcta.
 */
class CommandMakeCommand extends BaseCommand
{
    #[\Override]
    public function execute(array $params): int
    {
        // Manejar flag de ayuda
        if (in_array('--help', $params, strict: true) || in_array('-h', $params, strict: true)) {
            echo $this->getHelp() . "\n";
            return 0;
        }

        if (empty($params)) {
            $this->error('Falta el nombre del comando');
            echo "\n";
            echo $this->getHelp() . "\n";
            return 1;
        }

        $commandName = $params[0];

        // Validar formato del nombre
        if (!$this->isValidCommandName($commandName)) {
            $this->error('Formato de nombre inválido');
            $this->info('Usa formato: modulo:accion o modulo:accion:subaccion');
            $this->info('Ejemplos: email:send, report:generate:monthly, user:create');
            return 1;
        }

        // Convertir nombre de comando a nombre de clase
        $className = $this->commandNameToClassName($commandName);
        $fileName = "{$className}.php";
        $filePath = "app/commands/{$fileName}";

        // Verificar si ya existe
        if (file_exists($filePath)) {
            $this->error("El comando ya existe: {$filePath}");

            if ($this->confirm('¿Deseas sobrescribirlo?', false)) {
                $this->warning('Sobrescribiendo archivo existente...');
            } else {
                $this->info('Operación cancelada');
                return 0;
            }
        }

        // Obtener descripción opcional
        $description = $this->getParam($params, 1, '');

        // Generar contenido del archivo

        $content = $this->generateCommandTemplate($className, $commandName, $description);

        // Crear directorio si no existe
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0o775, true);
        }

        // Escribir archivo
        if (file_put_contents($filePath, $content) === false) {
            $this->error("No se pudo crear el archivo: {$filePath}");
            return 1;
        }
        chmod($filePath, 0o775);

        $this->success("✓ Comando creado exitosamente: {$filePath}");
        echo "\n";
        $this->info("Clase generada: {$className}");
        $this->info("Nombre CLI: app:{$commandName}");
        echo "\n";

        // Actualizar cache automáticamente
        $this->info('Actualizando cache de comandos...');
        CommandRegistry::clearCache();
        $commands = CommandRegistry::getCachedCommands(true);

        if (isset($commands[$commandName])) {
            $this->success('✓ Comando registrado en cache');
        } else {
            $this->warning('⚠ Advertencia: El comando no aparece en el cache');
        }

        echo "\n";
        $this->success('¡Listo! Ahora puedes editarlo y usarlo:');
        echo "\n";
        $this->info("  Editar: code {$filePath}");
        $this->info("  Ejecutar: php versaCLI app:{$commandName}");
        $this->info("  Ver ayuda: php versaCLI app:{$commandName} --help");
        echo "\n";

        return 0;
    }

    /**
     * Convierte nombre de comando a nombre de clase.
     *
     * Ejemplos:
     * - email:send → EmailSendCommand
     * - report:generate:monthly → ReportGenerateMonthlyCommand
     */
    private function commandNameToClassName(string $commandName): string
    {
        $parts = explode(':', $commandName);
        $className = '';

        foreach ($parts as $part) {
            $className .= ucfirst($part);
        }

        return $className . 'Command';
    }

    /**
     * Valida que el nombre del comando tenga formato correcto.
     */
    private function isValidCommandName(string $name): bool
    {
        // Debe tener formato: palabra[:palabra][:palabra]
        // Solo letras minúsculas, números y guiones
        return preg_match('/^[a-z][a-z0-9\-]*(?::[a-z][a-z0-9\-]*)*$/', $name) === 1;
    }

    /**
     * Genera el contenido de la plantilla del comando.
     */
    private function generateCommandTemplate(string $className, string $commandName, string $description = ''): string
    {
        $descriptionText = !empty($description) ? $description : 'Descripción del comando';

        $date = date('Y-m-d H:i:s');

        return <<<PHP
            <?php

            declare(strict_types=1);

            namespace app\commands;

            use versaWYS\kernel\cli\BaseCommand;

            /**
             * {$descriptionText}
             *
             * @author Generado automáticamente
             * @date {$date}
             */
            class {$className} extends BaseCommand
            {
                /**
                 * Ejecuta el comando.
                 *
                 * @param array \$params Parámetros del comando
                 * @return int Exit code (0 = éxito, 1 = error)
                 */
                public function execute(array \$params): int
                {
                    \$this->info('Ejecutando comando: {$commandName}');

                    // TODO: Implementar la lógica del comando aquí

                    // Ejemplo de uso de parámetros:
                    // \$param1 = \$this->requireParam(\$params, 0, 'descripción del parámetro');
                    // \$param2 = \$this->getParam(\$params, 1, 'valor por defecto');

                    // Ejemplo de salida:
                    // \$this->success('Operación completada');
                    // \$this->error('Ocurrió un error');
                    // \$this->warning('Advertencia');

                    // Ejemplo de interacción:
                    // \$name = \$this->ask('¿Cuál es tu nombre?');
                    // if (\$this->confirm('¿Continuar?')) {
                    //     // hacer algo
                    // }

                    // Ejemplo de logging:
                    // \$this->log('Acción realizada', ['key' => 'value']);

                    \$this->success('Comando ejecutado exitosamente');

                    return 0; // Exit code 0 = éxito
                }

                /**
                 * Descripción breve del comando.
                 */
                public function getDescription(): string
                {
                    return '{$descriptionText}';
                }

                /**
                 * Texto de ayuda del comando.
                 */
                public function getHelp(): string
                {
                    return <<<'HELP'
                    Comando: app:{$commandName}

                    Descripción:
                      {$descriptionText}

                    Uso:
                      php versaCLI app:{$commandName} [parámetros]

                    Parámetros:
                      Documenta aquí los parámetros que acepta tu comando

                    Ejemplos:
                      php versaCLI app:{$commandName}
                      php versaCLI app:{$commandName} param1 param2

                    Opciones:
                      --help, -h    Muestra esta ayuda

                    HELP;
                }
            }

            PHP;
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Genera un nuevo comando desde plantilla';
    }

    #[\Override]
    public function getHelp(): string
    {
        return <<<'HELP'
            Comando: command:make

            Descripción:
              Genera un nuevo comando desde una plantilla predefinida con toda
              la estructura necesaria para comenzar a desarrollar rápidamente.

            Uso:
              php versaCLI command:make:<nombre-comando>

            Parámetros:
              nombre-comando  Nombre del comando en formato modulo:accion
                             Ejemplos: email:send, report:generate, user:create

            Ejemplos:
              php versaCLI command:make:notification:send
              php versaCLI command:make:report:generate:monthly
              php versaCLI command:make:data:import:csv

            Conversión de nombres:
              email:send              → EmailSendCommand.php
              report:generate         → ReportGenerateCommand.php
              user:create:admin       → UserCreateAdminCommand.php

            Lo que hace:
              1. Valida el nombre del comando
              2. Convierte el nombre a formato de clase (CamelCase + Command)
              3. Genera el archivo en app/commands/ con plantilla completa
              4. Actualiza el cache de comandos automáticamente
              5. Muestra instrucciones para usar el nuevo comando

            Nota:
              Para agregar una descripción personalizada, edita el archivo
              generado y modifica el método getDescription().

            Después de crear el comando:
              - Edita app/commands/TuComandoCommand.php
              - Implementa la lógica en el método execute()
              - Ejecuta: php versaCLI app:tu:comando

            HELP;
    }
}
