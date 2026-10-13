<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/routes/ModulesRoutes.php.
 *
 * This file contains the route definitions for the modules management endpoints.
 *
 * GRUPOS DE RUTAS:
 * 1. Consultas de módulos (solo lectura)
 * 2. Gestión de módulos (escritura con CSRF)
 * 3. Gestión de submódulos (escritura con CSRF)
 */

declare(strict_types=1);

namespace app\routes;

use app\controllers\ModulesController;
use app\middleware\AuthMiddleware;
use app\middleware\ModulesMiddleware;
use versaWYS\kernel\Router;

// =====================================================
// 1. CONSULTAS DE MÓDULOS (Solo lectura - SuperAdmin)
// =====================================================
Router::group(
    '/admin',
    function (): void {
        // description: Muestra la lista de módulos en el panel de administración.
        Router::get('/modulesRoutes', [ModulesController::class, 'index']);

        // description: Obtiene una lista paginada de módulos.
        // query-param name:page type:int description:Número de página (desde 1)
        // query-param name:per_page type:int description:Filas por página (máximo 5000)
        // query-param name:filter type:string description:Texto a buscar
        // query-param name:order type:string description:Columna y dirección, por ejemplo nombre,asc
        // query-param name:externalFilters type:string description:Filtros permitidos: estado=true o estado=false
        Router::get('/modules/getModulesPaginated', [
            ModulesController::class,
            'getModulesPaginated',
        ]);

        // description: Obtiene una lista paginada de los submódulos de un módulo.
        // query-param name:page type:int description:Número de página (desde 1)
        // query-param name:per_page type:int description:Filas por página (máximo 5000)
        // query-param name:filter type:string description:Texto a buscar
        // query-param name:order type:string description:Columna y dirección, por ejemplo nombre,asc
        // query-param name:externalFilters type:string description:Filtro por módulo: id_menu=ID del módulo
        Router::get('/modules/getSubModules', [ModulesController::class, 'getSubModules']);
    },
    [
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlySuperAdmin'],
    ],
);

// =====================================================
// 2. GESTIÓN DE MÓDULOS (Escritura + CSRF + SuperAdmin)
// =====================================================
Router::group(
    '/admin/modules',
    function (): void {
        // description: Guarda un nuevo módulo.
        // request-body name:seccion type:string description:Sección del módulo
        // request-body name:nombre type:string description:Nombre del módulo
        // request-body name:descripcion type:string description:Descripción del módulo
        // request-body name:icono type:string description:Ícono del módulo
        // request-body name:estado type:string description:Estado del módulo
        Router::post('/saveModule', [ModulesController::class, 'saveModule'])->middleware([
            [ModulesMiddleware::class, 'validateSaveModuleParams'],
        ]);

        // description: Cambia el estado de un módulo.
        // request-body name:id type:int description:ID del módulo
        // request-body name:estado type:string description:Estado del módulo
        Router::patch('/changeStatus', [ModulesController::class, 'changeStatus'])->middleware([
            [ModulesMiddleware::class, 'validateChangeStatusParams'],
        ]);

        // description: Cambia la posición de un módulo.
        // request-body name:id type:int description:ID del módulo
        // request-body name:accion type:string description:Acción a realizar (subir/bajar)
        Router::patch('/movePosition', [ModulesController::class, 'movePosition'])->middleware([
            [ModulesMiddleware::class, 'validateMovePositionParams'],
        ]);
    },
    [
        [AuthMiddleware::class, 'validateCSRFToken'],
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlySuperAdmin'],
    ],
);

// =====================================================
// 3. GESTIÓN DE SUBMÓDULOS (Escritura + CSRF + SuperAdmin)
// =====================================================
Router::group(
    '/admin/submodules',
    function (): void {
        // description: Cambia el estado de un submódulo.
        // request-body name:id type:int description:ID del submódulo
        // request-body name:id_menu type:int description:ID del menú
        // request-body name:estado type:string description:Estado del submódulo
        Router::patch('/changeStatus', [
            ModulesController::class,
            'changeStatusSubModule',
        ])->middleware([
            [ModulesMiddleware::class, 'validateChangeStatusSubModulesParams'],
        ]);

        // description: Guarda un nuevo submódulo.
        // request-body name:id_menu type:int description:ID del menú
        // request-body name:nombre type:string description:Nombre del submódulo
        // request-body name:descripcion type:string description:Descripción del submódulo
        // request-body name:url type:string description:URL del submódulo
        // request-body name:estado type:string description:Estado del submódulo
        Router::post('/saveModule', [ModulesController::class, 'saveSubModule'])->middleware([
            [ModulesMiddleware::class, 'validateSaveSubModuleParams'],
        ]);

        // description: Cambia la posición de un submódulo.
        // request-body name:id type:int description:ID del submódulo
        // request-body name:id_menu type:int description:ID del menú
        // request-body name:position type:int description:Nueva posición del submódulo
        Router::patch('/changePositionSubModule', [
            ModulesController::class,
            'movePositionSubModule',
        ])->middleware([
            [ModulesMiddleware::class, 'validateMovePositionSubModuleParams'],
        ]);
    },
    [
        [AuthMiddleware::class, 'validateCSRFToken'],
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlySuperAdmin'],
    ],
);
