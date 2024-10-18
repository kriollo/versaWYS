<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\Response;

//Controlador de la vista UrlTestController
class UrlTestController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    public function index()
    {
        return $this->template->render('dashboard/loader', [
            'm' => 'dashboard/js/UrlTestController/dashUrlTestControllerLimpio',
        ]);
    }
}
