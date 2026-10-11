<?php

declare(strict_types=1);

namespace app\seeders;

use VersaORM\VersaORMException;
use versaWYS\kernel\model\VersaORMModel;

class S202502_PerfilDefaultSeeder extends VersaORMModel
{
    public static function run(): array
    {
        $instance = new self();

        return $instance->execute();
    }

    private function execute(): array
    {
        try {
            $perfiles = [
                [
                    'id_empresa' => 1,
                    'nombre' => 'Personalizado',
                    'pagina_inicio' => 'dashboard',
                    'estado' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'id_empresa' => 1,
                    'nombre' => 'Usuario',
                    'pagina_inicio' => 'dashboard',
                    'estado' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'id_empresa' => 1,
                    'nombre' => 'Invitado',
                    'pagina_inicio' => 'dashboard',
                    'estado' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
            ];

            foreach ($perfiles as $currentPerfil) {
                $perfil = $this->dispense('versa_perfil');
                $perfil->id_empresa = $currentPerfil['id_empresa'];
                $perfil->nombre = $currentPerfil['nombre'];
                $perfil->pagina_inicio = $currentPerfil['pagina_inicio'];
                $perfil->estado = $currentPerfil['estado'];
                $perfil->created_at = $currentPerfil['created_at'];
                $perfil->updated_at = $currentPerfil['updated_at'];
                $perfil->store();
            }

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }
}
