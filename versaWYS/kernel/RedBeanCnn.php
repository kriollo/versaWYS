<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use PDO;
use RedBeanPHP\R;
use RedBeanPHP\SimpleModel;

class RedBeanCnn extends SimpleModel
{
    protected mixed $host;
    protected mixed $user;
    protected mixed $pass;
    protected mixed $dbName;

    public function connet(): void
    {
        global $config;

        $db_config = $config['DB'];

        $this->host = $db_config['DB_HOST'];
        $this->user = $db_config['DB_USER'];
        $this->pass = $db_config['DB_PASS'];
        $this->dbName = $db_config['DB_NAME'];

        if (R::$currentDB == null) {
            R::setup('mysql:host=' . $this->host . ';dbname=' . $this->dbName, $this->user, $this->pass, false, false, [
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            ]);
            R::freeze();
        }
    }

    public function closeDB(): void
    {
        R::close();
    }
    public function scape($e)
    {
        if (null === $e) {
            return '';
        }

        if (is_numeric($e) && $e <= 2147483647) {
            if (explode('.', $e)[0] != $e) {
                return (float) $e;
            }
            return (int) $e;
        }

        return (string) trim(
            str_replace(
                ['\\', "\x00", '\n', '\r', "'", '"', "\x1a"],
                ['\\\\', '\\0', '\\n', '\\r', "\'", '\"', '\\Z'],
                $e
            )
        );
    }
}
