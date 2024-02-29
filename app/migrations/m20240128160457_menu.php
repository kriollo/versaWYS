<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class m20240128160457_menu
{
    public static function up()
    {
        try {

            R::exec("CREATE TABLE IF NOT EXISTS `versamenu` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `nombre` VARCHAR(255) NOT NULL,
                `seccion` VARCHAR(255) NOT NULL,
                `descripcion` VARCHAR(255) NOT NULL,
                `icono` VARCHAR(255) NOT NULL,
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

    public static function down()
    {
        try {

            R::exec("DROP TABLE IF EXISTS `versamenu`;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
