<?php

declare(strict_types=1);

namespace app\seeders;

use VersaORM\VersaORMException;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\model\VersaORMModel;

class S202501_EmpresaSeeder extends VersaORMModel
{
    public static function run(): array
    {
        $instance = new self();

        return $instance->execute();
    }

    private function execute(): array
    {
        try {
            $empresa = [
                'nombre' => 'Mi Empresa',
                'token' => Functions::generateCSRFToken(),
                'rut' => '11111111-1',
                'telefono' => '',
                'email' => 'admin@example.com',
                'estado' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $empresaBean = $this->dispense('empresas');
            $empresaBean->token_empresa = $empresa['token'];
            $empresaBean->nombre = $empresa['nombre'];
            $empresaBean->rut = $empresa['rut'];
            $empresaBean->telefono = $empresa['telefono'];
            $empresaBean->email = $empresa['email'];
            $empresaBean->estado = $empresa['estado'];
            $empresaBean->created_at = $empresa['created_at'];
            $empresaBean->updated_at = $empresa['updated_at'];
            $empresaBean->store();

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }
}
