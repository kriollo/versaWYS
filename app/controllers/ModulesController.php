<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use versaWYS\kernel\GlobalControllers;

class ModulesController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }
    /**
     * Renders the index page for the ModulesController.
     *
     * @return string The rendered template for the index page.
     */
    public function index()
    {
        return $this->template->render('dashboard/modules/dashModules.twig');
    }

    /**
     * Retrieves paginated modules.
     *
     * @return void
     */
    public function getModulesPaginated(): array
    {
        global $request;

        $params = $this->getParamsPaginate($request, ['seccion', 'nombre', 'descripcion']);
        $filter = $params['filter'] ? $params['filter'] : '';
        $order = $params['order'] ? "ORDER BY seccion asc, $params[order]" : 'ORDER BY seccion asc , posicion DESC';
        $result = (new Models\Pagination())->pagination(
            'versamenu',
            ['id', 'seccion', 'nombre', 'descripcion', 'icono', 'fill', 'estado', 'url', 'posicion', 'submenu'],
            $filter,
            $order,
            $params['limit']
        );

        $count = (int) $result['total'];

        $total_pages = ceil($count / $params['per_page']);
        if ($total_pages === 1 || $total_pages === 0) {
            $total_pages = 1;
            if ($params['page'] >= 1) {
                $params['page'] = 0;
            }
        }

        $columns = [
            [
                'field' => 'id',
                'title' => 'Posición',
                'type' => 'position',
                'buttons' => [
                    [
                        'type' => 'up',
                        'title' => 'Arriba',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'icon' => 'bi bi-arrow-up',
                        'action' => 'changePosition',
                    ],
                    [
                        'type' => 'down',
                        'title' => 'Abajo',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'icon' => 'bi bi-arrow-down',
                        'action' => 'changePosition',
                    ],
                ],
            ],
            ['field' => 'seccion', 'title' => 'Sección'],
            ['field' => 'nombre', 'title' => 'Nombre Menú'],
            ['field' => 'descripcion', 'title' => 'Descripción'],
            ['field' => 'url', 'title' => 'URL'],
            ['field' => 'icono', 'title' => 'Icono SVG', 'type' => 'svg'],
            ['field' => 'estado', 'title' => 'Estado', 'type' => 'status'],
            [
                'field' => 'actions',
                'title' => 'Acciones',
                'type' => 'actions',
                'buttons' => [
                    [
                        'title' => 'Editar',
                        'icon' => 'bi bi-pencil-fill text-xl text-yellow-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'showEditModule',
                        'condition' => 'estado',
                        'condition_value' => '1',
                    ],
                    [
                        'title' => 'Ver Submenús',
                        'icon' => 'bi bi-menu-app-fill text-xl text-blue-500  dark:text-blue-300',
                        'class' => [
                            'condition_field' => 'submenu',
                            'condition_value' => '1',
                            'active' => 'bg-gray-500 hover:bg-gray-600 dark:bg-gray-500 dark:hover:bg-gray-600',
                            'inactive' => 'text-gray-400 dark:text-gray-400',
                        ],
                        'action' => 'viewSubmenus',
                        'condition' => 'estado',
                        'condition_value' => '1',
                    ],
                    [
                        'title' => 'Desactivar Menú',
                        'icon' => 'bi bi-trash-fill text-xl text-red-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'changeStatus',
                        'condition' => 'estado',
                        'condition_value' => '1',
                    ],
                    [
                        'title' => 'Activar Menú',
                        'icon' => 'bi bi-recycle text-xl text-green-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'changeStatus',
                        'condition' => 'estado',
                        'condition_value' => '0',
                    ],
                ],
            ],
        ];

        return [
            'success' => 1,
            'data' => $result['data'],
            'columns' => $columns,
            'meta' => [
                'filter' => $params['filtro'],
                'total' => $count,
                'from' => $params['page'] + 1,
                'to' => $params['page'] + count($result['data']),
                'page' => $params['page'],
                'total_pages' => (int) $total_pages,
            ],
        ];
    }

    /**
     * Saves a module.
     *
     * @return void
     */
    public function saveModule(): array
    {
        global $request;

        $params = $request->getAllParams();
        $params['estado'] = $params['estado'] === 'true' ? 1 : 0;
        $result = (new Models\Modules())->saveModule($params);

        if ($result) {
            return ['success' => 1, 'message' => 'Menú guardado correctamente'];
        }
        return ['success' => 0, 'message' => 'Error al crear el menú'];
    }

    /**
     * Change the status of a module.
     *
     * This method is responsible for changing the status of a module based on the provided parameters.
     *
     * @return void
     */
    public function changeStatus(): array
    {
        global $request;

        $params = $request->getAllParams();
        $result = (new Models\Modules())->changeStatus($params);

        if ($result) {
            return ['success' => 1, 'message' => 'Menú actualizado correctamente'];
        }
        return ['success' => 0, 'message' => 'Error al actualizar el menú'];
    }

    public function movePosition(): array
    {
        global $request;

        //todo: finalizar este método
        $params = $request->getAllParams();
        (new Models\Modules())->movePosition($params);

        return ['success' => 1];
    }

    //submodules
    public function getSubModules(): array
    {
        global $request;

        $params = $this->getParamsPaginate($request, ['id'], true);

        $filter = $params['filter'] ? $params['filter'] : '';
        $order = $params['order'] ? "ORDER BY $params[order]" : 'ORDER BY id asc , posicion DESC';
        $result = (new Models\Pagination())->pagination(
            'versasubmenu',
            ['id_menu', 'id', 'nombre', 'descripcion', 'estado', 'url', 'posicion'],
            $filter,
            $order,
            $params['limit']
        );

        $count = (int) $result['total'];

        $total_pages = ceil($count / $params['per_page']);
        if ($total_pages === 1 || $total_pages === 0) {
            $total_pages = 1;
            if ($params['page'] >= 1) {
                $params['page'] = 0;
            }
        }

        $columns = [
            [
                'field' => 'id',
                'title' => 'Posición',
                'type' => 'position',
                'buttons' => [
                    [
                        'type' => 'up',
                        'title' => 'Arriba',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'icon' => 'bi bi-arrow-up',
                        'action' => 'changePosition',
                    ],
                    [
                        'type' => 'down',
                        'title' => 'Abajo',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'icon' => 'bi bi-arrow-down',
                        'action' => 'changePosition',
                    ],
                ],
            ],
            ['field' => 'nombre', 'title' => 'Nombre Menú'],
            ['field' => 'descripcion', 'title' => 'Descripción'],
            ['field' => 'url', 'title' => 'URL'],
            ['field' => 'estado', 'title' => 'Estado', 'type' => 'status'],
            [
                'field' => 'actions',
                'title' => 'Acciones',
                'type' => 'actions',
                'buttons' => [
                    [
                        'title' => 'Editar',
                        'icon' => 'bi bi-pencil-fill text-xl text-yellow-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'showEditSubModule',
                        'condition' => 'estado',
                        'condition_value' => '1',
                    ],
                    [
                        'title' => 'Desactivar Menú',
                        'icon' => 'bi bi-trash-fill text-xl text-red-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'changeStatusSubMenu',
                        'condition' => 'estado',
                        'condition_value' => '1',
                    ],
                    [
                        'title' => 'Activar Menú',
                        'icon' => 'bi bi-recycle text-xl text-green-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'changeStatusSubMenu',
                        'condition' => 'estado',
                        'condition_value' => '0',
                    ],
                ],
            ],
        ];

        return [
            'success' => 1,
            'data' => $result['data'],
            'columns' => $columns,
            'meta' => [
                'filter' => $params['filtro'],
                'total' => $count,
                'from' => $params['page'] + 1,
                'to' => $params['page'] + count($result['data']),
                'page' => $params['page'],
                'total_pages' => (int) $total_pages,
            ],
        ];
    }

    public function saveSubModule(): array
    {
        global $request;

        $params = $request->getAllParams();
        $params['estado'] = $params['estado'] === 'true' ? 1 : 0;
        $result = (new Models\Modules())->saveSubModule($params);

        if ($result) {
            return ['success' => 1, 'message' => 'Menú guardado correctamente'];
        }
        return ['success' => 0, 'message' => 'Error al crear el menú'];
    }

    public function changeStatusSubModule(): array
    {
        global $request;

        $params = $request->getAllParams();
        $result = (new Models\Modules())->changeStatusSubModule($params);

        if ($result) {
            return ['success' => 1, 'message' => 'Menú actualizado correctamente'];
        }
        return ['success' => 0, 'message' => 'Error al actualizar el menú'];
    }
}
