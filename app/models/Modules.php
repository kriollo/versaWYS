<?php

declare(strict_types=1);

namespace app\models;

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
        return $this->getAll('SELECT * FROM versamenu');
    }

    public function getLastPositionBySeccion($seccion): int
    {
        $module = $this->findOne('versamenu', 'seccion = ? ORDER BY posicion DESC LIMIT 1', [$seccion]);
        return $module ? $module->posicion + 1 : 1;
    }

    public function saveModule($data): int|string
    {
        if ($data['action'] === 'edit') {
            $module = $this->load('versamenu', $data['id']);
        } else {
            $module = $this->dispense('versamenu');
            $module->posicion = $this->getLastPositionBySeccion($data['seccion']);
            $module->created_at = date('Y-m-d H:i:s');
        }
        $module->seccion = $data['seccion'];
        $module->nombre = $data['nombre'];
        $module->descripcion = $data['descripcion'];
        $module->icono = $this->scape($data['icono']);
        $module->fill = $data['fill'];
        $module->estado = $data['estado'];
        $module->url = $data['url'];
        $module->updated_at = date('Y-m-d H:i:s');
        return $this->store($module);
    }

    public function changeStatus($params): mixed
    {
        $module = $this->load('versamenu', $params['id']);
        $module->estado = $params['estado'];
        return $this->store($module);
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
    }

    private function toggleSubMenuField($idMenu): void
    {
        $subModules = $this->findAll('versasubmenu', 'id_menu = ?', [$idMenu]);
        $allDisabled = true;
        foreach ($subModules as $subModule) {
            if ($subModule->estado === '1') {
                $allDisabled = false;
                break;
            }
        }

        $mainModule = $this->load('versamenu', $idMenu);
        $mainModule->submenu = $allDisabled ? '0' : '1';
        $this->store($mainModule);
    }

    //submodules
    public function changeStatusSubModule($params): mixed
    {
        $module = $this->load('versasubmenu', $params['id']);
        $module->estado = $params['estado'];
        $result = $this->store($module);

        $this->toggleSubMenuField($module->id_menu);

        return $result;
    }

    public function saveSubModule($data): int|string
    {
        if ($data['action'] === 'edit') {
            $module = $this->load('versasubmenu', (int)$data['id']);
        } else {
            $module = $this->dispense('versasubmenu');
            $module->created_at = date('Y-m-d H:i:s');
        }
        $module->id_menu = $data['id_menu'];
        $module->nombre = $data['nombre'];
        $module->descripcion = $data['descripcion'];
        $module->estado = $data['estado'];
        $module->url = $data['url'];
        $module->updated_at = date('Y-m-d H:i:s');
        $result = $this->store($module);

        $this->toggleSubMenuField($data['id_menu']);

        return $result;
    }

    public function movePositionSubModule($params): mixed
    {
        $subModule = $this->load('versasubmenu', $params['id']);
        $subModule->posicion = $params['position'];
        return $this->store($subModule);
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
