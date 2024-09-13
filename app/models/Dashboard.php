<?php

declare(strict_types=1);

namespace app\models;

use RedBeanPHP\Cursor;
use RedBeanPHP\R;
use versaWYS\kernel\RedBeanCnn;

class Dashboard extends RedBeanCnn
{
    public function getMenuAdmin(): Cursor|array|int|null
    {
        return R::getAll("SELECT vm.seccion,
        vm.id AS id_menu, vm.nombre AS menu, vm.descripcion AS desc_menu, vm.icono AS ico_menu, vm.url AS url_menu,vm.submenu AS smenu,
        vsm.id AS id_submenu, vsm.nombre AS submenu, vsm.descripcion AS desc_submenu, vsm.icono AS ico_submenu, vsm.url AS url_submenu
        FROM versamenu AS vm LEFT JOIN versasubmenu AS vsm ON vm.id = vsm.id_menu AND vsm.estado = 1
        WHERE vm.estado = 1
        ORDER BY vm.seccion, vm.posicion ASC, vsm.posicion asc");
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
