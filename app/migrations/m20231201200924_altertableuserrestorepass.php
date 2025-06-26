<?php

declare(strict_types=1);

namespace app\migrations;

use versaWYS\kernel\RedBeanCnn;

class m20231201200924_altertableuserrestorepass extends RedBeanCnn
{
    public function up()
    {
        try {
            $this->exec("ALTER TABLE `versausers`
            ADD COLUMN `restore_token` VARCHAR(255) NULL AFTER `status`;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }

    public function down()
    {
        try {
            $this->exec("ALTER TABLE `versausers`
            DROP COLUMN `restore_token`;");

            return ['message' => 'Migración ejecutada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
