<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/routes/PerfilRoutes.php.
 *
 * This file contains the route definitions for the profiles management endpoints.
 *
 * GRUPOS DE RUTAS:
 * 1. Consultas de perfiles (solo lectura)
 * 2. Gestión básica de perfiles (escritura sin CSRF)
 * 3. Gestión avanzada de perfiles (escritura con CSRF)
 */

declare(strict_types=1);

namespace app\routes;

use app\controllers\PerfilController;
use app\middleware\AuthMiddleware;
use app\middleware\PerfilMiddleware;
use versaWYS\kernel\Router;

// =====================================================
// 1. CONSULTAS DE PERFILES (Solo lectura - Admin)
// =====================================================
Router::group(
    '/admin/perfiles',
    function (): void {
        // description: Muestra la lista de perfiles en el panel de administración.
        Router::get('', [PerfilController::class, 'index']);

        // description: Obtiene todos los perfiles.
        Router::get('/all', [PerfilController::class, 'getAllPerfiles']);

        // description: Obtiene un perfil por ID.
        // path-param name:id type:int description:ID del perfil que se consulta
        Router::get('/getPerfil/{id}', [PerfilController::class, 'getPerfil']);
    },
    [
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlyAdmin'],
    ],
);

// =====================================================
// 2. GESTIÓN BÁSICA DE PERFILES (Escritura + CSRF - Admin)
// =====================================================
Router::group(
    '/admin/perfiles',
    function (): void {
        // description: Guarda un nuevo perfil.
        // request-body name:nombre type:string description:Nombre del perfil
        Router::post('/save', [PerfilController::class, 'savePerfil'])->middleware([
            [PerfilMiddleware::class, 'validateParamsPerfil'],
        ]);

        // description: Cambia el estado de un perfil.
        // request-body name:id type:int description:ID del perfil
        Router::patch('/changeState', [PerfilController::class, 'changeStatePerfil'])->middleware([
            [PerfilMiddleware::class, 'validateParamsChangeState'],
        ]);
    },
    [
        [AuthMiddleware::class, 'validateCSRFToken'],
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlyAdmin'],
    ],
);

// =====================================================
// 3. GESTIÓN AVANZADA DE PERFILES (Escritura + CSRF - Admin)
// =====================================================
Router::group(
    '/admin/perfiles',
    function (): void {
        // description: Guarda los permisos de un perfil.
        // request-body name:id type:int description:ID del perfil
        // request-body name:nombre type:string description:Nombre del perfil
        // request-body name:pagina_inicio type:string description:URL de la página de inicio del perfil
        Router::post('/savePerfilPermisos', [
            PerfilController::class,
            'savePerfilPermisos',
        ])->middleware([
            [PerfilMiddleware::class, 'validateParamsSavePermisos'],
        ]);
    },
    [
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlyAdmin'],
        [AuthMiddleware::class, 'validateCSRFToken'],
    ],
);
