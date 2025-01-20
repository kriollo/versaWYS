<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\ModulesController;
use app\middleware\AuthMiddleware;
use app\middleware\ModulesMiddleware;
use versaWYS\kernel\Router;

// description: Muestra la lista de módulos en el panel de administración.
Router::get('/admin/modulesRoutes', [ModulesController::class, 'index'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Obtiene una lista paginada de módulos.
Router::get('/admin/modules/getModulesPaginated', [ModulesController::class, 'getModulesPaginated'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Guarda un nuevo módulo.
// request-body name:seccion type:string description:Sección del módulo
// request-body name:nombre type:string description:Nombre del módulo
// request-body name:descripcion type:string description:Descripción del módulo
// request-body name:icono type:string description:Ícono del módulo
// request-body name:estado type:string description:Estado del módulo
Router::post('/admin/modules/saveModule', [ModulesController::class, 'saveModule'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateSaveModuleParams'],
]);

// description: Cambia el estado de un módulo.
// request-body name:id type:int description:ID del módulo
// request-body name:estado type:string description:Estado del módulo
Router::patch('/admin/modules/changeStatus', [ModulesController::class, 'changeStatus'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateChangeStatusParams'],
]);

// description: Cambia la posición de un módulo.
// request-body name:id type:int description:ID del módulo
// request-body name:accion type:string description:Acción a realizar (subir/bajar)
Router::patch('/admin/modules/movePosition', [ModulesController::class, 'movePosition'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateMovePositionParams'],
]);

// description: Obtiene los submódulos de un módulo.
Router::get('/admin/modules/getSubModules', [ModulesController::class, 'getSubModules'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Cambia el estado de un submódulo.
// request-body name:id type:int description:ID del submódulo
// request-body name:id_menu type:int description:ID del menú
// request-body name:estado type:string description:Estado del submódulo
Router::patch('/admin/submodules/changeStatus', [ModulesController::class, 'changeStatusSubModule'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateChangeStatusSubModulesParams'],
]);

// description: Guarda un nuevo submódulo.
// request-body name:id_menu type:int description:ID del menú
// request-body name:nombre type:string description:Nombre del submódulo
// request-body name:descripcion type:string description:Descripción del submódulo
// request-body name:url type:string description:URL del submódulo
// request-body name:estado type:string description:Estado del submódulo
Router::post('/admin/submodules/saveModule', [ModulesController::class, 'saveSubModule'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateSaveSubModuleParams'],
]);

// description: Cambia la posición de un submódulo.
// request-body name:id type:int description:ID del submódulo
// request-body name:id_menu type:int description:ID del menú
// request-body name:position type:int description:Nueva posición del submódulo
Router::patch('/admin/submodules/changePositionSubModule', [
    ModulesController::class,
    'movePositionSubModule',
])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [ModulesMiddleware::class, 'validateMovePositionSubModuleParams'],
]);
