<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use versaWYS\kernel\RedBeanCnn;

//TODO: validar si el modo debug está activo y preguntar si está seguro

class MigrationManager extends RedBeanCnn
{
    private static string $path = 'app/migrations/';
    private static string $pathClass = '\\app\\migrations\\';

    /**
     * @throws RedException
     */
    public function __construct()
    {
        $this->connet();
        R::freeze(false);
        R::debug(true, 1);
    }
    public function __destruct()
    {
        R::freeze();
        $this->closeDB();
    }

    public static function runUP($close = true): void
    {
        $className = '';
        try {
            $migrations = glob(self::$path . '*.php');
            $c = 0;
            foreach ($migrations as $migration) {
                $className = basename($migration, '.php');
                $fullClassName = self::$pathClass . $className;
                $fullPathClassName = self::$path . $className . '.php';

                if (self::checkIfExecuted($className)) {
                    continue;
                }

                //check if file exists
                if (!file_exists($fullPathClassName)) {
                    echo "No existe la migración $className.php\n";
                    exit();
                }

                echo "Ejecutando... $className....:";
                $result = $fullClassName::up();
                echo "{$result['message']}\n";
                $c++;

                if (!$result['success']) {
                    echo "Error: {$result['message']}\n";
                    exit();
                }

                $manager = R::dispense('versamigrations');
                $manager->name = $className;
                $manager->created_at = date('Y-m-d H:i:s');
                R::store($manager);
            }
            if ($c == 0) {
                echo "No hay migraciones pendientes.\n";
            } else {
                echo "Migraciones ejecutadas con éxito.\n";
            }

            if ($close) {
                exit();
            }
        } catch (Exception $e) {
            echo "Error al ejecutar la migración $className: {$e->getMessage()}\n";
            exit();
        }
    }

    /**
     * @throws RedException
     */
    public function runDown($fileDown): void
    {
        if (!file_exists(self::$path . "$fileDown.php")) {
            echo "No existe la migración $fileDown.php\n";
            exit();
        }

        $className = basename($fileDown, '.php');
        $fullClassName = self::$pathClass . $className;

        echo "Verificando migración $className...";
        if (!self::checkIfExecuted($className)) {
            echo "No ejecutada.\n";

            exit();
        }

        echo 'Ejecutando...';
        $result = $fullClassName::down();
        echo "{$result['message']}\n";

        if (!$result['success']) {
            echo "Error: {$result['message']}\n";

            exit();
        }
        try {
            R::exec("DELETE FROM versamigrations WHERE name = '$className'");

            echo "Migración ejecutada con éxito.\n";

            exit();
        } catch (Exception $e) {
            echo "Error al ejecutar la migración $className: {$e->getMessage()}\n";

            exit();
        }
    }

    public static function createMigration($name): void
    {
        $date = date('YmdHis');
        $migrationName = "m{$date}_$name";
        $migrationFile = self::$path . "$migrationName.php";

        if (file_exists($migrationFile)) {
            echo "La migración $migrationFile ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen('php://stdin', 'r');
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";
                exit();
            }
            unlink($migrationFile);
        }
        $template = <<<'EOT'
<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class $migrationName {
    public static function up() {
        try {
            // Agrega tu lógica de migración aquí
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public static function down() {
        try {
            // Agrega tu lógica para revertir la migración aquí
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
EOT;

        $template = str_replace('$migrationName', $migrationName, $template);

        file_put_contents($migrationFile, $template);
        echo "Migración $migrationFile creada.\n";
    }

    public static function checkIfExecuted($migration): bool
    {
        $migrations = R::findAll('versamigrations');
        foreach ($migrations as $m) {
            if ($m->name == $migration) {
                return true;
            }
        }
        return false;
    }

    public function rollbackAll(): void
    {
        $migrations = R::getAll('SELECT * FROM versamigrations ORDER BY id DESC');
        foreach ($migrations as $m) {
            echo "Ejecutando rollback... {$m['name']}... :";

            if (!file_exists(self::$path . $m['name'] . '.php')) {
                echo 'No existe la migración ' . $m['name'] . ".php\n";
                R::exec("DELETE FROM versamigrations WHERE name = '{$m['name']}'");
                continue;
            }

            $className = basename($m['name'], '.php');
            $fullClassName = self::$pathClass . $className;

            $result = $fullClassName::down();
            echo "{$result['message']}\n";

            if (!$result['success']) {
                echo "Error: {$result['message']}\n";
                exit();
            }
        }
    }

    public function rollback(): void
    {
        $migrations = R::getAll('SELECT * FROM versamigrations');
        $migrations = array_reverse($migrations);
        $this->runDown($migrations[0]['name']);
    }

    public function refresh($runSeeders = false): void
    {
        $this->rollbackAll();
        echo "\n";
        echo "\n";
        echo "Ejecutando migraciones...\n";
        $this->runUP(false);

        if ($runSeeders) {
            echo "\n";
            echo "\n";
            echo "Ejecutando seeders...\n";
            (new SeederManager())->runAllSeeders();
        }
    }
}
