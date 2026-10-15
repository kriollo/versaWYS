<?php

/**
 * FILEPATH: app/routes/DatabaseBackupRoutes.php.
 *
 * Rutas para exportar/importar un respaldo completo de la base de datos desde el
 * panel de Configuración (solo superadmin). Usa pg_dump/psql o mysqldump/mysql según el motor configurado.
 */

declare(strict_types=1);

namespace app\routes;

use app\controllers\DatabaseBackupController;
use app\middleware\AuthMiddleware;
use versaWYS\kernel\Router;

Router::group(
    '/admin/configuracion/database',
    function (): void {
        // description: Genera un dump completo de la base de datos y devuelve un token de descarga.
        Router::post('/export/prepare', [DatabaseBackupController::class, 'prepareExport'])->middleware([
            [AuthMiddleware::class, 'validateCSRFToken'],
        ]);

        // description: Descarga (y elimina del servidor) el dump generado previamente.
        // path-param name:token type:string description:Token temporal emitido al preparar la exportación
        Router::get('/export/{token}/download', [DatabaseBackupController::class, 'downloadExport']);

        // description: Restaura la base de datos a partir de un archivo .sql subido.
        // request-body name:dump type:file description:Dump SQL a restaurar
        Router::post('/import', [DatabaseBackupController::class, 'importDatabase'])->middleware([
            [AuthMiddleware::class, 'validateCSRFToken'],
        ]);

        // description: Consola SQL: ejecuta la consulta y devuelve hasta 500 filas. Queda registrada en el log.
        // request-body name:query type:string description:Sentencia SQL a ejecutar
        Router::post('/query', [DatabaseBackupController::class, 'runQuery'])->middleware([
            [AuthMiddleware::class, 'validateCSRFToken'],
        ]);
    },
    [
        [AuthMiddleware::class, 'checkSession'],
        [AuthMiddleware::class, 'onlySuperAdmin'],
    ],
);
