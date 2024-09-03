<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class ModelManager
{
    private static string $path = 'app/Models/';
    public static function createModel(string $modelName): void
    {
        $originalModelName = strtolower($modelName);
        $modelName = ucfirst($modelName);

        echo "Creando modelo $modelName...\n";
        $modelFile = self::$path . "$modelName.php";

        if (file_exists($modelFile)) {
            echo "El modelo $modelFile ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen('php://stdin', 'r');
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";
                exit();
            }
            unlink($modelFile);
        }
        $template = <<<'EOT'
<?php

declare(strict_types=1);

namespace app\models;

use RedBeanPHP\R;
use versaWYS\kernel\RedBeanCnn;

class $modelName extends \RedBeanPHP\SimpleModel
{
    protected static $table = '$originalModelName';

    /**
     * Get all $originalModelName.
     *
     * Retrieves all the $originalModelName from the database.
     *
     * @return array An array of user records.
     */
    public function all()
    {
        return R::getAll('SELECT * FROM $originalModelName');
    }

    public function __construct()
    {
        (new RedBeanCnn())->setup();
    }

    public function __destruct()
    {
        R::close();
    }
}
EOT;

        $template = str_replace('$modelName', $modelName, $template);
        $template = str_replace('$originalModelName', $originalModelName, $template);
        file_put_contents($modelFile, $template);
        echo "Modelo $modelFile creado.\n";
    }
    public static function deleteModel(string $modelName): void
    {
        $modelName = ucfirst($modelName);

        echo "Eliminando modelo $modelName...\n";
        $modelFile = self::$path . "$modelName.php";

        if (!file_exists($modelFile)) {
            echo "El modelo $modelFile no existe.\n";
            exit();
        }

        unlink($modelFile);
        echo "Modelo $modelFile eliminado.\n";
    }
}
