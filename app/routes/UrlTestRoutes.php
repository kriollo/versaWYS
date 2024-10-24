<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\UrlTestController;
use app\middleware\AuthMiddleware;
use app\middleware\UrlTestMiddleware;
use versaWYS\kernel\Router;

// Rutas de Navegador
// index => funcion que se ejecutara en el controlador
Router::get('/admin/urlTest', [UrlTestController::class, 'index'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyUserWithAccess'],
]);

Router::put('/admin/api/urlTest', [UrlTestController::class, 'returnApi'])->middleware([]);
