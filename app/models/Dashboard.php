<?php

declare(strict_types=1);

namespace app\models;

use RedBeanPHP\Cursor;
use versaWYS\kernel\RedBeanCnn;

class Dashboard extends RedBeanCnn
{
    public function getMenuAdmin(): Cursor|array|int|null
    {
        return $this->getAll(
            "SELECT
                        vm.seccion,
                        vm.id AS id_menu,
                        vm.nombre AS desc_menu,
                        vm.icono AS ico_menu,
                        vm.fill AS fill_menu,
                        vm.url AS url_menu,
                        vm.submenu AS smenu,
                        vsm.id AS id_submenu,
                        vsm.nombre AS desc_submenu,
                        vsm.icono AS ico_submenu,
                        vsm.url AS url_submenu
                    FROM versamenu AS vm
                    LEFT JOIN versasubmenu AS vsm ON vm.id = vsm.id_menu AND vsm.estado = 1
                    WHERE vm.estado = 1
                    ORDER BY vm.seccion, vm.posicion ASC, vsm.posicion asc"
        );
    }

    public function getMenuUser(int $idUser, $idPerfil = 0): Cursor|array|int|null
    {
        return $this->getAll(
            "SELECT * FROM
                (
                    SELECT
                        am.id AS id_menu,
                        am.seccion,
                        am.nombre AS desc_menu,
                        am.icono AS ico_menu,
                        am.fill AS fill_menu,
                        am.url AS url_menu,
                        am.submenu AS smenu,
                        asm.id AS id_submenu,
                        asm.nombre AS desc_submenu,
                        asm.url AS url_submenu,
                        asm.icono as ico_submenu,
                        am.posicion AS menu_posicion,
                        asm.posicion AS submenu_posicion
                    FROM
                        versamenu AS am
                        LEFT JOIN versasubmenu AS asm ON asm.id_menu = am.id
                        LEFT JOIN versaperfildetalle AS ap ON asm.id_menu = ap.menu_id
                        AND asm.id = ap.submenu_id
                        AND ap.perfil_id = ?
                        AND asm.estado = 1
                    WHERE am.submenu = 1 AND am.estado = 1
                    UNION
                    SELECT
                        am.id AS id_menu,
                        am.seccion,
                        am.nombre AS desc_menu,
                        am.icono AS ico_menu,
                        am.fill AS fill_menu,
                        am.url AS url_menu,
                        am.submenu as smenu,
                        0 AS id_submenu,
                        '' AS desc_submenu,
                        '' AS url_submenu,
                        '' AS ico_submenu,
                        am.posicion AS menu_posicion,
                        0 AS submenu_posicion
                    FROM
                        versamenu AS am
                        LEFT JOIN versaperfildetalle AS ap ON am.id = ap.menu_id
                        AND ap.perfil_id = ?
                    WHERE am.submenu = 0 AND am.estado = 1
                ) AS op
                INNER JOIN versaperfildetalleuser as vpu ON vpu.menu_id = op.id_menu AND vpu.submenu_id = op.id_submenu
                WHERE vpu.id_user = ?
                ORDER BY op.seccion, op.menu_posicion, op.submenu_posicion;",
            [$idPerfil, $idPerfil, $idUser]
        );
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
