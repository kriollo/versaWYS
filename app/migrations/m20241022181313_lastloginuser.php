<?php

declare(strict_types=1);

namespace app\migrations;

use versaWYS\kernel\RedBeanCnn;

class m20241022181313_lastloginuser extends RedBeanCnn
{
    public  function up()
    {
        try {
            $this->exec('ALTER TABLE versausers ADD COLUMN last_login DATETIME NULL DEFAULT NULL AFTER expiration_pass');
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public  function down()
    {
        try {
            $this->exec('ALTER TABLE versausers DROP COLUMN last_login');
            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
