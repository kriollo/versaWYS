<?php

declare(strict_types=1);

namespace app\models;

use versaWYS\kernel\RedBeanCnn;

class UrlTest extends RedBeanCnn
{
    protected static $table = 'urltest';

    /**
     * Get all urltest.
     *
     * Retrieves all the urltest from the database.
     *
     * @return array An array of user records.
     */
    public function all()
    {
        return $this->getAll('SELECT * FROM self::$table');
    }

    public function __construct()
    {
        $this->connet();
    }

    public function __destruct()
    {
        $this->closeDB();
    }
}
