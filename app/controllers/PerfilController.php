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
}
