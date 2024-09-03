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
        return $this->template->render('dashboard/perfil/dashPerfil', [
            'menu_op' => ['id_menu' => 0],
        ]);
    }
}
