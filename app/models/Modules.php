<?php

declare(strict_types=1);

namespace app\models;

use VersaORM\VersaModel;
use versaWYS\kernel\model\VersaORMModel;

class Modules extends VersaORMModel
{
    protected string $table = 'versa_menu';

    /**
     * Get all modules.
     *
     * Retrieves all the modules from the database.
     *
     * @return array an array of user records
     */
    public function all(): array
    {
        return $this->getAll('SELECT * FROM versa_menu');
    }

    public function getLastPositionBySeccion(string $seccion): int
    {
        $module = $this->qb()->where('seccion', '=', $seccion)->orderBy('posicion', 'desc')->findOne();

        return $module instanceof VersaModel ? $module->posicion + 1 : 1;
    }

    public function saveModule(array $data): int|string
    {
        $module = $data['action'] === 'edit' ? $this->load('versa_menu', $data['id']) : $this->dispense('versa_menu');
        $module->posicion = $this->getLastPositionBySeccion($data['seccion']);
        $module->created_at = date('Y-m-d H:i:s');

        $module->seccion = $data['seccion'];
        $module->nombre = $data['nombre'];
        $module->descripcion = $data['descripcion'];
        $module->icono = $data['icono'];
        $module->fill = $data['fill'];
        $module->estado = $data['estado'];
        $module->url = $data['url'];
        $module->updated_at = date('Y-m-d H:i:s');
        $module->store();

        return $module->id;
    }

    public function changeStatus(array $params): mixed
    {
        $module = $this->load('versa_menu', $params['id']);
        $module->estado = !$module->estado;
        $module->store();

        return $module->id;
    }

    public function movePosition(array $params): array
    {
        $id = (int) $params['id'];
        $direction = $params['direction'];

        $modules = $this->qb()->orderBy('posicion', 'asc')->get();

        if ($modules === []) {
            return [];
        }

        // Orden secundario por id en PHP
        usort($modules, static fn(array $a, array $b): int => $a['posicion'] !== $b['posicion']
            ? $a['posicion'] <=> $b['posicion']
            : $a['id'] <=> $b['id']);

        // Normalizar si hay duplicados o ceros
        $uniquePositions = array_unique(array_column($modules, 'posicion'));
        if (count($uniquePositions) < count($modules)) {
            foreach ($modules as $idx => &$module) {
                $mod = $this->load('versa_menu', (int) $module['id']);
                $mod->posicion = $idx + 1;
                $mod->store();
                $module['posicion'] = $idx + 1;
            }

            unset($module);
        }

        // Buscar el índice del ítem actual
        $currentIndex = -1;
        foreach ($modules as $idx => $module) {
            if ((int) $module['id'] !== $id) {
                continue;
            }

            $currentIndex = $idx;
            break;
        }

        if ($currentIndex === -1) {
            return array_map(static fn(array $m): array => ['id' => $m['id'], 'posicion' => $m['posicion']], $modules);
        }

        $swapIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($swapIndex < 0 || $swapIndex >= count($modules)) {
            return array_map(static fn(array $m): array => ['id' => $m['id'], 'posicion' => $m['posicion']], $modules);
        }

        // Intercambiar posiciones
        $currentMod = $this->load('versa_menu', (int) $modules[$currentIndex]['id']);
        $swapMod = $this->load('versa_menu', (int) $modules[$swapIndex]['id']);

        $tempPos = (int) $currentMod->posicion;
        $currentMod->posicion = (int) $swapMod->posicion;
        $swapMod->posicion = $tempPos;

        $currentMod->store();
        $swapMod->store();

        $modules[$currentIndex]['posicion'] = $currentMod->posicion;
        $modules[$swapIndex]['posicion'] = $swapMod->posicion;

        return array_map(static fn(array $m): array => ['id' => $m['id'], 'posicion' => $m['posicion']], $modules);
    }

    // submodules
    public function changeStatusSubModule(array $params): mixed
    {
        $submodule = $this->load('versa_submenu', $params['id']);
        $submodule->estado = !$submodule->estado;
        $submodule->store();

        $this->toggleSubMenuField($submodule->id_menu);

        return $submodule->id;
    }

    public function saveSubModule(array $data): int|string
    {
        $module = $data['action'] === 'edit'
            ? $this->load('versa_submenu', (int) $data['id'])
            : $this->dispense('versa_submenu');
        $module->created_at = date('Y-m-d H:i:s');

        $module->id_menu = $data['id_menu'];
        $module->nombre = $data['nombre'];
        $module->descripcion = $data['descripcion'];
        $module->estado = $data['estado'];
        $module->url = $data['url'];
        $module->updated_at = date('Y-m-d H:i:s');

        $module->store();

        $this->toggleSubMenuField((int) $data['id_menu']);

        return $module->id;
    }

    public function movePositionSubModule(array $params): array
    {
        $idMenu = (int) $params['id_menu'];
        $id = (int) $params['id'];
        $direction = $params['direction'];

        $subModules = $this->qb('versa_submenu')->where('id_menu', '=', $idMenu)->orderBy('posicion', 'asc')->get();

        if ($subModules === []) {
            return [];
        }

        // Orden secundario por id en PHP
        usort($subModules, static fn(array $a, array $b): int => $a['posicion'] !== $b['posicion']
            ? $a['posicion'] <=> $b['posicion']
            : $a['id'] <=> $b['id']);

        // Normalizar si hay duplicados o ceros
        $uniquePositions = array_unique(array_column($subModules, 'posicion'));
        if (count($uniquePositions) < count($subModules)) {
            foreach ($subModules as $idx => &$subModule) {
                $mod = $this->load('versa_submenu', (int) $subModule['id']);
                $mod->posicion = $idx + 1;
                $mod->store();
                $subModule['posicion'] = $idx + 1;
            }

            unset($subModule);
        }

        // Buscar el índice del ítem actual
        $currentIndex = -1;
        foreach ($subModules as $idx => $subModule) {
            if ((int) $subModule['id'] !== $id) {
                continue;
            }

            $currentIndex = $idx;
            break;
        }

        if ($currentIndex === -1) {
            return array_map(static fn(array $m): array => [
                'id' => $m['id'],
                'posicion' => $m['posicion'],
            ], $subModules);
        }

        $swapIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($swapIndex < 0 || $swapIndex >= count($subModules)) {
            return array_map(static fn(array $m): array => [
                'id' => $m['id'],
                'posicion' => $m['posicion'],
            ], $subModules);
        }

        // Intercambiar posiciones
        $currentMod = $this->load('versa_submenu', (int) $subModules[$currentIndex]['id']);
        $swapMod = $this->load('versa_submenu', (int) $subModules[$swapIndex]['id']);

        $tempPos = (int) $currentMod->posicion;
        $currentMod->posicion = (int) $swapMod->posicion;
        $swapMod->posicion = $tempPos;

        $currentMod->store();
        $swapMod->store();

        $subModules[$currentIndex]['posicion'] = $currentMod->posicion;
        $subModules[$swapIndex]['posicion'] = $swapMod->posicion;

        return array_map(static fn(array $m): array => ['id' => $m['id'], 'posicion' => $m['posicion']], $subModules);
    }

    private function toggleSubMenuField(int $idMenu): void
    {
        $subModules = $this->qb('versa_submenu')->where('id_menu', '=', $idMenu)->get();

        $allDisabled = true;
        foreach ($subModules as $subModule) {
            if ($subModule['estado']) {
                continue;
            }

            $allDisabled = false;
            break;
        }

        $mainModule = $this->load('versa_menu', $idMenu);
        $mainModule->submenu = $allDisabled;
        $mainModule->store();
    }
}
