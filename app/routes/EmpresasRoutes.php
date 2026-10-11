<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\EmpresasController;
use app\middleware\AuthMiddleware;
use app\middleware\EmpresasMiddleware;
use versaWYS\kernel\Router;

Router::group(
    '/admin/empresas',
    function (): void {
        // description: Muestra la lista de empresas en el panel de administración.
        Router::get('', [EmpresasController::class, 'index']);

        // description: Lista las todas las empresas registradas en la base
        Router::get('/api/list', [EmpresasController::class, 'getEmpresasPaginated']);

        // description: API para guardar los datos de una empresa.
        Router::post('/api/saveEmpresa', [EmpresasController::class, 'saveEmpresa'])->middleware([
            [AuthMiddleware::class,     'validateCSRFToken'],
            [EmpresasMiddleware::class, 'validateParamsEmpresa'],
        ]);

        // description: API para editar los datos de una empresa.
        Router::put('/api/editEmpresa', [EmpresasController::class, 'saveEmpresa'])->middleware([
            [AuthMiddleware::class,     'validateCSRFToken'],
            [EmpresasMiddleware::class, 'validateParamsEditEmpresa'],
        ]);

        // description: API para cambiar el estado de una empresa.
        Router::delete('/api/changeStatus', [EmpresasController::class, 'changeStatus'])->middleware([
            [AuthMiddleware::class,     'validateCSRFToken'],
            [EmpresasMiddleware::class, 'validateParamsChangeStatus'],
        ]);

        Router::get('/api/getModulesEmpresa', [EmpresasController::class, 'getModulesEmpresa']);
        Router::post('/api/saveModuloEmpresaAsociado', [
            EmpresasController::class,
            'saveModuloEmpresaAsociado',
        ])->middleware([
            [AuthMiddleware::class,     'validateCSRFToken'],
            [EmpresasMiddleware::class, 'validateParamsSaveModuloEmpresaAsociado'],
        ]);

        // ===================================
        // LOGO DE EMPRESA
        // ===================================

        // description: Sube y guarda el logo de una empresa
        Router::post('/api/uploadLogo', [EmpresasController::class, 'uploadLogo'])->middleware([
            [AuthMiddleware::class, 'validateCSRFToken'],
        ]);
    },
    [
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlySuperAdmin'],
    ],
);
