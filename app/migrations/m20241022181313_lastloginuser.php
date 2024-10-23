<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class m20241022181313_lastloginuser
{
    public static function up()
    {
        try {
            R::exec('ALTER TABLE versausers ADD COLUMN last_login DATETIME NULL DEFAULT NULL AFTER expiration_pass');
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public static function down()
    {
        try {
            R::exec('ALTER TABLE versausers DROP COLUMN last_login');
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}