<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use versaWYS\kernel\RedBeanCnn;

class SeederManager extends RedBeanCnn
{
    private static string $path = 'app/seeders/';
    private static string $pathClass = '\\app\\seeders\\';

    /**
     * @throws RedException
     */
    public function __construct()
    {
        $this->connet();
    }
    public function __destruct()
    {
        $this->closeDB();
    }

    public static function createSeeder($seederName): void
    {
        $seederName = ucfirst($seederName) . 'Seeder';

        echo "Creando controlador $seederName...\n";
        $SeedersFile = self::$path . "$seederName.php";

        if (file_exists($SeedersFile)) {
            echo "El Controllador $SeedersFile ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen('php://stdin', 'r');
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";
                exit();
            }
            unlink($SeedersFile);
        }
        $template = <<<'EOT'
<?php

declare(strict_types=1);

namespace app\seeders;

use RedBeanPHP\R;

class $seederName {
    public static function run() {
        try {
            // Agrega tu lógica de aquí
            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
EOT;

        $template = str_replace('$seederName', $seederName, $template);

        file_put_contents($SeedersFile, $template);
        echo "Migración $SeedersFile creada.\n";
    }

    public static function runSeeder($seederName): void
    {
        $seederName = ucfirst($seederName);
        $seederClass = self::$pathClass . $seederName;

        if (!class_exists($seederClass)) {
            echo "El Seeder $seederName no existe.\n";
            exit();
        }

        echo "Ejecutando... $seederName....:";
        $seeder = new $seederClass();
        $result = $seeder::run();

        if ($result['success']) {
            echo $result['message'] . "\n";
        } else {
            echo 'Error: ' . $result['message'] . "\n";
        }
    }

    public static function runAllSeeders(): void
    {
        $files = scandir(self::$path);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $seederName = pathinfo($file, PATHINFO_FILENAME);
            self::runSeeder($seederName);
        }
    }
}
