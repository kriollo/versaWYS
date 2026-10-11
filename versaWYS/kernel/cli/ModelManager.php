<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class ModelManager
{
    private static string $path = 'app/models/';

    public static function createModel(string $modelName): void
    {
        $originalModelName = strtolower($modelName);
        $modelName = ucfirst($modelName);

        CLIErrorHandler::info("Creando modelo {$modelName}...");
        $modelFile = self::$path . ($modelName . '.php');

        if (file_exists($modelFile)) {
            if (!CLIHelper::confirm("El modelo {$modelFile} ya existe. Desea sobreescribirlo?")) {
                CLIErrorHandler::info('Operación cancelada.');
                exit();
            }

            unlink($modelFile);
        }

        $template = <<<'EOT'
            <?php

            declare(strict_types=1);

            namespace app\models;

            use Exception;
            use VersaORM\VersaModel;
            use versaWYS\kernel\helpers\Functions;
            use versaWYS\kernel\model\VersaORMModel;

            class $modelName extends VersaORMModel
            {
                /**
                 * @var string
                 */
                protected string $table = '$originalModelName';

                /**
                 * Define las reglas de validación para el modelo.
                 *
                 * @return array
                 */
                protected array $rules = [];

                /**
                 * Define los tipos de propiedades para el modelo.
                 *
                 * @throws Exception
                 */
                public static function definePropertyTypes(): array
                {
                    return [
                        'id' => ['type' => 'int', 'nullable' => false, 'auto_increment' => true],
                        'estado' => ['type' => 'bool', 'nullable' => false],
                        'created_at' => ['type' => 'varchar', 'nullable' => false],
                        'updated_at' => ['type' => 'varchar', 'nullable' => false],
                    ];
                }

                /**
                 * Get all $originalModelName.
                 *
                 * Retrieves all the $originalModelName from the database.
                 *
                 * @return array An array of user records.
                 */
                public function all(): array
                {
                    return $this->getAll('SELECT * FROM ' . $this->table);
                }
            }
            EOT;

        $template = str_replace('$modelName', $modelName, $template);
        $template = str_replace('$originalModelName', $originalModelName, $template);

        CLIHelper::ensureDirectory(dirname($modelFile));

        if (file_put_contents($modelFile, $template)) {
            chmod($modelFile, 0o775);
            CLIErrorHandler::success("Modelo {$modelFile} creado.");
        } else {
            CLIErrorHandler::warning("No se pudo crear el archivo: {$modelFile}");
        }
    }

    public static function deleteModel(string $modelName): void
    {
        $modelName = ucfirst($modelName);

        CLIErrorHandler::info("Eliminando modelo {$modelName}...");
        $modelFile = self::$path . ($modelName . '.php');

        if (!file_exists($modelFile)) {
            CLIErrorHandler::warning("El modelo {$modelFile} no existe.");
            exit();
        }

        if (unlink($modelFile)) {
            CLIErrorHandler::success("Modelo {$modelFile} eliminado.");
        } else {
            CLIErrorHandler::warning("No se pudo eliminar: {$modelFile}");
        }
    }
}
