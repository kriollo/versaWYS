<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class m20241020025440_expirationpassuser
{
    public static function up()
    {
        try {
            R::exec('ALTER TABLE versausers ADD COLUMN expiration_pass DATETIME NULL DEFAULT NULL AFTER password');
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public static function down()
    {
        try {
            R::exec('ALTER TABLE versausers DROP COLUMN expiration_pass');
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
