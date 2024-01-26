<?php

declare(strict_types=1);

namespace app\Routes;

use versaWYS\kernel\Router;
use app\middleware\AuthMiddleware;
use app\middleware\PerfilMiddleware;

Router::get('/admin/perfiles',
    [\app\controllers\PerfilController::class,'index']
);
