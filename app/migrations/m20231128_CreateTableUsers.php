<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;
use versaWYS\kernel\helpers\Functions;

class m20231128_CreateTableUsers
{
    public static function up(): array
    {
        try {
            R::exec("CREATE TABLE `versausers` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `tokenid` varchar(255) NOT NULL,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `password` varchar(255) NOT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                `role` varchar(255) NOT NULL,
                `status` tinyint(1) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`),
                UNIQUE KEY `tokenid` (`tokenid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            $users = R::dispense('users');
            $users->tokenid = Functions::generateCSRFToken();
            $users->name = 'admin';
            $users->email = 'admin@wys.cl';
            $users->password = Functions::hash('admin2023');
            $users->created_at = date('Y-m-d H:i:s');
            $users->updated_at = date('Y-m-d H:i:s');
            $users->role = 'admin';
            $users->status = 1;
            R::store($users);

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];

        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public static function down(): void
    {
        R::exec("DROP TABLE `versausers`;");
    }
}
