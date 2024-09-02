<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use PDO;
use RedBeanPHP\R;

class RedBeanCnn
{
    protected mixed $host;
    protected mixed $user;
    protected mixed $pass;
    protected mixed $dbName;

    public function __construct()
    {
        global $config;

        $db_config = $config['DB'];

        $this->host = $db_config['DB_HOST'];
        $this->user = $db_config['DB_USER'];
        $this->pass = $db_config['DB_PASS'];
        $this->dbName = $db_config['DB_NAME'];

        $this->setup();
    }

    public function setup(): void
    {
        if (R::$currentDB == null) {
            R::setup('mysql:host=' . $this->host . ';dbname=' . $this->dbName, $this->user, $this->pass, false, false, [
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            ]);
            R::freeze();
        }
    }
}
