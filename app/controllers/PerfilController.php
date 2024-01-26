<?php

declare(strict_types=1);

namespace app\controllers;

use app\Models as Models;
use versaWYS\kernel\Response;

class PerfilController {
    public static function index()
    {
        global $twig;
        return $twig->render('PerfilController/index');
    }
}