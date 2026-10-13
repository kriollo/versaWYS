<?php

declare(strict_types=1);

namespace app\routes;

use app\middleware\AuthMiddleware;
use versaWYS\kernel\Router;
use versaWYS\kernel\versaController;

// description: Muestra la documentación interactiva de las rutas registradas.
Router::get('/doc', [versaController::class, 'versaRoute'])->middleware([[AuthMiddleware::class, 'onlyDebug']]);
