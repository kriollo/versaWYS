<?php

declare(strict_types=1);

namespace app\models;

use RedBeanPHP\R;
use versaWYS\kernel\RedBeanCnn;

class Perfil extends RedBeanCnn
{
    protected static string $table = 'versaperfil';

    /**
     * Get all perfil.
     *
     * Retrieves all the perfil from the database.
     *
     * @return array An array of user records.
     */
    public function all($estado = 'all'): array
    {
        if ($estado === 'all') {
            return R::findAll(self::$table);
        }
        return R::getAll('SELECT * FROM versaperfil WHERE estado = ?', [$estado]);
    }

    public function save(array $params): int
    {
        $perfil = R::dispense(self::$table);
        $perfil->nombre = $params['nombre'];
        $perfil->estado = $params['estado'];
        return R::store($perfil);
    }

    public function changeState(array $params): int
    {
        $perfil = R::load(self::$table, $params['id']);
        $perfil->estado = $perfil->estado === '1' ? '0' : '1';
        return R::store($perfil);
    }

    public function getPerfil(int $id): array
    {
        return R::getRow('SELECT * FROM versaperfil WHERE id = ?', [$id]);
    }

    public function getPerfilPermisos(int $id): array
    {
        $sql = "SELECT * FROM (SELECT
                    am.id AS id_menu,
                    am.seccion,
                    am.nombre AS menu,
                    am.icono AS icon,
                    am.url AS url_menu,
                    am.submenu AS ifsubmenu,
                    asm.id AS id_submenu,
                    asm.nombre AS submenu,
                    asm.url AS url_submenu,
                    CASE WHEN ap.id IS NULL THEN 0 ELSE 1 END AS checked,
                    am.posicion AS menu_posicion,
                    asm.posicion AS submenu_posicion
                FROM
                    versamenu AS am
                    LEFT JOIN versasubmenu AS asm ON asm.id_menu = am.id
                    LEFT JOIN versaperfildetalle AS ap ON asm.id_menu = ap.menu_id
                    AND asm.id = ap.submenu_id
                    AND ap.perfil_id = ?
                WHERE am.submenu = 1
                UNION
                SELECT
                    am.id AS id_menu,
                    am.seccion,
                    am.nombre AS menu,
                    am.icono AS icon,
                    am.url AS url_menu,
                    am.submenu,
                    0 AS id_submenu,
                    0 AS submenu,
                    '' AS url_submenu,
                    CASE WHEN ap.id IS NULL THEN 0 ELSE 1 END AS checked,
                    am.posicion AS menu_posicion,
                    0 AS submenu_posicion
                FROM
                    versamenu AS am
                    LEFT JOIN versaperfildetalle AS ap ON am.id = ap.menu_id
                    AND ap.perfil_id = ?
                WHERE am.submenu = 0) AS op
                ORDER BY op.seccion, op.menu_posicion, op.submenu_posicion";

        return R::getAll($sql, [$id, $id]);
    }

    public function savePerfilPermisos(array $params)
    {
        R::exec('DELETE FROM versaperfildetalle WHERE perfil_id = ?', [$params['id']]);
        $perfil = R::load(self::$table, $params['id']);
        $perfil->nombre = $params['nombre'];
        $perfil->pagina_inicio = $params['pagina_inicio'];
        $result = R::store($perfil);

        $permisos = $params['data'];
        if ($permisos === '[]' || $permisos === '') {
            return $result;
        }

        foreach ($permisos as $value) {
            //seccion
            foreach ($value as $value2) {
                //menu
                if (array_key_exists('checked', $value2)) {
                    if ($value2['checked'] === true) {
                        $perfilDetalle = R::dispense('versaperfildetalle');
                        $perfilDetalle->perfil_id = $params['id'];
                        $perfilDetalle->menu_id = $value2['id_menu'];
                        $perfilDetalle->submenu_id = 0;
                        R::store($perfilDetalle);
                    }
                }
                if (array_key_exists('submenu', $value2)) {
                    foreach ($value2['submenu'] as $value3) {
                        //submenu
                        if (array_key_exists('checked', $value3)) {
                            if ($value3['checked'] === true) {
                                $perfilDetalle = R::dispense('versaperfildetalle');
                                $perfilDetalle->perfil_id = $params['id'];
                                $perfilDetalle->menu_id = $value2['id_menu'];
                                $perfilDetalle->submenu_id = $value3['id_submenu'];
                                R::store($perfilDetalle);
                            }
                        }
                    }
                }
            }
        }

        //actualizo todos los permisos de todos los usuarios asociados a este perfil
        $userPerfil = R::getAll('SELECT id FROM versausers WHERE id_perfil = ?', [$params['id']]);
        foreach ($userPerfil as $key => $value) {
            R::exec('DELETE FROM versaperfildetalleuser WHERE id_user = ?', [$value['id']]);
            R::exec(
                'INSERT INTO versaperfildetalleuser (id_user, menu_id, submenu_id) SELECT ?, menu_id, submenu_id FROM versaperfildetalle WHERE perfil_id = ?',
                [$value['id'], $params['id']]
            );

            //actualizo la pagina de inicio
            $user = R::load('versausers', $value['id']);
            $user->pagina_inicio = $params['pagina_inicio'];
            R::store($user);
        }
        return $result;
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
