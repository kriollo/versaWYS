<?php

declare(strict_types=1);

namespace app\models;

use Exception;
use Override;
use SensitiveParameter;
use VersaORM\VersaModel;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\model\VersaORMModel;

class Empresas extends VersaORMModel
{
    protected string $table = 'empresas';

    protected array $rules = [
        'rut' => ['required', 'rut_chile'],
        'nombre' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:100'],
    ];

    #[Override]
    public static function definePropertyTypes(): array
    {
        return [
            'id' => ['type' => 'int', 'nullable' => false, 'auto_increment' => true],
            'token_empresa' => ['type' => 'string', 'max_length' => 255, 'nullable' => false],
            'rut' => ['type' => 'string', 'max_length' => 20, 'nullable' => false],
            'nombre' => ['type' => 'string', 'max_length' => 100, 'nullable' => false],
            'telefono' => ['type' => 'string', 'max_length' => 20, 'nullable' => false],
            'email' => ['type' => 'string', 'max_length' => 100, 'nullable' => false],
            'estado' => ['type' => 'bool', 'nullable' => false],
            'created_at' => ['type' => 'varchar', 'nullable' => false],
            'updated_at' => ['type' => 'varchar', 'nullable' => false],
        ];
    }

    /**
     * Get all empresas paginated.
     *
     * Retrieves all the empresas from the database paginated.
     *
     * @return array an array of user records
     */
    // @mago-expect lint:excessive-parameter-list (firma usada por los controladores de cada proyecto)
    public function getEmpresasPaginated(
        array $fields = ['*'],
        string $where = '',
        string $order = 'id ASC',
        int $limit = 15,
        int $offset = 0,
        string $pathLogo = '',
        array $bindings = [],
    ): array {
        $filter = trim($where) !== '' ? $where : '';

        $qb = $this
            ->qb()
            ->lazy()
            ->select($fields)
            ->selectRaw(sprintf("COUNT(*) OVER() AS total_count, CONCAT('/%s/', logo) AS logo", $pathLogo))
            ->from('empresas');

        if ($filter !== '' && $filter !== '0') {
            $qb->whereRaw($filter, $bindings);
        }

        $qb->orderByRaw($order)->limit($limit)->offset($offset);
        $result = $qb->collect();

        return [
            'total' => (int) ($result[0]['total_count'] ?? 0),
            'data' => $result,
        ];
    }

    /**
     * Get all empresas.
     *
     * Retrieves all the empresas from the database.
     *
     * @return array an array of user records
     */
    public function all(string $estado = 'all'): array
    {
        if ($estado === 'all') {
            return $this->getAll($this->table);
        }

        return $this->getAll('SELECT * FROM ' . $this->table . ' WHERE estado = ?', [$estado]);
    }

    public function getEmpresaByRut(string $rut): ?array
    {
        $empresa = $this->findOne($this->table, $rut, 'rut');

        return $empresa instanceof VersaModel ? $empresa->export() : null;
    }

    public function getEmpresaById(int $id): ?array
    {
        $empresa = $this->findOne($this->table, $id);

        return $empresa instanceof VersaModel ? $empresa->export() : null;
    }

    public function getEmpresaByToken(#[SensitiveParameter] string $token): ?array
    {
        $empresa = $this->load($this->table, $token, 'token_empresa');

        return $empresa instanceof VersaModel ? $empresa->export() : null;
    }

    public function saveEmpresa(array $data): int
    {
        $empresa = $data['action'] === 'edit' ? $this->load($this->table, $data['id']) : $this->dispense($this->table);

        if ($data['action'] !== 'edit') {
            $empresa->created_at = date('Y-m-d H:i:s');
            $empresa->token_empresa = Functions::generateCSRFToken();
        }

        if ($empresa->rut !== $data['rut'] && $empresa->rut !== null) {
            $existingEmpresa = $this->load($this->table, $data['rut'], 'rut');
            if ($existingEmpresa instanceof VersaModel) {
                throw new Exception('El RUT de la empresa ya existe');
            }
        }

        $empresa->rut = $data['rut'];
        $empresa->nombre = $data['nombre'];
        $empresa->telefono = $data['telefono'] ?? '';
        $empresa->email = $data['email'] ?? '';
        $empresa->updated_at = date('Y-m-d H:i:s');

        return $empresa->store();
    }

    public function updateLogo($idempresa, $path): void
    {
        $empresaBean = $this->load($this->table, $idempresa);
        $empresaBean->logo = $path;
        $empresaBean->store();
    }

    public function changeStatus(array $params): int
    {
        $empresa = $this->load($this->table, $params['id']);
        if (!$empresa instanceof VersaModel) {
            return 0;
        }

        $empresa->estado = $params['estado'];
        $empresa->store();

        return $empresa->id;
    }

    public function getEmpresasStats(): array
    {
        return $this->getRow(
            'SELECT COUNT(id) as count, MAX(updated_at) as last_updated FROM empresas WHERE estado = 1',
        );
    }

    public function getModulesByEmpresa(int $idEmpresa): array
    {
        $empresaModuloModel = new Empresa_modulo();

        return $empresaModuloModel->getModulesByEmpresa($idEmpresa);
    }

    public function saveModuloEmpresaAsociado(array $params): int
    {
        $empresa = $this->load($this->table, $params['id']);
        if (!$empresa instanceof VersaModel) {
            throw new Exception('Empresa no encontrada');
        }

        $empresaModuloModel = new Empresa_modulo();
        $empresaModuloModel->deleteModulosByEmpresa($params['id']);

        $paramsSave = [];
        $modulos = (array) json_decode($params['modulos'] ?? [], true);
        foreach ($modulos as $idModulo) {
            if (!$idModulo['asociado']) {
                continue;
            }

            $paramsSave['id_modulo'] = $idModulo['id'];
            $paramsSave['id_empresa'] = $params['id'];
            $empresaModuloModel->saveEmpresaModulo($paramsSave);
        }

        return $empresa->id;
    }

    protected function validateSingleRule(string $field, mixed $value, string $rule): ?string
    {
        if ($rule === 'rut_chile') {
            if (!Functions::validarRut($value)) {
                return sprintf('El campo %s no es un RUT chileno válido.', $field);
            }

            return null; // Validación exitosa
        }

        return parent::validateSingleRule($field, $value, $rule);
    }
}
