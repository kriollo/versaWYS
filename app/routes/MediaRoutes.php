<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\MediaController;
use app\middleware\AuthMiddleware;
use app\middleware\EmpresasMiddleware;
use app\middleware\MediaMiddleware;
use versaWYS\kernel\Router;

Router::group(
    '/admin/mediaStore',
    function (): void {
        Router::get('', [MediaController::class, 'indexMediaStore'])->middleware([]);

        Router::group('/api', function (): void {
            Router::get('/list/{tokenEmpresa}', [MediaController::class, 'getMediaByEmpresa'])->middleware([
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            Router::post('/upload/{tokenEmpresa}', [MediaController::class, 'uploadMedia'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
                [MediaMiddleware::class,    'validateUploadMedia'],
            ]);

            Router::post('/delete/{tokenEmpresa}', [MediaController::class, 'deleteMedia'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
                [MediaMiddleware::class,    'validateDeleteMedia'],
            ]);

            Router::post('/rename/{tokenEmpresa}', [MediaController::class, 'renameMedia'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
                [MediaMiddleware::class,    'validateRenameMedia'],
            ]);

            Router::post('/move/{tokenEmpresa}', [MediaController::class, 'moveMedia'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            // Rutas de carpetas
            Router::get('/folders/{tokenEmpresa}', [MediaController::class, 'getFolders'])->middleware([
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            Router::post('/folders/{tokenEmpresa}', [MediaController::class, 'createFolder'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            Router::post('/folders/rename/{tokenEmpresa}', [
                MediaController::class,
                'renameFolder',
            ])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            Router::post('/folders/delete/{tokenEmpresa}', [
                MediaController::class,
                'deleteFolder',
            ])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);
        });
    },
    [[AuthMiddleware::class, 'checkSession']],
);
