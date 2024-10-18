<?php

declare(strict_types=1);

namespace app\seeders;

use RedBeanPHP\R;

class ModulesTestsSeeder
{
    public static function run()
    {
        try {
            $modules = [
                [
                    'seccion' => 'Seccion 1',
                    'nombre' => 'Opcion 1',
                    'descripcion' => 'Descripcion de la opcion 1',
                    'icono' =>
                        '<path fill-rule="evenodd" d="M3 4a1 1 0 0 0-.822 1.57L6.632 12l-4.454 6.43A1 1 0 0 0 3 20h13.153a1 1 0 0 0 .822-.43l4.847-7a1 1 0 0 0 0-1.14l-4.847-7a1 1 0 0 0-.822-.43H3Z" clip-rule="evenodd"/>',
                    'fill' => 1,
                    'url' => 'url1',
                    'submenu' => 0,
                ],
                [
                    'seccion' => 'Seccion 1',
                    'nombre' => 'Opcion 2',
                    'descripcion' => 'Descripcion de la opcion 2',
                    'icono' =>
                        '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.153 19 21 12l-4.847-7H3l4.848 7L3 19h13.153Z"/>',
                    'fill' => 0,
                    'url' => 'url2',
                    'submenu' => 0,
                ],
                [
                    'seccion' => 'Seccion 2',
                    'nombre' => 'Opcion 3',
                    'descripcion' => 'Descripcion de la opcion 3',
                    'icono' =>
                        '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 14v4.833A1.166 1.166 0 0 1 16.833 20H5.167A1.167 1.167 0 0 1 4 18.833V7.167A1.166 1.166 0 0 1 5.167 6h4.618m4.447-2H20v5.768m-7.889 2.121 7.778-7.778"/>',
                    'fill' => 0,
                    'url' => 'url3',
                    'submenu' => 1,
                ],
            ];

            foreach ($modules as $module) {
                $newModule = R::dispense('versamenu');
                $newModule->seccion = $module['seccion'];
                $newModule->nombre = $module['nombre'];
                $newModule->descripcion = $module['descripcion'];
                $newModule->icono = $module['icono'];
                $newModule->fill = $module['fill'];
                $newModule->url = $module['url'];
                $newModule->submenu = $module['submenu'];
                $lastId = R::store($newModule);
            }

            $subMenu = [
                [
                    'id_menu' => $lastId,
                    'nombre' => 'Submenu 1',
                    'descripcion' => 'Descripcion del submenu 1',
                    'url' => 'url4',
                ],
                [
                    'id_menu' => $lastId,
                    'nombre' => 'Submenu 2',
                    'descripcion' => 'Descripcion del submenu 2',
                    'url' => 'url5',
                ],
                [
                    'id_menu' => $lastId,
                    'nombre' => 'Submenu 3',
                    'descripcion' => 'Descripcion del submenu 3',
                    'url' => 'url6',
                ],
            ];

            foreach ($subMenu as $sub) {
                $newSubMenu = R::dispense('versasubmenu');
                $newSubMenu->nombre = $sub['nombre'];
                $newSubMenu->descripcion = $sub['descripcion'];
                $newSubMenu->url = $sub['url'];
                $newSubMenu->id_menu = $sub['id_menu'];
                R::store($newSubMenu);
            }

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
