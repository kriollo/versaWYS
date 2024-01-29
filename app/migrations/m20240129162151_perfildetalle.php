<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class m20240129162151_perfildetalle
{
    public static function up()
    {
        try {

            R::exec("CREATE TABLE IF NOT EXISTS `versaperfildetalle` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `perfil_id` INT(11) NOT NULL,
                `modulo_id` INT(11) NOT NULL,
                `estado` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                INDEX `fk_perfildetalle_perfil_idx` (`perfil_id` ASC) VISIBLE,
                INDEX `fk_perfildetalle_modulo_idx` (`modulo_id` ASC) VISIBLE,
                CONSTRAINT `fk_perfildetalle_perfil`
                  FOREIGN KEY (`perfil_id`)
                  REFERENCES `perfil` (`id`)
                  ON DELETE NO ACTION
                  ON UPDATE NO ACTION,
                CONSTRAINT `fk_perfildetalle_modulo`
                  FOREIGN KEY (`modulo_id`)
                  REFERENCES `modulo` (`id`)
                  ON DELETE NO ACTION
                  ON UPDATE NO ACTION)
              ENGINE = InnoDB
              DEFAULT CHARSET = utf8mb4
              COLLATE = utf8mb4_unicode_ci;");

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
