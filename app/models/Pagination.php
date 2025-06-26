<?php

declare(strict_types=1);

namespace app\models;

use versaWYS\kernel\RedBeanCnn;

class Pagination extends RedBeanCnn
{
    public function pagination(
        string $table,
        array $fields = [],
        string $where = '',
        string $order = 'ORDER BY id ASC',
        string $limit = 'LIMIT 0, 15'
    ): array {
        $filter = trim($where) != '' ? "WHERE $where" : '';
        $fields = $fields ? implode(',', $fields) : '*';

        $result = $this->getAll("SELECT SQL_CALC_FOUND_ROWS $fields FROM $table $filter $order $limit");
        $total = $this->getCell('SELECT FOUND_ROWS()');
        return ['total' => $total, 'data' => $result];
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
