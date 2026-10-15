<?php

declare(strict_types=1);

namespace app\models;

use versaWYS\kernel\model\VersaORMModel;

class Dashboard extends VersaORMModel
{
    /**
     * Versión usando QueryBuilder de VersaORM de la consulta getMenuAdmin.
     * Nota sobre semántica: el SQL original coloca 'vsm.estado = 1' dentro del ON del LEFT JOIN
     * para que aún se devuelvan filas del menú aunque no existan submenús activos.
     * El QueryBuilder actual no expone una API para añadir condiciones adicionales al ON
     * más allá de la igualdad básica, por lo que simulamos la condición usando un WHERE
     * con (vsm.estado = 1 OR vsm.id IS NULL) para preservar las filas sin submenú activo.
     */
    public function getMenuAdmin(): array
    {
        // Selecciones con alias tal como en el SQL original
        $qb = $this
            ->qb('versa_menu')
            ->lazy()
            ->select([
                'versa_menu.seccion',
                'versa_menu.id AS id_menu',
                'versa_menu.nombre AS desc_menu',
                'versa_menu.icono AS ico_menu',
                'versa_menu.fill AS fill_menu',
                'versa_menu.url AS url_menu',
                'versa_menu.submenu AS smenu',
                'versa_submenu.id AS id_submenu',
                'versa_submenu.nombre AS desc_submenu',
                'versa_submenu.icono AS ico_submenu',
                'versa_submenu.url AS url_submenu',
            ])
            ->leftJoin('versa_submenu')
            ->on('versa_menu.id', '=', 'versa_submenu.id_menu')
            ->where('versa_menu.estado', '=', 1)
            // Simular condición del ON original (vsm.estado = 1) sin perder filas sin submenú
            ->whereRaw('(versa_submenu.estado = ? OR versa_submenu.id IS NULL)', [true])
            ->orderByRaw(
                'UPPER(TRIM(versa_menu.seccion)) ASC, versa_menu.posicion ASC, versa_submenu.posicion ASC',
            );

        return $qb->collect();
    }

    public function getMenuUser(int $idUser, int $idPerfil = 0): array
    {
        return $this->getAll("SELECT
                    am.id AS id_menu,
                    am.seccion,
                    am.nombre AS desc_menu,
                    am.icono AS ico_menu,
                    am.fill AS fill_menu,
                    am.url AS url_menu,
                    am.submenu AS smenu,
                    CASE WHEN am.submenu = true THEN asm.id ELSE 0 END AS id_submenu,
                    CASE WHEN am.submenu = true THEN asm.nombre ELSE '' END AS desc_submenu,
                    CASE WHEN am.submenu = true THEN asm.url ELSE '' END AS url_submenu,
                    CASE WHEN am.submenu = true THEN asm.icono ELSE '' END AS ico_submenu,
                    am.posicion AS menu_posicion,
                    CASE WHEN am.submenu = true THEN asm.posicion ELSE 0 END AS submenu_posicion
                FROM versa_menu AS am
                LEFT JOIN versa_submenu AS asm
                    ON asm.id_menu = am.id AND asm.estado = true
                LEFT JOIN versa_perfildetalle AS ap
                    ON (am.submenu = true AND asm.id_menu = ap.menu_id AND asm.id = ap.submenu_id
                        OR am.submenu = false AND am.id = ap.menu_id)
                AND ap.perfil_id = ?
                INNER JOIN versa_perfildetalleuser AS vpu
                    ON vpu.menu_id = am.id
                AND COALESCE(vpu.submenu_id, 0) = CASE WHEN am.submenu = true THEN asm.id ELSE 0 END
                AND vpu.id_user = ?
                WHERE am.estado = true
                ORDER BY
                    UPPER(TRIM(am.seccion)),
                    am.posicion,
                    submenu_posicion;", [$idPerfil, $idUser]);
    }
}
