<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use RedBeanPHP\R;

class RedBeanCnn
{

    protected $db;
    protected $host;
    protected $user;
    protected $pass;
    protected $db_name;

    public function __construct()
    {
        global $config;

        $db_config = $config['DB'];

        $this->host =    $db_config['DB_HOST'];
        $this->user =    $db_config['DB_USER'];
        $this->pass =    $db_config['DB_PASS'];
        $this->db_name = $db_config['DB_NAME'];

        $this->setup();
    }


    public function setup()
    {
        if (R::$currentDB == null) {
            R::setup(
                "mysql:host={$this->host};
                dbname={$this->db_name}",
                $this->user,
                $this->pass
            );
            R::freeze(true);
        }
    }

    public function closedb()
    {
        $this->db->close();
    }
}

