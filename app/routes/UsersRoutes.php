<?php

declare(strict_types=1);

namespace app\Routes;

use versaWYS\kernel\Router;
use app\middleware\AuthMiddleware;
use app\middleware\UsersMiddleware;

// Rutas de Navegador
Router::get('/admin/usuarios',
    [\app\controllers\UsersController::class, 'index']
)->middleware([
            [AuthMiddleware::class, 'checkSession'],
            [AuthMiddleware::class, 'onlyAdmin']
        ]);
Router::get('/admin/usuarios/addUser',
    [\app\controllers\UsersController::class, 'addUserTemplate']
)->middleware([
            [AuthMiddleware::class, 'checkSession'],
            [AuthMiddleware::class, 'onlyAdmin']
        ]);

Router::get('/admin/usuarios/editUser/{id}',
    [\app\controllers\UsersController::class, 'editUserTemplate']
)->middleware([
            [AuthMiddleware::class, 'checkSession'],
            [AuthMiddleware::class, 'onlyAdmin']
        ]);

//Rutas de API
Router::get('/admin/users/getAllUsers',
    [\app\controllers\UsersController::class, 'getAllUsers']
)->middleware([
            [AuthMiddleware::class, 'checkSession']
        ]);

Router::post('/admin/users/addUser',
    [\app\controllers\UsersController::class, 'registerUser']
)->middleware([
            [AuthMiddleware::class, 'validateCSRFToken'],
            [AuthMiddleware::class, 'checkSession'],
            [UsersMiddleware::class, 'validateRegisterParams']
        ]);

Router::put('/admin/users/editUser',
    [\app\controllers\UsersController::class, 'editUser']
)->middleware([
            [AuthMiddleware::class, 'validateCSRFToken'],
            [AuthMiddleware::class, 'checkSession'],
            [UsersMiddleware::class, 'validateEditParams']
        ]);

Router::delete('/admin/users/deleteUser',
    [\app\controllers\UsersController::class, 'deleteUser']
)->middleware([
            [AuthMiddleware::class, 'checkSession']
        ]);

Router::patch('/admin/users/changePassword',
    [\app\controllers\UsersController::class, 'changePassword']
)->middleware([
            [AuthMiddleware::class, 'validateCSRFToken'],
            [AuthMiddleware::class, 'checkSession'],
            [UsersMiddleware::class, 'validateChangePasswordParams']
        ]);


