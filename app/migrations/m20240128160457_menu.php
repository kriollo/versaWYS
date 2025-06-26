<?php

declare(strict_types=1);

namespace app\migrations;

use versaWYS\kernel\RedBeanCnn;

class m20240128160457_menu extends RedBeanCnn
{
    public function up()
    {
        try {
            $this->exec("CREATE TABLE IF NOT EXISTS `versamenu` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `nombre` VARCHAR(255) NOT NULL,
                `seccion` VARCHAR(255) NOT NULL,
                `descripcion` VARCHAR(255) NOT NULL,
                `icono` LONGTEXT NULL,
                `fill` TINYINT(1) NOT NULL DEFAULT '0',
                `posicion` INT(11) NOT NULL DEFAULT '0',
                `estado` TINYINT(1) NOT NULL DEFAULT '1',
                `submenu` TINYINT(1) NOT NULL DEFAULT '0',
                `url` VARCHAR(255) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public function down()
    {
        try {
            $this->exec('DROP TABLE IF EXISTS `versamenu`;');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
