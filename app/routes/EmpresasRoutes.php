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

        // description: Obtiene una lista paginada de las empresas registradas.
        // query-param name:page type:int description:Número de página (desde 1)
        // query-param name:per_page type:int description:Filas por página (máximo 5000)
        // query-param name:filter type:string description:Texto a buscar
        // query-param name:order type:string description:Columna y dirección, por ejemplo nombre,asc
        // query-param name:externalFilters type:string description:Filtros permitidos: estado=true o estado=false
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

        // description: Lista los módulos del sistema indicando cuáles tiene asociados una empresa.
        // query-param name:idEmpresa type:int description:ID de la empresa
        Router::get('/api/getModulesEmpresa', [EmpresasController::class, 'getModulesEmpresa']);

        // description: Guarda los módulos asociados a una empresa.
        // request-body name:id type:int description:ID de la empresa
        // request-body name:modulos type:array description:IDs de los módulos que quedan asociados
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
