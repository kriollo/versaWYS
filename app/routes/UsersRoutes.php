<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/routes/UsersRoutes.php.
 *
 * This file contains the route definitions for the users management endpoints.
 *
 * GRUPOS DE RUTAS:
 * 1. Interfaces de usuario (solo lectura - templates y vistas)
 * 2. Consultas de datos (solo lectura - APIs)
 * 3. Operaciones básicas (escritura sin CSRF)
 * 4. Operaciones críticas (escritura con CSRF)
 */

declare(strict_types=1);

namespace app\routes;

use app\controllers\UsersController;
use app\middleware\AuthMiddleware;
use app\middleware\UsersMiddleware;
use versaWYS\kernel\Router;

// =====================================================
// 1. INTERFACES DE USUARIO (Templates y vistas - Admin)
// =====================================================
Router::group(
    '/admin/usuarios',
    function (): void {
        // description: Muestra la lista de usuarios en el panel de administración.
        Router::get('', [UsersController::class, 'index']);

        // description: Muestra el formulario para agregar un nuevo usuario.
        Router::get('/addUser', [UsersController::class, 'addUserTemplate']);

        // description: Muestra el formulario para editar un usuario existente.
        // path-param name:id type:int description:ID del usuario que se va a editar
        Router::get('/editUser/{id}', [UsersController::class, 'editUserTemplate']);
    },
    [
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlyAdmin'],
    ],
);

// =====================================================
// 2. CONSULTAS DE DATOS (APIs de solo lectura - Admin)
// =====================================================
Router::group(
    '/admin/users',
    function (): void {
        // description: Obtiene una lista paginada de usuarios.
        // query-param name:page type:int description:Número de página (desde 1)
        // query-param name:per_page type:int description:Filas por página (máximo 5000)
        // query-param name:filter type:string description:Texto a buscar
        // query-param name:order type:string description:Columna y dirección, por ejemplo nombre,asc
        // query-param name:externalFilters type:string description:Filtros permitidos: status=true o status=false
        Router::get('/getUsersPaginated', [UsersController::class, 'getUsersPaginated']);

        // description: Lo mismo con los filtros en el cuerpo (método QUERY): no quedan en la URL ni en los logs.
        // request-body name:page type:int description:Número de página (desde 1)
        // request-body name:per_page type:int description:Filas por página (máximo 5000)
        // request-body name:filter type:string description:Texto a buscar
        Router::query('/getUsersPaginated', [UsersController::class, 'getUsersPaginated']);
    },
    [
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlyAdmin'],
    ],
);

// =====================================================
// 3. OPERACIONES BÁSICAS (Escritura + CSRF - Admin)
// =====================================================
Router::group(
    '/admin/users',
    function (): void {
        // description: Elimina un usuario.
        // request-body name:tokenid type:string description:Token único del usuario
        Router::delete('/deleteUser', [UsersController::class, 'deleteUser']);
    },
    [
        [AuthMiddleware::class, 'validateCSRFToken'],
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlyAdmin'],
    ],
);

// =====================================================
// 4. OPERACIONES CRÍTICAS (Escritura + CSRF - Admin)
// =====================================================
Router::group(
    '/admin/users',
    function (): void {
        // description: Registra un nuevo usuario.
        // request-body name:name type:string description:Nombre del usuario
        // request-body name:email type:string description:Correo electrónico del usuario
        // request-body name:password type:string description:Contraseña del usuario
        Router::post('/addUser', [UsersController::class, 'registerUser'])->middleware([
            [UsersMiddleware::class, 'validateRegisterParams'],
        ]);

        // description: Edita un usuario existente.
        // request-body name:tokenid type:string description:Token único del usuario
        // request-body name:name type:string description:Nombre del usuario
        // request-body name:email type:string description:Correo electrónico del usuario
        // request-body name:password type:string description:Contraseña del usuario
        Router::put('/editUser', [UsersController::class, 'editUser'])->middleware([
            [UsersMiddleware::class, 'validateEditParams'],
        ]);

        // description: Cambia la contraseña de un usuario.
        // request-body name:tokenid type:string description:Token único del usuario
        // request-body name:new_password type:string description:Nueva contraseña del usuario
        Router::patch('/changePassword', [UsersController::class, 'changePassword'])->middleware([
            [UsersMiddleware::class, 'validateChangePasswordParams'],
        ]);
    },
    [
        [AuthMiddleware::class, 'validateCSRFToken'],
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlyAdmin'],
    ],
);
