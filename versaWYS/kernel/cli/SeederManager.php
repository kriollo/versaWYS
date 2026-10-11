<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use VersaORM\VersaModel;
use VersaORM\VersaORMException;
use versaWYS\kernel\model\OrmFactory;
use versaWYS\kernel\model\VersaORMModel;

class SeederManager extends VersaORMModel
{
    private static string $path = 'app/seeders/';

    private static string $pathClass = '\\app\\seeders\\';

    public function __construct()
    {
        global $versaOrm;

        $config = ConfigManager::getConfig();
        $debug = $config['build']['debug'] ?? false;

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

    public static function createSeeder($seederName): void
    {
        $seederName = ucfirst($seederName) . 'Seeder';

        CLIErrorHandler::info("Creando seeder {$seederName}...");
        $SeedersFile = self::$path . ($seederName . '.php');

        if (file_exists($SeedersFile)) {
            if (!CLIHelper::confirm("El seeder {$SeedersFile} ya existe. Desea sobreescribirlo?")) {
                CLIErrorHandler::info('Operación cancelada.');
                exit();
            }

            unlink($SeedersFile);
        }

        $template = <<<'EOT'
            <?php

            declare(strict_types=1);

            namespace app\seeders;

            use versaWYS\kernel\model\VersaORMModel;
            use VersaORM\VersaORMException;

            class $seederName extends VersaORMModel {
                public function run(): array {
                    try {
                        // Agrega tu lógica de aquí
                        return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
                    } catch (VersaORMException $versaormException) {
                        return ['message' => $versaormException->getMessage(), 'success' => false];
                    }
                }
            }
            EOT;

        $template = str_replace('$seederName', $seederName, $template);

        CLIHelper::ensureDirectory(self::$path);

        if (file_put_contents($SeedersFile, $template)) {
            chmod($SeedersFile, 0o775);
            CLIErrorHandler::success("Seeder {$SeedersFile} creada.");
        } else {
            CLIErrorHandler::warning("No se pudo crear el archivo: {$SeedersFile}");
        }
    }

    public static function runSeeder($seederName): void
    {
        try {
            $seederName = ucfirst($seederName);
            $seederClass = self::$pathClass . $seederName;

            if (!class_exists($seederClass)) {
                CLIErrorHandler::warning("El Seeder {$seederName} no existe.");
                return;
            }

            CLIErrorHandler::info(sprintf('Ejecutando... %s....:', $seederName));
            $seeder = new $seederClass();
            $result = $seeder->run();

            if ($result['success']) {
                echo $result['message'] . "\n";
            } else {
                CLIErrorHandler::warning('Error: ' . $result['message']);
            }
        } catch (VersaORMException $versaormException) {
            CLIErrorHandler::handleDatabaseError($versaormException);
        }
    }

    /**
     * Ejecuta todos los seeders de forma silenciosa.
     */
    public static function runAllSeedersQuiet($verbose = false): void
    {
        $files = glob(self::$path . '*.php');

        // Ordenar alfanuméricamente (s01, s02, s10 en orden correcto)
        if ($files === false) {
            $files = [];
        }
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        $count = 0;

        foreach ($files as $file) {
            $seederName = pathinfo($file, PATHINFO_FILENAME);

            if ($verbose) {
                self::runSeeder($seederName);
            } else {
                self::runSeederQuiet($seederName);
            }

            $count++;
        }

        if (!$verbose && $count > 0) {
            echo "   🌱 {$count} seeder(s) ejecutado(s)\n";
        }
    }

    /**
     * Ejecuta un seeder de forma silenciosa.
     */
    public static function runSeederQuiet($seederName): void
    {
        $seederName = ucfirst($seederName);
        $seederClass = self::$pathClass . $seederName;

        if (!class_exists($seederClass)) {
            CLIErrorHandler::warning("El Seeder {$seederName} no existe.");
            exit();
        }

        $seeder = new $seederClass();
        $result = $seeder->run();

        if (!$result['success']) {
            CLIErrorHandler::warning('Error en ' . $seederName . ': ' . $result['message']);
            exit();
        }
    }

    public static function runAllSeeders(): void
    {
        $files = glob(self::$path . '*.php');

        // Ordenar alfanuméricamente (s01, s02, s10 en orden correcto)
        if ($files === false) {
            $files = [];
        }
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($files as $file) {
            $seederName = pathinfo($file, PATHINFO_FILENAME);
            self::runSeeder($seederName);
        }
    }
}
