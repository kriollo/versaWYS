<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\UsersController;
use app\middleware\AuthMiddleware;
use app\middleware\UsersMiddleware;
use versaWYS\kernel\Router;

// description: Muestra la lista de usuarios en el panel de administración.
Router::get('/admin/usuarios', [UsersController::class, 'index'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Muestra el formulario para agregar un nuevo usuario.
Router::get('/admin/usuarios/addUser', [UsersController::class, 'addUserTemplate'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Muestra el formulario para editar un usuario existente.
// query-param name:id type:int description:ID del usuario a editar
Router::get('/admin/usuarios/editUser/{id}', [UsersController::class, 'editUserTemplate'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Obtiene una lista paginada de usuarios.
Router::get('/admin/users/getUsersPaginated', [UsersController::class, 'getUsersPaginated'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Registra un nuevo usuario.
// request-body name:name type:string description:Nombre del usuario
// request-body name:email type:string description:Correo electrónico del usuario
// request-body name:password type:string description:Contraseña del usuario
Router::post('/admin/users/addUser', [UsersController::class, 'registerUser'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [UsersMiddleware::class, 'validateRegisterParams'],
]);

// description: Edita un usuario existente.
// request-body name:tokenid type:string description:Token único del usuario
// request-body name:name type:string description:Nombre del usuario
// request-body name:email type:string description:Correo electrónico del usuario
// request-body name:password type:string description:Contraseña del usuario
Router::put('/admin/users/editUser', [UsersController::class, 'editUser'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [UsersMiddleware::class, 'validateEditParams'],
]);

// description: Elimina un usuario.
// request-body name:tokenid type:string description:Token único del usuario
Router::delete('/admin/users/deleteUser', [UsersController::class, 'deleteUser'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Cambia la contraseña de un usuario.
// request-body name:tokenid type:string description:Token único del usuario
// request-body name:new_password type:string description:Nueva contraseña del usuario
Router::patch('/admin/users/changePassword', [UsersController::class, 'changePassword'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [UsersMiddleware::class, 'validateChangePasswordParams'],
]);
