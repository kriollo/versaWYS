<?php

declare(strict_types=1);

namespace app\migrations;

use versaWYS\kernel\RedBeanCnn;

class m20240128160458_submenu extends RedBeanCnn
{
    public function up()
    {
        try {
            $this->exec("CREATE TABLE IF NOT EXISTS `versasubmenu` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `id_menu` INT(11) NOT NULL,
                `nombre` VARCHAR(255) NOT NULL,
                `descripcion` VARCHAR(255) NOT NULL,
                `url` VARCHAR(255) NOT NULL,
                `icono` VARCHAR(255) NOT NULL DEFAULT 'bi bi-circle',
                `posicion` INT(11) NOT NULL DEFAULT '0',
                `estado` TINYINT(1) NOT NULL DEFAULT '1',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `fk_submenu_menu_idx` (`id_menu` ASC) VISIBLE,
                CONSTRAINT `fk_submenu_menu_idx`
                    FOREIGN KEY (`id_menu`)
                    REFERENCES `versamenu` (`id`)
                    ON DELETE NO ACTION
                    ON UPDATE NO ACTION
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public function down()
    {
        try {
            $this->exec('DROP TABLE IF EXISTS `versasubmenu`;');

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
