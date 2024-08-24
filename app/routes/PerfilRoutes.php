<?php

declare(strict_types=1);

namespace app\Routes;

use app\controllers\PerfilController;
use app\middleware\AuthMiddleware;
use app\middleware\PerfilMiddleware;
use versaWYS\kernel\Router;

Router::get('/admin/perfiles', [PerfilController::class, 'index'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);
