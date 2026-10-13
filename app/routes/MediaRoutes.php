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
        // description: Muestra el administrador de archivos multimedia de la empresa.
        Router::get('', [MediaController::class, 'indexMediaStore'])->middleware([]);

        Router::group('/api', function (): void {
            // description: Lista los archivos multimedia de una empresa.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa cuyos archivos se consultan
            // query-param name:media_type type:string description:Filtra por tipo de media (image, video, audio o file)
            // query-param name:folder_id type:int description:ID de carpeta, root para la raíz o vacío para todas las carpetas
            // query-param name:filter type:string description:Texto para filtrar por nombre original
            // query-param name:page type:int description:Número de página solicitado
            // query-param name:per_page type:int description:Cantidad de elementos por página
            Router::get('/list/{tokenEmpresa}', [MediaController::class, 'getMediaByEmpresa'])->middleware([
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            // description: Sube un archivo multimedia a una empresa.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa de destino
            // request-body name:media type:file[] description:Archivos multimedia que se subirán
            // request-body name:media_type type:string description:Tipo de media (image, video, audio o file)
            // request-body name:folder_id type:int description:ID opcional de la carpeta de destino
            Router::post('/upload/{tokenEmpresa}', [MediaController::class, 'uploadMedia'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
                [MediaMiddleware::class,    'validateUploadMedia'],
            ]);

            // description: Elimina un archivo multimedia de una empresa.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa propietaria del archivo
            // request-body name:id type:int description:ID del archivo multimedia que se eliminará
            Router::post('/delete/{tokenEmpresa}', [MediaController::class, 'deleteMedia'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
                [MediaMiddleware::class,    'validateDeleteMedia'],
            ]);

            // description: Cambia el nombre de un archivo multimedia.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa propietaria del archivo
            // request-body name:id type:int description:ID del archivo multimedia que se renombrará
            // request-body name:new_name type:string description:Nuevo nombre original del archivo
            Router::post('/rename/{tokenEmpresa}', [MediaController::class, 'renameMedia'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
                [MediaMiddleware::class,    'validateRenameMedia'],
            ]);

            // description: Mueve un archivo multimedia a otra carpeta.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa propietaria del archivo
            // request-body name:id type:int description:ID del archivo multimedia que se moverá
            // request-body name:folder_id type:int description:ID de carpeta de destino; vacío o nulo mueve a la raíz
            Router::post('/move/{tokenEmpresa}', [MediaController::class, 'moveMedia'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            // Rutas de carpetas
            // description: Lista las carpetas multimedia de una empresa.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa cuyas carpetas se consultan
            // query-param name:parent_folder_id type:int description:ID opcional de la carpeta padre
            Router::get('/folders/{tokenEmpresa}', [MediaController::class, 'getFolders'])->middleware([
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            // description: Crea una carpeta multimedia para una empresa.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa propietaria de la carpeta
            // request-body name:name type:string description:Nombre de la carpeta nueva
            // request-body name:parent_folder_id type:int description:ID opcional de la carpeta padre
            Router::post('/folders/{tokenEmpresa}', [MediaController::class, 'createFolder'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            // description: Cambia el nombre de una carpeta multimedia.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa propietaria de la carpeta
            // request-body name:id type:int description:ID de la carpeta que se renombrará
            // request-body name:new_name type:string description:Nuevo nombre de la carpeta
            Router::post('/folders/rename/{tokenEmpresa}', [MediaController::class, 'renameFolder'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);

            // description: Elimina una carpeta multimedia de una empresa.
            // path-param name:tokenEmpresa type:string description:Identificador de la empresa propietaria de la carpeta
            // request-body name:id type:int description:ID de la carpeta que se eliminará; debe estar vacía
            Router::post('/folders/delete/{tokenEmpresa}', [MediaController::class, 'deleteFolder'])->middleware([
                [AuthMiddleware::class,     'validateCSRFToken'],
                [EmpresasMiddleware::class, 'validateSlugEmpresa'],
            ]);
        });
    },
    [[AuthMiddleware::class, 'checkSession']],
);
