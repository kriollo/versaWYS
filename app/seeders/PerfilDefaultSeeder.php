<?php

declare(strict_types=1);

namespace app\seeders;

use RedBeanPHP\R;

class PerfilDefaultSeeder
{
    public static function run()
    {
        try {
            $perfil = R::dispense('versaperfil');
            $perfil->nombre = 'Personalizado';
            $perfil->pagina_inicio = 'admin/dashboard';
            $perfil->estado = 1;
            $perfil->created_at = date('Y-m-d H:i:s');
            $perfil->updated_at = date('Y-m-d H:i:s');
            R::store($perfil);

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
