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
                    'expiration_pass' => date('Y-m-d H:i:s', strtotime('30 days')),
                    'role' => 'admin',
                    'id_perfil' => 1,
                    'pagina_inicio' => 'usuarios',
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s'),
                ],
                [
                    'name' => 'user',
                    'tokenid' => Functions::generateCSRFToken(),
                    'email' => 'user@wys.cl',
                    'password' => Functions::hash('user2023'),
                    'expiration_pass' => date('Y-m-d H:i:s', strtotime('-1 days')),
                    'role' => 'user',
                    'id_perfil' => 2,
                    'pagina_inicio' => 'dashboard',
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s', strtotime('-90 days')),
                ],
                [
                    'name' => 'guest',
                    'tokenid' => Functions::generateCSRFToken(),
                    'email' => 'guest@wys.cl',
                    'password' => Functions::hash('guest2023'),
                    'expiration_pass' => date('Y-m-d H:i:s', strtotime('+90 days')),
                    'role' => 'user',
                    'id_perfil' => 3,
                    'pagina_inicio' => 'dashboard',
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s'),
                ],
            ];

            foreach ($users as $currentUser) {
                $user = R::dispense('versausers');
                $user->name = $currentUser['name'];
                $user->tokenid = $currentUser['tokenid'];
                $user->email = $currentUser['email'];
                $user->password = $currentUser['password'];
                $user->expiration_pass = $currentUser['expiration_pass'];
                $user->role = $currentUser['role'];
                $user->id_perfil = $currentUser['id_perfil'];
                $user->pagina_inicio = $currentUser['pagina_inicio'];
                $user->status = $currentUser['status'];
                $user->created_at = $currentUser['created_at'];
                $user->updated_at = $currentUser['updated_at'];
                $user->last_login = $currentUser['last_login'];
                R::store($user);
            }

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
