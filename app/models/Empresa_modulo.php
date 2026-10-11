<?php

declare(strict_types=1);

namespace app\models;

use Exception;
use Override;
use versaWYS\kernel\model\VersaORMModel;

class Empresa_modulo extends VersaORMModel
{
    protected string $table = 'empresa_modulo';

    /**
     * Define las reglas de validación para el modelo.
     *
     * @return array
     */
    protected array $rules = [
        'id_empresa' => ['required', 'int'],
        'id_modulo' => ['required', 'int'],
    ];

    /**
     * Define los tipos de propiedades para el modelo.
     *
     * @throws Exception
     */
    #[Override]
    public static function definePropertyTypes(): array
    {
        return [
            'id' => ['type' => 'int', 'nullable' => false, 'auto_increment' => true],
            'id_empresa' => ['type' => 'int', 'nullable' => false],
            'id_modulo' => ['type' => 'int', 'nullable' => false],
            'created_at' => ['type' => 'varchar', 'nullable' => false],
            'updated_at' => ['type' => 'varchar', 'nullable' => false],
        ];
    }

    /**
     * Get all empresa_modulo.
     *
     * Retrieves all the empresa_modulo from the database.
     *
     * @return array an array of user records
     */
    public function all(): array
    {
        return $this->getAll('SELECT * FROM ' . self::$table);
    }

    public function deleteModulosByEmpresa($idEmpresa): void
    {
        $this->exec('DELETE FROM empresa_modulo WHERE id_empresa = ?', [$idEmpresa]);
    }

    public function saveEmpresaModulo(array $params): void
    {
        $modulo = $this->dispense($this->table);
        $modulo->id_empresa = $params['id_empresa'];
        $modulo->id_modulo = $params['id_modulo'];
        $modulo->fill($params);
        $modulo->store();
    }

    public function getModulesByEmpresa(int $idEmpresa): array
    {
        $sql = 'SELECT
                    m.id,
                    m.nombre,
                    CASE WHEN em.id_modulo IS NULL THEN false ELSE true END AS asociado,
                    m.icono,
                    m.fill
                FROM
                    versa_menu m
                    LEFT JOIN empresa_modulo em ON m.id = em.id_modulo
                    AND em.id_empresa = ?
                WHERE
                    m.estado = true
                ORDER BY
                    m.seccion,
                    m.posicion';

        return $this->getAll($sql, [$idEmpresa]);
    }
}
