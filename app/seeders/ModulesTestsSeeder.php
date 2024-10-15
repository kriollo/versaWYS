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
                    'icono' => 'icono1',
                    'url' => 'url1',
                    'submenu' => 0,
                ],
                [
                    'seccion' => 'Seccion 1',
                    'nombre' => 'Opcion 2',
                    'descripcion' => 'Descripcion de la opcion 2',
                    'icono' => 'icono2',
                    'url' => 'url2',
                    'submenu' => 0,
                ],
                [
                    'seccion' => 'Seccion 2',
                    'nombre' => 'Opcion 3',
                    'descripcion' => 'Descripcion de la opcion 3',
                    'icono' => 'icono3',
                    'url' => 'url3',
                    'submenu' => 0,
                ],
            ];

            foreach ($modules as $module) {
                $newModule = R::dispense('versamenu');
                $newModule->seccion = $module['seccion'];
                $newModule->nombre = $module['nombre'];
                $newModule->descripcion = $module['descripcion'];
                $newModule->icono = $module['icono'];
                $newModule->url = $module['url'];
                $newModule->submenu = $module['submenu'];
                R::store($newModule);
            }

            return ['message' => 'Seeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => $e->getMessage(), 'success' => false];
        }
    }
}
