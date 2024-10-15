<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\PerfilController;
use app\middleware\AuthMiddleware;
use app\middleware\PerfilMiddleware;
use versaWYS\kernel\Router;

Router::get('/admin/perfiles', [PerfilController::class, 'index'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// API
Router::get('/admin/perfiles/all', [PerfilController::class, 'getAllPerfiles'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

Router::post('/admin/perfiles/save', [PerfilController::class, 'savePerfil'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [PerfilMiddleware::class, 'validateParamsPerfil'],
]);

Router::patch('/admin/perfiles/changeState', [PerfilController::class, 'changeStatePerfil'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [PerfilMiddleware::class, 'validateParamsChangeState'],
]);

Router::get('/admin/perfiles/getPerfil/{id}', [PerfilController::class, 'getPerfil'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

Router::post('/admin/perfiles/savePerfilPermisos', [PerfilController::class, 'savePerfilPermisos'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [AuthMiddleware::class, 'validateCSRFToken'],
    [PerfilMiddleware::class, 'validateParamsSavePermisos'],
]);
