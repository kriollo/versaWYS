<?php

declare(strict_types=1);

namespace app\routes;

use versaWYS\kernel\Router;
use app\controllers\UrlTestController;
use app\middleware\AuthMiddleware;
use app\middleware\UrltestMiddleware;

// Rutas de Navegador
// index => funcion que se ejecutara en el controlador
Router::get('/admin/urltest',
    [UrlTestController::class,'index']
)->middleware([
    // [AuthMiddleware::class, 'checkSession'],
    // [AuthMiddleware::class, 'onlyUserWithAccess'],
    // [UrltestMiddleware::class, 'middlewareMethod'],
]);
