<?php

declare(strict_types=1);

namespace app\seeders;

use RedBeanPHP\R;
use versaWYS\kernel\helpers\Functions;

class UsersSeeder
{
    public static function run()
    {
        try {
            $users = R::dispense('versausers');
            $users->tokenid = Functions::generateCSRFToken();
            $users->name = 'admin';
            $users->email = 'admin@wys.cl';
            $users->password = Functions::hash('admin2023');
            $users->created_at = date('Y-m-d H:i:s');
            $users->updated_at = date('Y-m-d H:i:s');
            $users->role = 'admin';
            $users->id_perfil = 0;
            $users->pagina_inicio = 'admin/usuarios';
            $users->status = 1;
            R::store($users);

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
