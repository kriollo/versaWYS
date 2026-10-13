<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\InstallController;
use app\middleware\AuthMiddleware;
use app\middleware\InstallMiddleware;
use versaWYS\kernel\Router;

// description: Muestra el asistente de instalación (solo mientras el sitio no esté instalado).
Router::get('/install', [InstallController::class, 'index'])->middleware([
    [InstallMiddleware::class, 'checkNotInstalled'],
]);

// description: Ejecuta la instalación: guarda la conexión, migra, y crea la empresa y el superadministrador.
// request-body name:install_token type:string description:Token generado por versaCLI config:init
// request-body name:db_driver type:string description:Motor de base de datos (mysql o postgresql)
// request-body name:db_host type:string description:Host de la base de datos
// request-body name:db_port type:int description:Puerto de la base de datos
// request-body name:db_name type:string description:Nombre de la base de datos (debe existir y estar vacía)
// request-body name:db_user type:string description:Usuario de la base de datos
// request-body name:db_pass type:string description:Contraseña de la base de datos
// request-body name:empresa_nombre type:string description:Nombre de la empresa
// request-body name:empresa_rut type:string description:RUT de la empresa
// request-body name:admin_name type:string description:Nombre del superadministrador
// request-body name:admin_email type:string description:Correo del superadministrador
// request-body name:admin_password type:string description:Contraseña del superadministrador
// request-body name:admin_password_confirm type:string description:Repetición de la contraseña
Router::post('/install/run', [InstallController::class, 'run'])->middleware([
    [InstallMiddleware::class, 'checkNotInstalled'],
    [AuthMiddleware::class, 'validateCSRFToken'],
    [InstallMiddleware::class, 'checkInstallToken'],
    [InstallMiddleware::class, 'validateParams'],
]);
