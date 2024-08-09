<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;
use RedBeanPHP\R;
use RedBeanPHP\RedException;
use versaWYS\kernel\RedBeanCnn;

class MigrationManager
{

    private static string $path = 'app/migrations/';
    private static string $pathClass = '\\app\\migrations\\';

    /**
     * @throws RedException
     */
    private static function inicializate(): void
    {
        (new RedBeanCnn())->setup();
        R::freeze(false);
        R::debug(true, 1);
    }
    public static function close(): void
    {
        R::freeze();
        R::close();
    }

    public static function runUP(): void
    {
        $className = '';
        try {
            self::inicializate();

            $migrations = glob(self::$path . '*.php');
            $c = 0;
            foreach ($migrations as $migration) {
                $className = basename($migration, '.php');
                $fullClassName = self::$pathClass . $className;

                if (self::checkIfExecuted($className)) {
                    continue;
                }

                echo "Ejecutando... $className:";
                $result = $fullClassName::up();
                echo "{$result['message']}\n";
                $c++;

                if (!$result['success']) {
                    echo "Error: {$result['message']}\n";
                    self::close();
                    exit;
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
            self::close();
            exit;
        } catch (Exception $e) {
            echo "Error al ejecutar la migración $className: {$e->getMessage()}\n";
            self::close();
            exit;
        }
    }

    /**
     * @throws RedException
     */
    public static function runDown($fileDown): void
    {

        self::inicializate();

        if (!file_exists(self::$path . "$fileDown.php")) {
            echo "No existe la migración $fileDown.php\n";
            self::close();
            exit;
        }

        $className = basename($fileDown, '.php');
        $fullClassName = self::$pathClass . $className;


        echo "Verificando migración $className...";
        if (!self::checkIfExecuted($className)) {
            echo "No ejecutada.\n";
            self::close();
            exit;
        }

        echo "Ejecutando...";
        $result = $fullClassName::down();
        echo "{$result['message']}\n";

        if (!$result['success']) {
            echo "Error: {$result['message']}\n";
            self::close();
            exit;
        }
        try {
            R::exec("DELETE FROM versamigrations WHERE name = '$className'");

            echo "Migración ejecutada con éxito.\n";
            self::close();
            exit;
        } catch (Exception $e) {
            echo "Error al ejecutar la migración $className: {$e->getMessage()}\n";
            self::close();
            exit;
        }
    }

    public static function createMigration($name): void
    {

        $date = date('YmdHis');
        $migrationName = "m{$date}_$name";
        $migrationFile = self::$path . "$migrationName.php";

        if (file_exists($migrationFile)) {
            echo "La migración $migrationFile ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";
                exit;
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
}
