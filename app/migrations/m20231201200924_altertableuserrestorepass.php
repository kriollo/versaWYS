<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class m20231201200924_altertableuserrestorepass
{
    public static function up()
    {
        try {

            R::exec("ALTER TABLE `users`
            ADD COLUMN `restore_token` VARCHAR(255) NULL AFTER `status`;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public static function down()
    {
        try {

            R::exec("ALTER TABLE `users`
            DROP COLUMN `restore_token`;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}