<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class m20240129162151_perfildetalle_user
{
    public static function up()
    {
        try {
            R::exec("CREATE TABLE `versaperfildetalleuser` (
                    `id` INT NOT NULL AUTO_INCREMENT,
                    `pagina_inicio` VARCHAR(255) NOT NULL DEFAULT 'dashboard' COLLATE 'utf8mb4_unicode_ci',
                    `perfil_id` INT NOT NULL,
                    `menu_id` INT NOT NULL,
                    `submenu_id` INT NOT NULL,
                    PRIMARY KEY (`id`) USING BTREE
                )
                COLLATE='utf8mb4_unicode_ci'
                ENGINE=InnoDB;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public static function down()
    {
        try {
            // Agrega tu lógica para revertir la migración aquí
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
