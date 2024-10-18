<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\UsersController;
use app\middleware\AuthMiddleware;
use app\middleware\UsersMiddleware;
use versaWYS\kernel\Router;

// Rutas de Navegador
Router::get('/admin/usuarios', [UsersController::class, 'index'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);
Router::get('/admin/usuarios/addUser', [UsersController::class, 'addUserTemplate'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);
Router::get('/admin/usuarios/editUser/{id}', [UsersController::class, 'editUserTemplate'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

//Rutas de API
Router::get('/admin/users/getUsersPaginated', [UsersController::class, 'getUsersPaginated'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

Router::post('/admin/users/addUser', [UsersController::class, 'registerUser'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [UsersMiddleware::class, 'validateRegisterParams'],
]);

Router::put('/admin/users/editUser', [UsersController::class, 'editUser'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [UsersMiddleware::class, 'validateEditParams'],
]);

Router::delete('/admin/users/deleteUser', [UsersController::class, 'deleteUser'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

Router::patch('/admin/users/changePassword', [UsersController::class, 'changePassword'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [UsersMiddleware::class, 'validateChangePasswordParams'],
]);
