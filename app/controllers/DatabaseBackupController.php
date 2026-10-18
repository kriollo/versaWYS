<?php

declare(strict_types=1);

namespace app\controllers;

use app\services\DatabaseBackupService;
use app\services\DatabaseQueryService;
use Exception;
use SensitiveParameter;
use versaWYS\kernel\File;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\Loggin;
use versaWYS\kernel\Output;
use versaWYS\kernel\Response;

class DatabaseBackupController extends GlobalControllers
{
    private DatabaseBackupService $backupService;

    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);

        $this->backupService = new DatabaseBackupService();
    }

    public function prepareExport(): array
    {
        try {
            $result = $this->backupService->exportToFile();

            if (!$result['success']) {
                return [
                    'success' => 0,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                    'code' => 422,
                ];
            }

            return [
                'success' => 1,
                'data' => [
                    'token' => $result['token'],
                    'filename' => $result['filename'],
                    'size_bytes' => $result['size_bytes'],
                ],
            ];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al generar el respaldo de la base de datos.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function downloadExport(#[SensitiveParameter] string $token): void
    {
        $path = $this->backupService->getDumpPath($token);

        if ($path === null || !is_file($path)) {
            Response::json(['success' => 0, 'message' => 'Respaldo no encontrado o expirado.'], 404);

            return;
        }

        // Por Output: así responde igual en producción y en las pruebas (TestClient)
        Output::header('Content-Type: application/sql');
        Output::header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        Output::header('Content-Length: ' . filesize($path));
        Output::header('Cache-Control: private, no-cache, no-store, must-revalidate');
        Output::header('X-Content-Type-Options: nosniff');

        readfile($path);

        $this->backupService->deleteDump($token);
    }

    public function importDatabase(): array
    {
        try {
            global $request;

            $files = $request->getAllFiles('dump');
            $uploadedFile = $files[0] ?? null;

            if (!$uploadedFile instanceof File) {
                return ['success' => 0, 'message' => 'No se recibió ningún archivo.', 'code' => 400];
            }

            $result = $this->backupService->importFromFile($uploadedFile);

            if (!$result['success']) {
                return [
                    'success' => 0,
                    'message' => $result['message'],
                    'error' => $result['error'] ?? null,
                    'code' => 422,
                ];
            }

            return ['success' => 1, 'message' => $result['message']];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al restaurar la base de datos.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    /**
     * Consola SQL del panel: ejecuta la consulta tal cual (solo superadmin, con CSRF) y deja registro.
     */
    public function runQuery(): array
    {
        global $request;

        $sql = trim($request->string('query'));
        // Auditoría: quién ejecutó qué, aunque la consulta falle
        Loggin::warning('Consola SQL', 'database', [
            'id_user' => $this->id_user,
            'email' => $this->user['email'] ?? '',
            'query' => $sql,
        ]);

        $result = (new DatabaseQueryService())->run($sql);

        // El error de la base va como resultado y no como fallo: en producción el kernel oculta los mensajes
        // de error con SQL, y aquí quien pregunta es un superadmin que ya puede ejecutar cualquier consulta
        return [
            'success' => 1,
            'data' => $result['success'] ? $result : ['type' => 'error', 'message' => $result['message']],
        ];
    }
}
