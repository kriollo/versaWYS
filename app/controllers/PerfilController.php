<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use versaWYS\kernel\CacheUsers;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\Response;

class PerfilController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function index(): string
    {
        return $this->template->render('dashboard/loader', [
            'm' => 'dashboard/js/perfil/dashPerfiles',
        ]);
    }

    public function getAllPerfiles(): void
    {
        $modelo = new Models\Perfil();
        $perfiles = $modelo->all((int) $this->empresaSelected['id']);
        Response::json([
            'success' => 1,
            'data' => $perfiles,
        ]);
    }

    public function savePerfil(): void
    {
        global $request;
        $params = $request->getAllParams();
        $params['id_empresa'] = (int) $this->empresaSelected['id'];

        $modelo = new Models\Perfil();
        $id = $modelo->savePerfil($params);

        if ($id === 0) {
            Response::json(['success' => 0, 'message' => 'Error al guardar el perfil'], 500);

            return;
        }

        Response::json([
            'success' => 1,
            'message' => 'Perfil guardado correctamente',
            'code' => 200,
        ]);
    }

    public function changeStatePerfil(): void
    {
        global $request;
        $params = $request->getAllParams();
        $params['id_empresa'] = (int) $this->empresaSelected['id'];
        $id = (new Models\Perfil())->changeState($params);

        if ($id === 0) {
            Response::json(['success' => 0, 'message' => 'Perfil no encontrado'], 404);

            return;
        }

        Response::json([
            'success' => 1,
            'message' => 'Estado del perfil cambiado correctamente',
            'code' => 200,
        ]);
    }

    public function getPerfil(int $id): void
    {
        $perfil = (new Models\Perfil())->getPerfil($id, (int) $this->empresaSelected['id']);
        if ($perfil === null) {
            Response::json(['success' => 0, 'message' => 'Perfil no encontrado'], 404);

            return;
        }

        $result = (new Models\Perfil())->getPerfilPermisos($id, (int) $this->empresaSelected['id']);

        $data = [];
        $urls = [
            [
                'url' => 'dashboard',
                'nombre' => 'Dashboard',
            ],
        ];
        if ($result !== []) {
            foreach ($result as $item) {
                if (!array_key_exists($item['seccion'], $data)) {
                    $data[$item['seccion']] = [];
                }

                if (!array_key_exists($item['menu'], $data[$item['seccion']])) {
                    $data[$item['seccion']][$item['menu']] = [
                        'id_menu' => $item['id_menu'],
                        'icon' => $item['icon'],
                        'fill_menu' => $item['fill_menu'],
                    ];
                    $data[$item['seccion']][$item['menu']]['submenu'] = [];
                    $urls[] = $item['ifsubmenu']
                        ? [
                            'url' => $item['url_submenu'],
                            'nombre' => $item['submenu'],
                        ]
                        : [
                            'url' => $item['url_menu'],
                            'nombre' => $item['menu'],
                        ];
                    if (!$item['ifsubmenu']) {
                        $data[$item['seccion']][$item['menu']]['checked'] = $item['checked'];
                    }
                }

                if ($item['ifsubmenu']) {
                    $data[$item['seccion']][$item['menu']]['submenu'][] = [
                        'id_menu' => $item['id_menu'],
                        'id_submenu' => $item['id_submenu'],
                        'submenu' => $item['submenu'],
                        'checked' => $item['checked'],
                        'url' => $item['url_submenu'],
                    ];
                }
            }
        }

        if ($result === null) {
            Response::json([
                'success' => 0,
                'message' => 'Perfil no encontrado',
                'code' => 404,
            ]);

            return;
        }

        Response::json([
            'success' => 1,
            'perfil' => $perfil,
            'result_puro' => $result,
            'data' => $data,
            'urls' => $urls,
        ]);
    }

    public function savePerfilPermisos(): void
    {
        global $request;
        $params = $request->getAllParams();

        $params['id_empresa'] = (int) $this->empresaSelected['id'];

        $id = (new Models\Perfil())->savePerfilPermisos($params);
        CacheUsers::invalidateAllUserCache();

        // Invalidar la versión del menú para todos los usuarios,
        // ya que los permisos han cambiado.
        (new CacheUsers())->updateMenuVersion();

        if ($id === 0) {
            Response::json(['success' => 0, 'message' => 'Perfil no encontrado'], 404);

            return;
        }

        Response::json([
            'success' => 1,
            'result' => $id,
            'message' => 'Perfil guardado correctamente',
            'code' => 200,
        ]);
    }
}
