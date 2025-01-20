<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/routes/http_dashboard.php
 *
 * This file contains the route definitions for the dashboard related endpoints.
 */

declare(strict_types=1);

namespace app\routes;

use app\controllers\DashBoardController;
use app\controllers\UsersController;
use app\middleware\AuthMiddleware;
use app\middleware\UsersMiddleware;
use versaWYS\kernel\Router;

// description: Define la página principal.
Router::get('/', [DashBoardController::class, 'index'])->middleware([[AuthMiddleware::class, 'redirectIfSession']]);

// description: Define la ruta para el panel de administración.
Router::get('/admin', [DashBoardController::class, 'dashboard'])->middleware([[AuthMiddleware::class, 'checkSession']]);

// description: Define la ruta para la página de inicio de sesión del panel de administración.
Router::get('/admin/login', [DashBoardController::class, 'login'])->middleware([
    [AuthMiddleware::class, 'redirectIfSession'],
]);

// description: Define la ruta para el proceso de autenticación del inicio de sesión del panel de administración.
// request-body name:email type:string description:Correo electrónico del administrador
// request-body name:password type:string description:Contraseña del administrador
Router::post('/admin/login/autentication', [DashBoardController::class, 'autentication'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'validateParamsLogin'],
    [AuthMiddleware::class, 'validateAttemps'],
]);

// description: Define la ruta para cerrar sesión de administración.
Router::get('/admin/logout', [DashBoardController::class, 'logout'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
]);

// description: Define la ruta para el panel de administración.
Router::get('/admin/dashboard', [DashBoardController::class, 'dashboard'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
]);

// description: Define la ruta para la página de recuperación de contraseña.
Router::get('/admin/lost-password', [DashBoardController::class, 'lostPassword'])->middleware([
    [AuthMiddleware::class, 'redirectIfSession'],
]);

// description: Define la ruta para enviar el correo de recuperación de contraseña.
// request-body name:email type:string description:Correo electrónico del administrador
Router::patch('/admin/lost-password/send', [DashBoardController::class, 'sendLostPassword'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkMail'],
]);

// description: Define la ruta para la página de restablecimiento de contraseña.
Router::get('/admin/reset-password', [DashBoardController::class, 'resetPassword']);

// description: Define la ruta para aplicar el restablecimiento de contraseña.
// request-body name:new_password type:string description:Nueva contraseña
// request-body name:comfirm_new_password type:string description:Confirmación de la nueva contraseña
// request-body name:tokenReset type:string description:Token temporal para restablecer la contraseña
// request-body name:email type:string description:Correo electrónico del administrador
Router::post('/admin/login/apply-reset-password', [DashBoardController::class, 'applyChangePassword'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'validateParamsApplyResetPassword'],
]);

// description: Define la ruta para restablecer la contraseña por el usuario.
// request-body name:id type:int description:ID del usuario
// request-body name:password type:string description:Nueva contraseña
// request-body name:password_confirmation type:string description:Confirmación de la nueva contraseña
Router::post('/admin/login/resetPassByUser', [UsersController::class, 'resetPassByIdUser'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [UsersMiddleware::class, 'validateParamsResetPassByUser'],
]);

// description: Actualiza el avatar del usuario.
// request-body name:id type:int description:ID del usuario
// request-body name:avatar type:file description:Archivo de imagen
Router::post('/admin/usuarios/updateAvatar', [UsersController::class, 'updateAvatarById'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [UsersMiddleware::class, 'validateParamsUpdateAvatar'],
]);

// description: Define la ruta para la página de perfil del usuario.
Router::get('/admin/perfiluser', [DashBoardController::class, 'perfiluser'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
]);
