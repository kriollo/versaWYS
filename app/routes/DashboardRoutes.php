<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/routes/http_dashboard.php
 *
 * This file contains the route definitions for the dashboard related endpoints.
 */

//TODO: portal de acceso para usuarios dt.gob.cl
//TODO: si la IP es de la red interna de la DT (200.72.242.0/27), redirigir a la página de acceso de la DT SIN VALIDAR

declare(strict_types=1);

namespace app\routes;

use app\controllers\DashBoardController;
use app\controllers\UsersController;
use app\middleware\AuthMiddleware;
use app\middleware\UsersMiddleware;
use versaWYS\kernel\Router;

// description: Define the root page.
Router::get('/', [DashBoardController::class, 'index'])->middleware([[AuthMiddleware::class, 'redirectIfSession']]);

// description: Define the route for the admin dashboard.
Router::get('/admin', [DashBoardController::class, 'dashboard'])->middleware([[AuthMiddleware::class, 'checkSession']]);

// description: Define the route for the admin login page.
Router::get('/admin/login', [DashBoardController::class, 'login'])->middleware([
    [AuthMiddleware::class, 'redirectIfSession'],
]);

// description: Define the route for the authentication process of the admin login.
Router::post('/admin/login/autentication', [DashBoardController::class, 'autentication'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'validateParamsLogin'],
    [AuthMiddleware::class, 'validateAttemps'],
]);

// description: Define the route for the admin logout.
Router::get('/admin/logout', [DashBoardController::class, 'logout'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
]);

// description: Define the route for the admin dashboard.
Router::get('/admin/dashboard', [DashBoardController::class, 'dashboard'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
]);

// description: Define the route for the lost password page.
Router::get('/admin/lost-password', [DashBoardController::class, 'lostPassword'])->middleware([
    [AuthMiddleware::class, 'redirectIfSession'],
]);

// description: Define the route for sending the lost password email.
Router::patch('/admin/lost-password/send', [DashBoardController::class, 'sendLostPassword'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkMail'],
]);

// description: Define the API route for the reset password page.
Router::get('/admin/reset-password', [DashBoardController::class, 'resetPassword']);

// description: Define the API route for applying the password reset.
// request-body name:new_password type:string description:Nueva contraseña
// request-body name:comfirm_new_password type:string description:Confirmación de la nueva contraseña
// request-body name:tokenReset type:string description:Token temporal para resetear la contraseña
// request-body name:email type:int description:Email del usuario
Router::post('/admin/login/apply-reset-password', [DashBoardController::class, 'applyChangePassword'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'validateParamsApplyResetPassword'],
]);

// description: Define the route for reset password by user.
// request-body name:id type:int description:ID del usuario
// request-body name:password type:string description:Nueva contraseña
// request-body name:password_confirmation type:string description:Confirmación de la nueva contraseña
Router::post('/admin/login/resetPassByUser', [UsersController::class, 'resetPassByIdUser'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [UsersMiddleware::class, 'validateParamsResetPassByUser'],
]);

// description: Actualiza el avatar del usuario
// request-body name:id type:int description:ID del usuario
// request-body name:avatar type:file description:Archivo de imagen
Router::post('/admin/usuarios/updateAvatar', [UsersController::class, 'updateAvatarById'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [UsersMiddleware::class, 'validateParamsUpdateAvatar'],
]);

// description: Define the route for the profile user page.
Router::get('/admin/perfiluser', [DashBoardController::class, 'perfiluser'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
]);
