<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
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
        $perfiles = (new Models\Perfil())->all();
        Response::json([
            'success' => 1,
            'data' => $perfiles,
        ]);
    }

    public function savePerfil(): void
    {
        global $request;
        $params = $request->getAllParams();
        $id = (new Models\Perfil())->save($params);

        if ($id === 0) {
            Response::json([
                'success' => 0,
                'message' => 'Error al guardar el perfil',
                'code' => 500,
            ]);
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
        $id = (new Models\Perfil())->changeState($params);

        if ($id === 0) {
            Response::json([
                'success' => 0,
                'message' => 'Error al cambiar el estado del perfil',
                'code' => 500,
            ]);
        }

        Response::json([
            'success' => 1,
            'message' => 'Estado del perfil cambiado correctamente',
            'code' => 200,
        ]);
    }

    public function getPerfil($slug = null): void
    {
        global $request;
        $result = (new Models\Perfil())->getPerfil((int) $slug);

        if ($result !== []) {
            $data = [];
            $urls = [];

            foreach ($result as $item) {
                if (!array_key_exists($item['seccion'], $data)) {
                    $data[$item['seccion']] = [];
                }
                if (!array_key_exists($item['menu'], $data[$item['seccion']])) {
                    $data[$item['seccion']][$item['menu']] = [
                        'id_menu' => $item['id_menu'],
                        'icon' => $item['icon'],
                    ];
                    $data[$item['seccion']][$item['menu']]['submenu'] = [];
                    if ($item['ifsubmenu'] === '1') {
                        array_push($urls, [
                            'url' => $item['url_submenu'],
                            'nombre' => $item['submenu'],
                        ]);
                    } else {
                        $data[$item['seccion']][$item['menu']]['checked'] = $item['checked'] == 1 ? true : false;
                        array_push($urls, [
                            'url' => $item['url_menu'],
                            'nombre' => $item['menu'],
                        ]);
                    }
                }
                if ($item['ifsubmenu'] === '1') {
                    $data[$item['seccion']][$item['menu']]['submenu'][] = [
                        'id_menu' => $item['id_menu'],
                        'id_submenu' => $item['id_submenu'],
                        'submenu' => $item['submenu'],
                        'checked' => $item['checked'] == 1 ? true : false,
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
        }

        Response::json([
            'success' => 1,
            'result_puro' => $result,
            'data' => $data,
            'urls' => $urls,
        ]);
    }

    public function savePerfilPermisos(): void
    {
        global $request;
        $params = $request->getAllParams();

        $id = (new Models\Perfil())->savePerfilPermisos($params);

        if ($id === 0) {
            Response::json([
                'success' => 0,
                'message' => 'Error al guardar el perfil',
                'code' => 500,
            ]);
        }

        Response::json([
            'success' => 1,
            'message' => 'Perfil guardado correctamente',
            'code' => 200,
        ]);
    }
}
