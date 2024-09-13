<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\ModulesController;
use app\middleware\AuthMiddleware;
use app\middleware\ModulesMiddleware;
use versaWYS\kernel\Router;

Router::get('/admin/modulesRoutes', [ModulesController::class, 'index'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

//rutas de API's
Router::get('/admin/modules/getModulesPaginated', [ModulesController::class, 'getModulesPaginated'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

Router::post('/admin/modules/saveModule', [ModulesController::class, 'saveModule'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateSaveModuleParams'],
]);

Router::patch('/admin/modules/changeStatus', [ModulesController::class, 'changeStatus'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateChangeStatusParams'],
]);

Router::patch('/admin/modules/movePosition', [ModulesController::class, 'movePosition'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateMovePositionParams'],
]);
