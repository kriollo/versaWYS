<?php

declare(strict_types=1);

namespace app\controllers;

use app\Models as Models;
use versaWYS\kernel\Globalcontrollers;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Response;

class ModulesController extends Globalcontrollers
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
    public function getModulesPaginated()
    {
        global $request;

        $params = Functions::getParamsPaginate($request, ['seccion', 'nombre', 'descripcion']);
        $filter = $params['filter'] ? $params['filter'] : '';
        $order = $params['order'] ? "ORDER BY seccion asc, $params[order]" : 'ORDER BY seccion asc , posicion DESC';
        $result = (new Models\Pagination())->pagination(
            'versamenu',
            ['id', 'seccion', 'nombre', 'descripcion', 'icono', 'estado', 'url', 'posicion'],
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
                        'icon' => 'bi bi-menu-app-fill text-xl text-blue-500',
                        'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
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

        Response::json(
            [
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
            ],
            200
        );
    }

    /**
     * Saves a module.
     *
     * @return void
     */
    public function saveModule()
    {
        global $request;

        $params = $request->getAllParams();
        $params['estado'] = $params['estado'] === 'true' ? 1 : 0;
        $result = (new Models\Modules())->saveModule($params);

        if ($result) {
            Response::json(['success' => 1, 'message' => 'Menú guardado correctamente'], 200);
        } else {
            Response::json(['success' => 0, 'message' => 'Error al crear el menú'], 500);
        }
    }

    /**
     * Change the status of a module.
     *
     * This method is responsible for changing the status of a module based on the provided parameters.
     *
     * @return void
     */
    public function changeStatus(): void
    {
        global $request;

        $params = $request->getAllParams();
        $result = (new Models\Modules())->changeStatus($params);

        if ($result) {
            Response::json(['success' => 1, 'message' => 'Menú actualizado correctamente'], 200);
        } else {
            Response::json(['success' => 0, 'message' => 'Error al actualizar el menú'], 500);
        }
    }

    public function movePosition(): void
    {
        global $request;

        $params = $request->getAllParams();
        (new Models\Modules())->movePosition($params);

        Response::json(['success' => 1], 200);
    }
}
