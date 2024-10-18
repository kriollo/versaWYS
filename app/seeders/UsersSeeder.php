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
            $users = [
                [
                    'name' => 'admin',
                    'tokenid' => Functions::generateCSRFToken(),
                    'email' => 'admin@wys.cl',
                    'password' => Functions::hash('admin2023'),
                    'role' => 'admin',
                    'id_perfil' => 1,
                    'pagina_inicio' => 'admin/dashboard',
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'user',
                    'tokenid' => Functions::generateCSRFToken(),
                    'email' => 'user@wys.cl',
                    'password' => Functions::hash('user2023'),
                    'role' => 'user',
                    'id_perfil' => 2,
                    'pagina_inicio' => 'admin/dashboard',
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ],
            ];

            foreach ($users as $currentUser) {
                $user = R::dispense('versausers');
                $user->name = $currentUser['name'];
                $user->tokenid = $currentUser['tokenid'];
                $user->email = $currentUser['email'];
                $user->password = $currentUser['password'];
                $user->role = $currentUser['role'];
                $user->id_perfil = $currentUser['id_perfil'];
                $user->pagina_inicio = $currentUser['pagina_inicio'];
                $user->status = $currentUser['status'];
                $user->created_at = $currentUser['created_at'];
                $user->updated_at = $currentUser['updated_at'];
                R::store($user);
            }

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
