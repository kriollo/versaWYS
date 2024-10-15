<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class m20240129153807_perfil
{
    public static function up()
    {
        try {
            R::exec("CREATE TABLE IF NOT EXISTS `versaperfil` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `nombre` VARCHAR(255) NOT NULL,
                `pagina_inicio` VARCHAR(255) DEFAULT 'dashboard',
                `estado` INT(11) NOT NULL DEFAULT '1',
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
            R::exec('DROP TABLE IF EXISTS `versaperfil`;');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
