<?php

declare(strict_types=1);

namespace app\seeders;

use VersaORM\VersaORMException;
use versaWYS\kernel\model\VersaORMModel;

/**
 * Agrega las entradas de menú y submenú del módulo de medios.
 * Incluye: Media Store (vista principal) y Configuraciones.
 */
class S202603_MediaModuleSeeder extends VersaORMModel
{
    public function run(): array
    {
        try {
            $AllModule = [
                [
                    'seccion' => 'ARCHIVOS',
                    'nombre' => 'Media Store',
                    'descripcion' => 'Módulo de administración de archivos multimedia',
                    'icono' => '<svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 8H4m0-2v13a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1h-5.032a1 1 0 0 1-.768-.36l-1.9-2.28a1 1 0 0 0-.768-.36H5a1 1 0 0 0-1 1Z"/></svg>',
                    'fill' => 0,
                    'url' => '#',
                    'submenu' => 1,
                    'posicion' => 5,
                ],
            ];

            foreach ($AllModule as $module) {
                $newModule = $this->dispense('versa_menu');
                $newModule->seccion = $module['seccion'];
                $newModule->nombre = $module['nombre'];
                $newModule->descripcion = $module['descripcion'];
                $newModule->icono = $module['icono'];
                $newModule->fill = $module['fill'];
                $newModule->url = $module['url'];
                $newModule->submenu = $module['submenu'];
                $newModule->posicion = $module['posicion'];
                $newModule->store();
            }

            $lastId = $this->getCell('SELECT MAX(id) FROM versa_menu');

            $subMenu = [
                [
                    'id_menu' => $lastId,
                    'nombre' => 'Almacenamiento de Medios',
                    'descripcion' => 'Gestionar archivos multimedia',
                    'url' => '/mediaStore',
                ],
            ];

            foreach ($subMenu as $sub) {
                $newSubMenu = $this->dispense('versa_submenu');
                $newSubMenu->nombre = $sub['nombre'];
                $newSubMenu->descripcion = $sub['descripcion'];
                $newSubMenu->url = $sub['url'];
                $newSubMenu->id_menu = $sub['id_menu'];
                $newSubMenu->store();
            }

            return ['message' => 'Seeder de medios ejecutado con éxito.', 'success' => true];
        } catch (VersaORMException $versaormException) {
            return ['message' => $versaormException->getMessage(), 'success' => false];
        }
    }
}
