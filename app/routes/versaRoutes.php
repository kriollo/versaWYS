<?php

declare(strict_types=1);

namespace app\routes;

use app\middleware\AuthMiddleware;
use versaWYS\kernel\Router;
use versaWYS\kernel\versaController;

// description: Define the route for the versa documentation.
Router::get('/doc', [versaController::class, 'versaRoute'])->middleware([[AuthMiddleware::class, 'onlyDebug']]);
