<?php

declare(strict_types=1);

namespace app\controllers;

use versaWYS\kernel\Globalcontrollers;
use app\Models as Models;
use versaWYS\kernel\Response;

class PerfilController extends Globalcontrollers
{

    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    public function index()
    {
        return $this->template->render('dashboard/perfil/dashPerfil', [
            'menu_op' => ['id_menu' => 0]
        ]);
    }
}
