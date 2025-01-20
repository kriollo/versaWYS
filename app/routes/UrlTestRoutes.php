<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\UrlTestController;
use app\middleware\AuthMiddleware;
use app\middleware\UrltestMiddleware;
use versaWYS\kernel\Router;

// description: Muestra la página de prueba de URL.
Router::get('/admin/urltest', [UrlTestController::class, 'index'])->middleware([
    // [AuthMiddleware::class, 'checkSession'],
    // [AuthMiddleware::class, 'onlyUserWithAccess'],
    // [UrltestMiddleware::class, 'middlewareMethod'],
]);
