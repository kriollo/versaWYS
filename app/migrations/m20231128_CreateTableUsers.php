<?php

declare(strict_types=1);

namespace app\migrations;

use versaWYS\kernel\RedBeanCnn;

class m20231128_CreateTableUsers extends RedBeanCnn
{
    public function up(): array
    {
        try {
            $this->exec("CREATE TABLE `versausers` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `tokenid` varchar(255) NOT NULL,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `password` varchar(255) NOT NULL,
                `created_at` datetime NOT NULL,
                `updated_at` datetime NOT NULL,
                `role` varchar(255) NOT NULL,
                `status` tinyint(1) NOT NULL,
                `avatar` varchar(255) DEFAULT NULL,
                `id_perfil` int(11) DEFAULT NULL,
                `pagina_inicio` varchar(255) DEFAULT 'dashboard',
                PRIMARY KEY (`id`),
                UNIQUE KEY `email` (`email`)  USING BTREE,
                UNIQUE KEY `tokenid` (`tokenid`)  USING BTREE,
                INDEX `id_perfil` (`id_perfil`) USING BTREE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->exec('DROP TABLE `versausers`;');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
