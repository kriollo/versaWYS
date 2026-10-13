<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/routes/http_dashboard.php.
 *
 * This file contains the route definitions for the dashboard related endpoints.
 *
 * GRUPOS DE RUTAS:
 * 1. Acceso público (solo invitados)
 * 2. Páginas de autenticación (solo invitados)
 * 3. Procesos de autenticación (con CSRF)
 * 4. Panel administrativo (autenticado)
 * 5. Gestión de usuarios (autenticado + CSRF)
 * 6. Funciones de superadmin (autenticado + CSRF + superadmin)
 */

declare(strict_types=1);

namespace app\routes;

use app\controllers\DashBoardController;
use app\controllers\UsersController;
use app\middleware\AuthMiddleware;
use app\middleware\UsersMiddleware;
use versaWYS\kernel\Router;

// =====================================================
// 1. ACCESO PÚBLICO (Solo invitados)
// =====================================================
Router::group(
    '/',
    function (): void {
        // description: Define la página principal.
        Router::get('', [DashBoardController::class, 'index']);
    },
    [[AuthMiddleware::class, 'redirectIfSession']],
);

// =====================================================
// 2. PÁGINAS DE AUTENTICACIÓN (Solo invitados)
// =====================================================
Router::group(
    '/admin',
    function (): void {
        // description: Define la ruta para la página de inicio de sesión del panel de administración.
        Router::get('/login', [DashBoardController::class, 'login']);

        // description: Define la ruta para la página de recuperación de contraseña.
        Router::get('/lost-password', [DashBoardController::class, 'lostPassword']);

        // description: Define la ruta para la página de restablecimiento de contraseña.
        Router::get('/reset-password', [DashBoardController::class, 'resetPassword']);
    },
    [[AuthMiddleware::class, 'redirectIfSession']],
);

// =====================================================
// 3. PROCESOS DE AUTENTICACIÓN (Con validación CSRF)
// =====================================================
Router::group(
    '/admin',
    function (): void {
        // description: Define la ruta para el proceso de autenticación del inicio de sesión del panel de administración.
        // request-body name:email type:string description:Correo electrónico del administrador
        // request-body name:password type:string description:Contraseña del administrador
        Router::post('/login/autentication', [DashBoardController::class, 'autentication'])->middleware([
            [AuthMiddleware::class, 'checkLoginRateLimit'],
            [AuthMiddleware::class, 'validateParamsLogin'],
            [AuthMiddleware::class, 'validateAttemps'],
        ]);

        // description: Define la ruta para enviar el correo de recuperación de contraseña.
        // request-body name:email type:string description:Correo electrónico del administrador
        Router::patch('/lost-password/send', [DashBoardController::class, 'sendLostPassword'])->middleware([
            [AuthMiddleware::class, 'checkMail'],
        ]);

        // description: Define la ruta para aplicar el restablecimiento de contraseña.
        // request-body name:new_password type:string description:Nueva contraseña
        // request-body name:comfirm_new_password type:string description:Confirmación de la nueva contraseña
        // request-body name:tokenReset type:string description:Token temporal para restablecer la contraseña
        // request-body name:email type:string description:Correo electrónico del administrador
        Router::post('/login/apply-reset-password', [DashBoardController::class, 'applyChangePassword'])->middleware([
            [AuthMiddleware::class, 'validateParamsApplyResetPassword'],
        ]);
    },
    [[AuthMiddleware::class, 'validateCSRFToken']],
);

// =====================================================
// 4. PANEL ADMINISTRATIVO (Requiere autenticación)
// =====================================================
Router::group(
    '/admin',
    function (): void {
        // description: Define la ruta para el panel de administración.
        Router::get('', [DashBoardController::class, 'dashboard']);

        // description: Define la ruta para el panel de administración.
        Router::get('/dashboard', [DashBoardController::class, 'dashboard']);

        // description: Define la ruta para la página de perfil del usuario.
        Router::get('/perfiluser', [DashBoardController::class, 'perfilUser']);

        // description: define la ruta para informar que no tiene un plan activo.
    },
    [[AuthMiddleware::class, 'checkSession']],
);

// =====================================================
// ENDPOINTS API SIN MIDDLEWARE DE SESIÓN (manejan su propia validación)
// =====================================================
// description: Endpoint para verificar el estado de la sesión (heartbeat)
// Este endpoint maneja su propia validación de sesión para devolver JSON en lugar de redirecciones
Router::get('/admin/api/session/heartbeat', [DashBoardController::class, 'sessionHeartbeat']);

// description: Define la ruta para cerrar sesión de administración.
// El controlador valida el token y, si no es válido, redirige en vez de responder un error JSON
Router::post('/admin/logout', [DashBoardController::class, 'logout'])->withoutCsrf();
// =====================================================
// 5. GESTIÓN DE USUARIOS (Requiere autenticación + CSRF)
// =====================================================
Router::group(
    '/admin',
    function (): void {
        // description: Define la ruta para restablecer la contraseña por el usuario.
        // request-body name:id type:int description:ID del usuario
        // request-body name:password type:string description:Nueva contraseña
        // request-body name:password_confirmation type:string description:Confirmación de la nueva contraseña
        Router::post('/login/resetPassByUser', [UsersController::class, 'resetPassByIdUser'])->middleware([
            [UsersMiddleware::class, 'validateParamsResetPassByUser'],
        ]);

        // description: Actualiza el avatar del usuario.
        // request-body name:id type:int description:ID del usuario
        // request-body name:avatar type:file description:Archivo de imagen
        Router::post('/usuarios/updateAvatar', [UsersController::class, 'updateAvatarById'])->middleware([
            [UsersMiddleware::class, 'validateParamsUpdateAvatar'],
        ]);
    },
    [
        [AuthMiddleware::class, 'validateCSRFToken'],
        [AuthMiddleware::class, 'checkSession'],
    ],
);

// =====================================================
// 6. FUNCIONES EXCLUSIVAS DE SUPERADMIN
// =====================================================
Router::group(
    '/admin',
    function (): void {
        // description: Seleccionar empresa activa para superadmin
        Router::post('/selectempresa', [DashBoardController::class, 'selectEmpresa']);
    },
    [
        [AuthMiddleware::class, 'validateCSRFToken'],
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlySuperAdmin'],
    ],
);

// description: Muestra la política de privacidad (página pública).
Router::get('/privacy-policy', [DashBoardController::class, 'renderPrivacyPolicy']);

// description: Muestra las instrucciones para solicitar la eliminación de datos (página pública).
Router::get('/data-deletion', [DashBoardController::class, 'renderDataDeletion']);
