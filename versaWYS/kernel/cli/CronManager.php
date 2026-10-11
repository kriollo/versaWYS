<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;

class CronManager
{
    private static string $path = 'app/cron/';

    public static function createCron(string $name): void
    {
        $name = ucfirst($name);
        $className = $name;

        CLIErrorHandler::info("Creando tarea cron {$name}...");

        CLIHelper::ensureDirectory(self::$path);

        $cronFile = self::$path . ($className . '.php');

        if (file_exists($cronFile)) {
            if (!CLIHelper::confirm("La tarea cron {$cronFile} ya existe. Desea sobreescribirla?")) {
                CLIErrorHandler::info('Operación cancelada.');
                return;
            }
            unlink($cronFile);
        }

        $template = <<<'EOT'
            <?php

            declare(strict_types=1);

            namespace app\cron;

            use VersaORM\VersaModel;
            use versaWYS\kernel\Loggin;
            use versaWYS\kernel\model\OrmFactory;

            class $className
            {
                public function __construct()
                {
                    global $config, $debug, $versaOrm;
                    $versaOrm = OrmFactory::make(
                        $config
                        + [
                            'versaorm' => [
                                'debug' => $debug,
                            ],
                        ],
                    );
                    VersaModel::setORM($versaOrm);
                }
                public function run(): void
                {
                    // Tu lógica aquí
                    echo "Ejecutando tarea cron: $className\n";
                }
            }
            EOT;

        $template = str_replace('$className', $className, $template);

        if (file_put_contents($cronFile, $template)) {
            chmod($cronFile, 0o775);
            CLIErrorHandler::success("Tarea cron {$cronFile} creada exitosamente.");
        } else {
            CLIErrorHandler::warning("No se pudo crear el archivo: {$cronFile}");
        }
    }

    public static function listCrons(): void
    {
        if (!is_dir(self::$path)) {
            CLIErrorHandler::info('No hay tareas cron creadas (directorio no existe).');
            return;
        }

        $files = glob(self::$path . '*.php');
        if (empty($files)) {
            CLIErrorHandler::info('No hay tareas cron creadas.');
            return;
        }

        CLIErrorHandler::info('Tareas cron disponibles:');
        foreach ($files as $file) {
            $name = basename($file, '.php');
            echo " - {$name}\n";
        }
    }

    public static function runCron(string $name): void
    {
        $className = 'app\\cron\\' . ucfirst($name);

        if (!class_exists($className)) {
            CLIErrorHandler::warning("Error: La clase de cron '{$className}' no existe.");
            return;
        }

        if (!method_exists($className, 'run')) {
            CLIErrorHandler::warning("Error: La clase '{$className}' no tiene un método run().");
            return;
        }

        try {
            CLIErrorHandler::info("Iniciando ejecución de {$name}...");
            $startTime = microtime(true);

            (new $className())->run();

            $duration = round(microtime(true) - $startTime, 4);
            CLIErrorHandler::success("Ejecución finalizada en {$duration}s.");
        } catch (Exception $e) {
            CLIErrorHandler::handleGeneralError($e, "Ejecutando cron {$name}");
        }
    }
}
