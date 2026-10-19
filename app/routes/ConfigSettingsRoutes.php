<?php

/**
 * FILEPATH: app/routes/ConfigSettingsRoutes.php.
 *
 * Rutas para la pantalla de Configuración del panel admin (solo superadmin).
 * Permite editar un subconjunto seguro de versaWYS/kernel/config/config.json (mail, intentos de
 * login, política de contraseñas, build y admin), sin tocar claves sensibles (database, session,
 * twig, redis, rate_limiting, assets).
 *
 * GRUPOS DE RUTAS:
 * 1. Consulta de configuración (solo lectura)
 * 2. Guardado de configuración (escritura con CSRF)
 */

declare(strict_types=1);

namespace app\routes;

use app\controllers\ConfigSettingsController;
use app\middleware\AuthMiddleware;
use app\middleware\ConfigSettingsMiddleware;
use versaWYS\kernel\Router;

// =====================================================
// 1. CONSULTA DE CONFIGURACIÓN (Solo lectura - SuperAdmin)
// =====================================================
Router::group(
    '/admin',
    function (): void {
        // description: Muestra la pantalla de configuración de la aplicación.
        Router::get('/configuracionRoutes', [ConfigSettingsController::class, 'index']);

        // description: Obtiene el subconjunto editable de la configuración de la app.
        Router::get('/configuracion/get', [ConfigSettingsController::class, 'getSettings']);

        // description: Tareas programadas (app/schedule.php) con su próxima y última ejecución, y si el crontab llama a app:schedule:run.
        Router::get('/configuracion/tareas', [ConfigSettingsController::class, 'scheduledTasks']);
    },
    [
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlySuperAdmin'],
    ],
);

// =====================================================
// 2. GUARDADO DE CONFIGURACIÓN (Escritura + CSRF + SuperAdmin)
// =====================================================
Router::group(
    '/admin/configuracion',
    function (): void {
        // description: Guarda el subconjunto editable de la configuración de la app.
        // request-body name:mail type:object description:Configuración de correo saliente
        // request-body name:login_attempt type:object description:Intentos de login permitidos
        // request-body name:auth type:object description:Política de contraseñas y expiración
        // request-body name:build type:object description:Datos generales y build de la app
        // request-body name:admin type:object description:Configuración del panel de administración
        Router::post('/save', [ConfigSettingsController::class, 'saveSettings'])->middleware([
            [ConfigSettingsMiddleware::class, 'validateSaveSettingsParams'],
        ]);

        // description: Envía un correo de prueba al usuario en sesión con la configuración guardada.
        Router::post('/testMail', [ConfigSettingsController::class, 'testMail']);

        // description: Ejecuta ahora una tarea programada, en segundo plano.
        // request-body name:id type:string description:Id de la tarea (el de app:schedule:list)
        Router::post('/tareas/ejecutar', [ConfigSettingsController::class, 'runScheduledTask']);
    },
    [
        [AuthMiddleware::class, 'validateCSRFToken'],
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlySuperAdmin'],
    ],
);
