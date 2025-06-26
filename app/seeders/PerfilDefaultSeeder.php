<?php

declare(strict_types=1);

namespace app\seeders;

use versaWYS\kernel\RedBeanCnn;

class PerfilDefaultSeeder extends RedBeanCnn
{
    public static function run()
    {
        $instance = new self();
        return $instance->execute();
    }

    private function execute()
    {
        $this->connet();
        try {
            $perfiles = [
                [
                    'nombre' => 'Personalizado',
                    'pagina_inicio' => 'dashboard',
                    'estado' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'nombre' => 'Usuario',
                    'pagina_inicio' => 'dashboard',
                    'estado' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'nombre' => 'Invitado',
                    'pagina_inicio' => 'dashboard',
                    'estado' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
            ];

            foreach ($perfiles as $currentPerfil) {
                $perfil = $this->dispense('versaperfil');
                $perfil->nombre = $currentPerfil['nombre'];
                $perfil->pagina_inicio = $currentPerfil['pagina_inicio'];
                $perfil->estado = $currentPerfil['estado'];
                $perfil->created_at = $currentPerfil['created_at'];
                $perfil->updated_at = $currentPerfil['updated_at'];
                $this->store($perfil);
            }

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        } finally {
            $this->closeDB();
        }
    }
}
