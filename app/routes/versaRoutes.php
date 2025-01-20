<?php

declare(strict_types=1);

namespace app\routes;

use app\middleware\AuthMiddleware;
use versaWYS\kernel\Router;
use versaWYS\kernel\versaController;

// description: Define la ruta para la documentación de Versa.
Router::get('/doc', [versaController::class, 'versaRoute'])->middleware([[AuthMiddleware::class, 'onlyDebug']]);
