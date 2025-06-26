<?php

declare(strict_types=1);

namespace app\migrations;

use versaWYS\kernel\RedBeanCnn;

class m20240129162151_perfildetalle extends RedBeanCnn
{
    public function up()
    {
        try {
            $this->exec("CREATE TABLE `versaperfildetalle` (
                            `id` INT NOT NULL AUTO_INCREMENT,
                            `perfil_id` INT NOT NULL,
                            `menu_id` INT NOT NULL,
                            `submenu_id` INT NULL DEFAULT NULL,
                            `estado` TINYINT(1) NOT NULL DEFAULT '1',
                            `created_at` DATETIME NOT NULL DEFAULT (CURRENT_TIMESTAMP),
                            `updated_at` DATETIME NOT NULL DEFAULT (CURRENT_TIMESTAMP) ON UPDATE CURRENT_TIMESTAMP,
                            PRIMARY KEY (`id`) USING BTREE,
                            INDEX `fk_perfildetalle_perfil_idx` (`perfil_id`) USING BTREE,
                            INDEX `fk_perfildetalle_menu_idx` (`menu_id`) USING BTREE,
                            INDEX `fk_perfildetalle_submenu` (`submenu_id`) USING BTREE,
                            CONSTRAINT `fk_perfildetalle_menu` FOREIGN KEY (`menu_id`) REFERENCES `versamenu` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION,
                            CONSTRAINT `fk_perfildetalle_perfil` FOREIGN KEY (`perfil_id`) REFERENCES `versaperfil` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
                        )
                        COLLATE='utf8mb4_unicode_ci'
                        ENGINE=InnoDB
                        ;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public function down()
    {
        try {
            // Agrega tu lógica para revertir la migración aquí

            $this->exec('DROP TABLE `versaperfildetalle`;');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
