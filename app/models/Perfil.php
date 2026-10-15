<?php

declare(strict_types=1);

namespace app\models;

use VersaORM\VersaModel;
use versaWYS\kernel\model\VersaORMModel;

class Perfil extends VersaORMModel
{
    protected string $table = 'versa_perfil'; // Example table name

    protected array $fillable = [];

    protected array $guarded = [];

    protected array $rules = [];

    /**
     * Get all perfil.
     *
     * Retrieves all the perfil from the database.
     *
     * @return array an array of user records
     */
    public function all(int $id_empresa, string|int $estado = 'all'): array
    {
        if ($estado === 'all') {
            return $this->qb()->where('id_empresa', '=', $id_empresa)->getAll();
        }

        return $this->qb()->where('estado', '=', $estado)->where('id_empresa', '=', $id_empresa)->getAll();
    }

    public function savePerfil(array $params): int
    {
        $perfil = $this->dispense($this->table);
        $perfil->id_empresa = $params['id_empresa'];
        $perfil->nombre = $params['nombre'];
        $perfil->estado = $params['estado'];
        $this->executeWithErrorHandling($perfil->store(...), [
            'operation' => 'savePerfil',
            'params' => $params,
        ]);

        return $perfil->id;
    }

    public function changeState(array $params): int
    {
        $perfil = $this->load($this->table, $params['id']);
        // Un perfil solo puede modificarse desde su propia empresa
        if (!$perfil instanceof VersaModel || (int) $perfil->id_empresa !== (int) $params['id_empresa']) {
            return 0;
        }
        $perfil->estado = $perfil->estado ? 0 : 1;
        $perfil->store();

        return $perfil->id;
    }

    public function getPerfil(int $id): array
    {
        return $this->getRow('SELECT * FROM versa_perfil WHERE id = ?', [$id]);
    }

    public function getPerfilPermisos(int $perfilId, int $empresaId): array
    {
        $sql = "SELECT *
                    FROM
                        (
                            SELECT
                                am.id AS id_menu,
                                am.seccion,
                                am.nombre AS menu,
                                am.icono AS icon,
                                am.fill AS fill_menu,
                                am.url AS url_menu,
                                am.submenu AS ifsubmenu,
                                asm.id AS id_submenu,
                                asm.nombre AS submenu,
                                asm.url AS url_submenu,
                                CASE WHEN ap.id IS NULL THEN false ELSE true END AS checked,
                                am.posicion AS menu_posicion,
                                asm.posicion AS submenu_posicion
                            FROM
                                versa_menu AS am
                                INNER JOIN empresa_modulo as em ON em.id_modulo = am.id AND em.id_empresa = ?
                                LEFT JOIN versa_submenu AS asm ON asm.id_menu = am.id
                                LEFT JOIN versa_perfildetalle AS ap ON asm.id_menu = ap.menu_id
                                AND asm.id = ap.submenu_id
                                AND ap.perfil_id = ?
                            WHERE
                                am.submenu = true
                            UNION ALL
                            SELECT
                                am.id AS id_menu,
                                am.seccion,
                                am.nombre AS menu,
                                am.icono AS icon,
                                am.fill AS fill_menu,
                                am.url AS url_menu,
                                am.submenu AS ifsubmenu,
                                0 AS id_submenu,
                                '' AS submenu,
                                '' AS url_submenu,
                                CASE WHEN ap.id IS NULL THEN false ELSE true END AS checked,
                                am.posicion AS menu_posicion,
                                0 AS submenu_posicion
                            FROM
                                versa_menu AS am
                                INNER JOIN empresa_modulo as em ON em.id_modulo = am.id AND em.id_empresa = ?
                                LEFT JOIN versa_perfildetalle AS ap ON am.id = ap.menu_id
                                AND ap.perfil_id = ?
                            WHERE
                                am.submenu = false
                        ) AS op
                    ORDER BY
                        op.seccion,
                        op.menu_posicion,
                        op.submenu_posicion";

        return $this->executeWithErrorHandling(
            fn(): array => $this->getAll($sql, [$empresaId, $perfilId, $empresaId, $perfilId]),
            ['operation' => 'savePerfilPermisos', 'table' => $this->table, 'query' => $sql],
        );
    }

    public function savePerfilPermisos(array $params): int
    {
        $perfil = $this->load($this->table, $params['id']);
        // Un perfil solo puede modificarse desde su propia empresa
        if (!$perfil instanceof VersaModel || (int) $perfil->id_empresa !== (int) $params['id_empresa']) {
            return 0;
        }
        $perfil->nombre = $params['nombre'];
        $perfil->pagina_inicio = $params['pagina_inicio'];
        $this->executeWithErrorHandling($perfil->store(...), [
            'operation' => 'savePerfilPermisos',
            'params' => $params,
        ]);

        $permisos = json_decode($params['data'], true);
        if ($permisos === '[]' || $permisos === '') {
            return $perfil->id;
        }

        $this->qb('versa_perfildetalle')->where('perfil_id', '=', $params['id'])->delete();
        foreach ($permisos as $value) {
            // seccion
            foreach ($value as $value2) {
                // menu
                if (array_key_exists('checked', $value2) && $value2['checked']) {
                    $perfilDetalle = $this->dispense('versa_perfildetalle');
                    $perfilDetalle->perfil_id = (int) $params['id'];
                    $perfilDetalle->menu_id = (int) $value2['id_menu'];
                    // Sin submenú: NULL, porque submenu_id es una llave foránea a versa_submenu
                    $perfilDetalle->submenu_id = null;
                    $this->executeWithErrorHandling($perfilDetalle->store(...), [
                        'operation' => 'versa_perfildetalle',
                        'table' => 'versa_perfildetalle',
                    ]);
                }

                if (array_key_exists('submenu', $value2)) {
                    foreach ($value2['submenu'] as $value3) {
                        // submenu
                        if (!(array_key_exists('checked', $value3) && $value3['checked'])) {
                            continue;
                        }

                        $perfilDetalle = $this->dispense('versa_perfildetalle');
                        $perfilDetalle->perfil_id = $params['id'];
                        $perfilDetalle->menu_id = $value2['id_menu'];
                        $perfilDetalle->submenu_id = $value3['id_submenu'];
                        $this->executeWithErrorHandling($perfilDetalle->store(...), [
                            'operation' => 'versa_perfildetalle',
                            'table' => 'versa_perfildetalle',
                        ]);
                    }
                }
            }
        }

        // actualizo todos los permisos de todos los usuarios asociados a este perfil
        $userPerfil = $this->getAll('SELECT id FROM versa_users WHERE id_perfil = ?', [
            $params['id'],
        ]);
        foreach ($userPerfil as $key => $value) {
            $this->exec('DELETE FROM versa_perfildetalleuser WHERE id_user = ?', [$value['id']]);
            $this->exec(
                'INSERT INTO versa_perfildetalleuser (id_user, menu_id, submenu_id) SELECT ?, menu_id, submenu_id FROM versa_perfildetalle WHERE perfil_id = ?',
                [
                    $value['id'],
                    $params['id'],
                ],
            );

            // actualizo la pagina de inicio
            $id = (int) $value['id'];
            $user = $this->load('versa_users', $id);
            $user->pagina_inicio = $params['pagina_inicio'];
            $this->executeWithErrorHandling($user->store(...), [
                'operation' => 'savePerfilPermisos',
            ]);
        }

        return $perfil->id;
    }
}
