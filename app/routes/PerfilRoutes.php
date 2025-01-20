<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\PerfilController;
use app\middleware\AuthMiddleware;
use app\middleware\PerfilMiddleware;
use versaWYS\kernel\Router;

// description: Muestra la lista de perfiles en el panel de administración.
Router::get('/admin/perfiles', [PerfilController::class, 'index'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Obtiene todos los perfiles.
Router::get('/admin/perfiles/all', [PerfilController::class, 'getAllPerfiles'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Guarda un nuevo perfil.
// request-body name:nombre type:string description:Nombre del perfil
Router::post('/admin/perfiles/save', [PerfilController::class, 'savePerfil'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [PerfilMiddleware::class, 'validateParamsPerfil'],
]);

// description: Cambia el estado de un perfil.
// request-body name:id type:int description:ID del perfil
Router::patch('/admin/perfiles/changeState', [PerfilController::class, 'changeStatePerfil'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [PerfilMiddleware::class, 'validateParamsChangeState'],
]);

// description: Obtiene un perfil por ID.
// query-param name:id type:int description:ID del perfil
Router::get('/admin/perfiles/getPerfil/{id}', [PerfilController::class, 'getPerfil'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
]);

// description: Guarda los permisos de un perfil.
// request-body name:id type:int description:ID del perfil
// request-body name:nombre type:string description:Nombre del perfil
// request-body name:pagina_inicio type:string description:URL de la página de inicio del perfil
Router::post('/admin/perfiles/savePerfilPermisos', [PerfilController::class, 'savePerfilPermisos'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
    [AuthMiddleware::class, 'onlyAdmin'],
    [AuthMiddleware::class, 'validateCSRFToken'],
    [PerfilMiddleware::class, 'validateParamsSavePermisos'],
]);
