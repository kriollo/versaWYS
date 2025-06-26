<?php

declare(strict_types=1);

namespace app\migrations;

use versaWYS\kernel\RedBeanCnn;

class m20231127_CreateTableMigration extends RedBeanCnn
{
    public function up(): array
    {
        try {
            $this->exec("CREATE TABLE `versamigrations` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `created_at` datetime NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->exec('DROP TABLE `versamigrations`;');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
