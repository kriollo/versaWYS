<?php

declare(strict_types=1);

namespace app\seeders;

use RedBeanPHP\R;


class ModulesTestsSeeder
{
    public static function run()
    {
        try {
            $AllModule = [
                [
                    'seccion' => 'Seccion 1',
                    'nombre' => 'Opcion 1',
                    'descripcion' => 'Descripcion de la opcion 1',
                    'icono' =>
                        '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.556 8.5h8m-8 3.5H12m7.111-7H4.89a.896.896 0 0 0-.629.256.868.868 0 0 0-.26.619v9.25c0 .232.094.455.26.619A.896.896 0 0 0 4.89 16H9l3 4 3-4h4.111a.896.896 0 0 0 .629-.256.868.868 0 0 0 .26-.619v-9.25a.868.868 0 0 0-.26-.619.896.896 0 0 0-.63-.256Z"/>',
                    'fill' => 0,
                    'url' => 'urltest',
                    'submenu' => 0,
                    'posicion' => 1,
                ],
                [
                    'seccion' => 'Seccion 1',
                    'nombre' => 'Opcion 2',
                    'descripcion' => 'Descripcion de la opcion 2',
                    'icono' =>
                        '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m7.171 12.906-2.153 6.411 2.672-.89 1.568 2.34 1.825-5.183m5.73-2.678 2.154 6.411-2.673-.89-1.568 2.34-1.825-5.183M9.165 4.3c.58.068 1.153-.17 1.515-.628a1.681 1.681 0 0 1 2.64 0 1.68 1.68 0 0 0 1.515.628 1.681 1.681 0 0 1 1.866 1.866c-.068.58.17 1.154.628 1.516a1.681 1.681 0 0 1 0 2.639 1.682 1.682 0 0 0-.628 1.515 1.681 1.681 0 0 1-1.866 1.866 1.681 1.681 0 0 0-1.516.628 1.681 1.681 0 0 1-2.639 0 1.681 1.681 0 0 0-1.515-.628 1.681 1.681 0 0 1-1.867-1.866 1.681 1.681 0 0 0-.627-1.515 1.681 1.681 0 0 1 0-2.64c.458-.361.696-.935.627-1.515A1.681 1.681 0 0 1 9.165 4.3ZM14 9a2 2 0 1 1-4 0 2 2 0 0 1 4 0Z"/>',
                    'fill' => 0,
                    'url' => 'url2',
                    'submenu' => 0,
                    'posicion' => 2,
                ],
                [
                    'seccion' => 'Seccion 2',
                    'nombre' => 'Opcion 3',
                    'descripcion' => 'Descripcion de la opcion 3',
                    'icono' => '<path fill="currentColor" d="M6.94318 11h-.85227l.96023-2.90909h1.07954L9.09091 11h-.85227l-.63637-2.10795h-.02272L6.94318 11Zm-.15909-1.14773h1.60227v.59093H6.78409v-.59093ZM9.37109 11V8.09091h1.25571c.2159 0 .4048.04261.5667.12784.162.08523.2879.20502.3779.35937.0899.15436.1349.33476.1349.5412 0 .20833-.0464.38873-.1392.54119-.0918.15246-.2211.26989-.3878.35229-.1657.0824-.3593.1236-.5809.1236h-.75003v-.61367h.59093c.0928 0 .1719-.0161.2372-.0483.0663-.03314.1169-.08002.152-.14062.036-.06061.054-.13211.054-.21449 0-.08334-.018-.15436-.054-.21307-.0351-.05966-.0857-.10511-.152-.13636-.0653-.0322-.1444-.0483-.2372-.0483h-.2784V11h-.78981Zm3.41481-2.90909V11h-.7898V8.09091h.7898Z"/><path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M8.31818 2c-.55228 0-1 .44772-1 1v.72878c-.06079.0236-.12113.04809-.18098.07346l-.55228-.53789c-.38828-.37817-1.00715-.37817-1.39543 0L3.30923 5.09564c-.19327.18824-.30229.44659-.30229.71638 0 .26979.10902.52813.30229.71637l.52844.51468c-.01982.04526-.03911.0908-.05785.13662H3c-.55228 0-1 .44771-1 1v2.58981c0 .5523.44772 1 1 1h.77982c.01873.0458.03802.0914.05783.1366l-.52847.5147c-.19327.1883-.30228.4466-.30228.7164 0 .2698.10901.5281.30228.7164l1.88026 1.8313c.38828.3781 1.00715.3781 1.39544 0l.55228-.5379c.05987.0253.12021.0498.18102.0734v.7288c0 .5523.44772 1 1 1h2.65912c.5523 0 1-.4477 1-1v-.7288c.1316-.0511.2612-.1064.3883-.1657l.5435.2614v.4339c0 .5523.4477 1 1 1H14v.0625c0 .5523.4477 1 1 1h.0909v.0625c0 .5523.4477 1 1 1h.6844l.4952.4823c1.1648 1.1345 3.0214 1.1345 4.1863 0l.2409-.2347c.1961-.191.3053-.454.3022-.7277-.0031-.2737-.1183-.5342-.3187-.7207l-6.2162-5.7847c.0173-.0398.0342-.0798.0506-.12h.7799c.5522 0 1-.4477 1-1V8.17969c0-.55229-.4478-1-1-1h-.7799c-.0187-.04583-.038-.09139-.0578-.13666l.5284-.51464c.1933-.18824.3023-.44659.3023-.71638 0-.26979-.109-.52813-.3023-.71637l-1.8803-1.8313c-.3883-.37816-1.0071-.37816-1.3954 0l-.5523.53788c-.0598-.02536-.1201-.04985-.1809-.07344V3c0-.55228-.4477-1-1-1H8.31818Z"/>
                    ',
                    'fill' => 0,
                    'url' => 'url3',
                    'submenu' => 1,
                    'posicion' => 3,
                ],
            ];

            $moduleInsert = [];
            foreach ($AllModule as $module) {
                $newModule = R::dispense(typeOrBeanArray: 'versamenu');
                $newModule->seccion = $module['seccion'];
                $newModule->nombre = $module['nombre'];
                $newModule->descripcion = $module['descripcion'];
                $newModule->icono = $module['icono'];
                $newModule->fill = $module['fill'];
                $newModule->url = $module['url'];
                $newModule->submenu = $module['submenu'];
                $newModule->posicion = $module['posicion'];
                $moduleInsert[] = $newModule;
            }
            R::storeAll($moduleInsert);

            $lastId = R::getCell('SELECT MAX(id) FROM versamenu');

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
