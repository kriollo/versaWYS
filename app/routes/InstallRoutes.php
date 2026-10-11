<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\InstallController;
use app\middleware\AuthMiddleware;
use app\middleware\InstallMiddleware;
use versaWYS\kernel\Router;

// Asistente de instalación de primera ejecución.
Router::get('/install', [InstallController::class, 'index'])->middleware([
    [InstallMiddleware::class, 'checkNotInstalled'],
]);

Router::post('/install/run', [InstallController::class, 'run'])->middleware([
    [InstallMiddleware::class, 'checkNotInstalled'],
    [AuthMiddleware::class, 'validateCSRFToken'],
    [InstallMiddleware::class, 'checkInstallToken'],
    [InstallMiddleware::class, 'validateParams'],
]);
