<?php

declare(strict_types=1);

namespace app\models;

use versaWYS\kernel\model\VersaORMModel;

class Pagination extends VersaORMModel
{
    // @mago-expect lint:excessive-parameter-list (firma usada por los controladores de cada proyecto)
    public function pagination(
        string $table,
        array $fields = [],
        string $where = '',
        string $order = 'id ASC',
        int $limit = 15,
        int $offset = 0,
        array $bindings = [],
    ): array {
        $filter = trim($where) !== '' ? $where : '';

        $qb = $this->qb()->lazy()->select($fields)->selectRaw('COUNT(*) OVER() AS total_count')->from($table);

        if ($filter !== '' && $filter !== '0') {
            $qb->whereRaw($filter, $bindings);
        }

        $qb->orderByRaw($order)->limit($limit)->offset($offset);
        $result = $qb->collect();

        return [
            'total' => (int) ($result[0]['total_count'] ?? 0),
            'data' => $result,
        ];
    }
}
