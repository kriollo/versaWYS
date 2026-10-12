<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use versaWYS\kernel\CacheUsers;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\helpers\Functions;

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
     * @return string the rendered template for the index page
     */
    public function index(): string
    {
        return $this->template->render('dashboard/loader', [
            'm' => '/dashboard/js/modules/dashModules',
        ]);
    }

    /**
     * Retrieves paginated modules.
     *
     * @return void
     */
    public function getModulesPaginated(): array
    {
        global $request;

        $params = $this->getParamsPaginate($request, ['seccion', 'nombre', 'descripcion'], true, 'posicion,asc', ['estado']);
        $filter = $params['filter'] ?? '';
        $order = $params['order'] ?: 'posicion ASC, seccion ASC';
        $modelo = new Models\Pagination();
        $result = $modelo->pagination(
            'versa_menu',
            ['id', 'seccion', 'nombre', 'descripcion', 'icono', 'fill', 'estado', 'url', 'posicion', 'submenu'],
            $filter,
            $order,
            $params['limitNumber'],
            $params['offset'],
            $params['bindings'],
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
                'field' => 'posicion',
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
                'export' => true,
            ],
            ['field' => 'seccion', 'title' => 'Sección', 'export' => true],
            ['field' => 'nombre', 'title' => 'Nombre Menú', 'export' => true],
            ['field' => 'descripcion', 'title' => 'Descripción', 'export' => true],
            ['field' => 'url', 'title' => 'URL', 'export' => true],
            ['field' => 'icono', 'title' => 'Icono SVG', 'type' => 'svg'],
            ['field' => 'estado', 'title' => 'Estado', 'type' => 'status', 'export' => true],
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
                        'condition_value' => true,
                    ],
                    [
                        'title' => 'Ver Submenús',
                        'icon' => 'bi bi-menu-app-fill text-xl text-blue-500  dark:text-blue-300',
                        'class' => [
                            'condition_field' => 'submenu',
                            'condition_value' => true,
                            'active' => 'bg-gray-200 hover:bg-gray-600 dark:bg-gray-500 dark:hover:bg-gray-600',
                            'inactive' => 'text-gray-400 dark:text-gray-400',
                        ],
                        'action' => 'viewSubmenus',
                        'condition' => 'estado',
                        'condition_value' => true,
                    ],
                    [
                        'title' => 'Desactivar Menú',
                        'icon' => 'bi bi-trash-fill text-xl text-red-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'changeStatus',
                        'condition' => 'estado',
                        'condition_value' => true,
                    ],
                    [
                        'title' => 'Activar Menú',
                        'icon' => 'bi bi-recycle text-xl text-green-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'action' => 'changeStatus',
                        'condition' => 'estado',
                        'condition_value' => false,
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
        $params['estado'] = $params['estado'] === 'true' || $params['estado'] === 1;
        $params['fill'] = $params['fill'] === 'true' || $params['fill'] === 1;

        // Sanitizar el icono SVG antes de guardar
        if (isset($params['icono'])) {
            $params['icono'] = Functions::sanitizeSvgContent($params['icono']);
        }

        $modelo = new Models\Modules();
        $result = $modelo->saveModule($params);

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

        $params = $request->getAllParams();
        $data = (new Models\Modules())->movePosition($params);

        (new CacheUsers())->updateMenuVersion();
        CacheUsers::invalidateAllUserCache();

        return ['success' => 1, 'data' => $data];
    }

    // submodules
    public function getSubModules(): array
    {
        global $request;

        $params = $this->getParamsPaginate($request, ['nombre', 'descripcion'], true, 'posicion,asc', ['id_menu']);

        $filter = $params['filter'] ?? '';
        $order = $params['order'] ?? 'posicion DESC, id ASC';
        $result = (new Models\Pagination())->pagination(
            'versa_submenu',
            ['id_menu', 'id', 'nombre', 'descripcion', 'estado', 'url', 'posicion'],
            $filter,
            $order,
            $params['limitNumber'],
            $params['offset'],
            $params['bindings'],
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
                'field' => 'posicion',
                'title' => 'Posición',
                'type' => 'position',
                'buttons' => [
                    [
                        'type' => 'up',
                        'title' => 'Arriba',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                        'icon' => 'bi bi-arrow-up ',
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
                'export' => true,
            ],
            ['field' => 'nombre', 'title' => 'Nombre Menú', 'export' => true],
            ['field' => 'descripcion', 'title' => 'Descripción', 'export' => true],
            ['field' => 'url', 'title' => 'URL', 'export' => true],
            ['field' => 'estado', 'title' => 'Estado', 'type' => 'status', 'export' => true],
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

    public function movePositionSubModule(): array
    {
        global $request;

        $params = $request->getAllParams();
        $data = (new Models\Modules())->movePositionSubModule($params);

        (new CacheUsers())->updateMenuVersion();
        CacheUsers::invalidateAllUserCache();

        return ['success' => 1, 'message' => 'Posición actualizada correctamente', 'data' => $data];
    }
}
