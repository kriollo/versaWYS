<?php

declare(strict_types=1);

namespace app\models;

use Exception;
use RedBeanPHP\R;
use versaWYS\kernel\RedBeanCnn;

class Modules extends RedBeanCnn
{
    protected static $table = 'versamenu';

    /**
     * Get all modules.
     *
     * Retrieves all the modules from the database.
     *
     * @return array An array of user records.
     */
    public function all()
    {
        return R::getAll('SELECT * FROM versamenu');
    }

    public function getLastPositionBySeccion($seccion): int
    {
        $module = R::findOne('versamenu', 'seccion = ? ORDER BY posicion DESC LIMIT 1', [$seccion]);
        return $module ? $module->posicion + 1 : 1;
    }

    public function saveModule($data): int|string
    {
        if ($data['action'] === 'edit') {
            $module = R::load('versamenu', $data['id']);
        } else {
            $module = R::dispense('versamenu');
            $module->posicion = $this->getLastPositionBySeccion($data['seccion']);
        }
        $module->seccion = $data['seccion'];
        $module->nombre = $data['nombre'];
        $module->descripcion = $data['descripcion'];
        $module->icono = $this->scape($data['icono']);
        $module->estado = $data['estado'];
        $module->url = $data['url'];
        $module->created_at = date('Y-m-d H:i:s');
        $module->updated_at = date('Y-m-d H:i:s');
        return R::store($module);
    }

    public function changeStatus($params): mixed
    {
        $module = R::load('versamenu', $params['id']);
        $module->estado = $params['estado'];
        return R::store($module);
    }

    public function movePosition($params): void
    {
        if ($params['direction'] === 'up') {
            $newPosition = $params['currentPosition'] - 1;
            if ($newPosition <= 0) {
                $newPosition = 1;
            }
        } else {
            $newPosition = $params['currentPosition'] + 1;
        }

        


        // return R::store($module);
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