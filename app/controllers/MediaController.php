<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\MediaFolder;
use app\models\MediaStore;
use Exception;
use SensitiveParameter;
use versaWYS\kernel\File;
use versaWYS\kernel\GlobalControllers;
use ZipArchive;

class MediaController extends GlobalControllers
{
    private string $PathMedia = '';

    private MediaStore $mediaModel;

    public function __construct()
    {
        global $twig, $session, $config;
        parent::__construct($twig, $session);

        $this->mediaModel = new MediaStore();
        $this->PathMedia = (string) $config['assets']['dashboard']['store']['dist'];
    }

    public function indexMediaStore(): string
    {
        return $this->template->render('dashboard/loader', [
            'm' => 'dashboard/js/mediaStore/MediaStoreDashboard',
        ]);
    }

    public function getFolders(string $tokenEmpresa): array
    {
        try {
            global $request;

            $rawParent = $request->get('parent_folder_id', '');
            $parentFolderId = $rawParent === '' || $rawParent === null ? null : (int) $rawParent;

            $folders = MediaFolder::getFolders((int) $this->empresaSelected['id'], $parentFolderId);

            return [
                'success' => 1,
                'data' => $folders,
            ];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al obtener carpetas.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function createFolder(string $tokenEmpresa): array
    {
        try {
            global $request;

            $params = $request->getAllParams();
            $name = trim((string) ($params['name'] ?? ''));

            if ($name === '') {
                return ['success' => 0, 'message' => 'El nombre de la carpeta es requerido.', 'code' => 422];
            }

            $rawParent = $params['parent_folder_id'] ?? null;
            $parentId = $rawParent !== null && $rawParent !== '' ? (int) $rawParent : null;
            $empresaToken = (string) ($this->empresaSelected['token_empresa'] ?? $tokenEmpresa);

            $id = MediaFolder::createFolder(
                (int) $this->empresaSelected['id'],
                $empresaToken,
                $name,
                $parentId,
                (int) $this->user['id'],
            );

            if ($id === null) {
                return ['success' => 0, 'message' => 'No fue posible crear la carpeta.'];
            }

            return [
                'success' => 1,
                'message' => 'Carpeta creada correctamente.',
                'data' => ['id' => $id, 'name' => $name, 'parent_folder_id' => $parentId],
            ];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al crear carpeta.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function renameFolder(string $tokenEmpresa): array
    {
        try {
            global $request;

            $params = $request->getAllParams();
            $id = (int) ($params['id'] ?? 0);
            $newName = trim((string) ($params['new_name'] ?? ''));

            if ($id <= 0 || $newName === '') {
                return ['success' => 0, 'message' => 'ID o nombre inválido.', 'code' => 422];
            }

            $updated = MediaFolder::renameFolder($id, (int) $this->empresaSelected['id'], $newName);

            if (!$updated) {
                return [
                    'success' => 0,
                    'message' => 'La carpeta no existe o no pertenece a la empresa.',
                    'code' => 404,
                ];
            }

            return ['success' => 1, 'message' => 'Carpeta renombrada correctamente.'];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al renombrar carpeta.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function deleteFolder(string $tokenEmpresa): array
    {
        try {
            global $request;

            $params = $request->getAllParams();
            $id = (int) ($params['id'] ?? 0);

            if ($id <= 0) {
                return ['success' => 0, 'message' => 'ID de carpeta inválido.', 'code' => 422];
            }

            $deleted = MediaFolder::deleteFolder($id, (int) $this->empresaSelected['id']);

            if (!$deleted) {
                return [
                    'success' => 0,
                    'message' => 'La carpeta no existe, no pertenece a la empresa, o no está vacía.',
                    'code' => 409,
                ];
            }

            return ['success' => 1, 'message' => 'Carpeta eliminada correctamente.'];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al eliminar carpeta.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function moveMedia(string $tokenEmpresa): array
    {
        try {
            global $request;

            $params = $request->getAllParams();
            $id = (int) ($params['id'] ?? 0);

            if ($id <= 0) {
                return ['success' => 0, 'message' => 'ID de archivo inválido.', 'code' => 422];
            }

            $rawFolder = $params['folder_id'] ?? null;
            $folderId = $rawFolder !== null && $rawFolder !== '' ? (int) $rawFolder : null;

            $moved = $this->mediaModel->moveToFolder($id, (int) $this->empresaSelected['id'], $folderId);

            if (!$moved) {
                return [
                    'success' => 0,
                    'message' => 'El archivo no existe o no pertenece a la empresa.',
                    'code' => 404,
                ];
            }

            return ['success' => 1, 'message' => 'Archivo movido correctamente.'];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al mover archivo.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function getMediaByEmpresa(string $tokenEmpresa): array
    {
        try {
            global $request;

            $params = $this->getParamsPaginate($request, ['cms.original_name'], false, 'id,desc');
            $mediaType = trim((string) $request->get('media_type', ''));

            // folder_id: vacío = todos, 0/'root' = raíz, número = carpeta específica
            $rawFolder = $request->get('folder_id', '');
            $folderId = -1; // -1 = sin filtro
            if ($rawFolder !== '' && $rawFolder !== null) {
                $folderId = $rawFolder === 'root' ? null : (int) $rawFolder;
            }

            $result = $this->mediaModel->getMediaPaginate(
                (int) $this->empresaSelected['id'],
                $mediaType,
                (int) $params['limitNumber'],
                (int) $params['offset'],
                $folderId,
                trim((string) ($params['filtro'] ?? '')),
            );

            return [
                'success' => 1,
                'data' => $result['data'],
                'meta' => [
                    'total' => (int) $result['total'],
                    'page' => (int) ($request->get('page') ?? 1),
                    'per_page' => (int) $params['per_page'],
                ],
            ];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al obtener archivos multimedia.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function uploadMedia(string $tokenEmpresa): array
    {
        try {
            global $request;

            $params = $request->getAllParams();
            $mediaType = (string) ($params['media_type'] ?? 'file');
            $rawFolder = $params['folder_id'] ?? null;
            $folderId = $rawFolder !== null && $rawFolder !== '' && (int) $rawFolder > 0 ? (int) $rawFolder : null;
            $files = $request->getAllFiles('media');

            if ($files === []) {
                return [
                    'success' => 0,
                    'message' => 'No se recibieron archivos para subir.',
                    'code' => 400,
                ];
            }

            $results = [];
            $empresaToken = (string) ($this->empresaSelected['token_empresa'] ?? $tokenEmpresa);
            $storagePaths = $this->getStoragePaths($empresaToken);

            foreach ($files as $uploadedFile) {
                if (!$uploadedFile instanceof File) {
                    continue;
                }

                try {
                    $validation = $this->validateMediaFile($uploadedFile, $mediaType, true);
                    if (!$validation['valid']) {
                        $results[] = [
                            'success' => false,
                            'name' => $uploadedFile->getName(),
                            'message' => $validation['message'],
                        ];

                        continue;
                    }

                    if (($validation['is_zip'] ?? false) === true) {
                        $zipResults = $this->processZipUpload(
                            $uploadedFile,
                            $mediaType,
                            $empresaToken,
                            $folderId,
                            $storagePaths,
                        );

                        $results = [...$results, ...$zipResults];

                        continue;
                    }

                    $safeName = $this->buildStoredFilenameFromName($uploadedFile->getName());
                    $absoluteFilePath = $storagePaths['absolute_directory'] . DIRECTORY_SEPARATOR . $safeName;
                    $uploadedFile->moveToNewName($absoluteFilePath);

                    $relativeFilePath = $storagePaths['relative_directory'] . '/' . $safeName;
                    $publicUrl = '/' . ltrim(str_replace('\\', '/', $relativeFilePath), '/');

                    $mediaId = $this->mediaModel->createMedia([
                        'empresa_id' => (int) $this->empresaSelected['id'],
                        'token_empresa' => $empresaToken,
                        'media_type' => $mediaType,
                        'original_name' => $uploadedFile->getName(),
                        'stored_name' => $safeName,
                        'relative_path' => $relativeFilePath,
                        'public_url' => $publicUrl,
                        'mime' => (string) ($validation['real_mime'] ?? $uploadedFile->getType()),
                        'size_bytes' => $uploadedFile->getSize(),
                        'created_by_user_id' => (int) $this->user['id'],
                        'updated_by_user_id' => (int) $this->user['id'],
                        'folder_id' => $folderId,
                    ]);

                    $results[] = [
                        'success' => true,
                        'id' => $mediaId,
                        'name' => $uploadedFile->getName(),
                        'media_type' => $mediaType,
                        'url' => $publicUrl,
                        'mime' => (string) ($validation['real_mime'] ?? $uploadedFile->getType()),
                        'size_bytes' => $uploadedFile->getSize(),
                    ];
                } catch (Exception $exception) {
                    $results[] = [
                        'success' => false,
                        'name' => $uploadedFile->getName(),
                        'message' => 'No fue posible procesar el archivo: ' . $exception->getMessage(),
                    ];
                }
            }

            $successCount = count(array_filter(
                $results,
                static fn(array $item): bool => ($item['success'] ?? false) === true,
            ));
            $failedCount = count($results) - $successCount;

            if ($successCount === 0 && $failedCount > 0) {
                return [
                    'success' => 0,
                    'message' => 'No fue posible subir archivos con validación de seguridad.',
                    'data' => $results,
                ];
            }

            return [
                'success' => 1,
                'message' => $failedCount > 0
                    ? 'Proceso de subida completado con advertencias de validación.'
                    : 'Proceso de subida completado.',
                'data' => $results,
            ];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al subir archivos multimedia.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function deleteMedia(string $tokenEmpresa): array
    {
        try {
            global $request;

            $params = $request->getAllParams();
            $id = (int) ($params['id'] ?? 0);

            if ($id <= 0) {
                return [
                    'success' => 0,
                    'message' => 'ID de media inválido.',
                    'code' => 422,
                ];
            }

            $media = $this->mediaModel->getMediaByIdAndEmpresa($id, (int) $this->empresaSelected['id']);
            if ($media === null) {
                return [
                    'success' => 0,
                    'message' => 'El archivo no existe o no pertenece a la empresa.',
                    'code' => 404,
                ];
            }

            if (!$this->deleteMediaPhysicalFile($media)) {
                return [
                    'success' => 0,
                    'message' => 'No fue posible eliminar el archivo físico del servidor.',
                    'error' => $exception->getMessage(),
                    'code' => 500,
                ];
            }

            $deleted = $this->mediaModel->softDeleteMedia(
                $id,
                (int) $this->empresaSelected['id'],
                (int) $this->user['id'],
            );

            if (!$deleted) {
                return [
                    'success' => 0,
                    'message' => 'Se eliminó el archivo físico, pero no se pudo actualizar su estado en la base de datos.',
                    'error' => $exception->getMessage(),
                    'code' => 500,
                ];
            }

            return [
                'success' => 1,
                'message' => 'Archivo eliminado correctamente.',
            ];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al eliminar archivo multimedia.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    public function renameMedia(string $tokenEmpresa): array
    {
        try {
            global $request;

            $params = $request->getAllParams();
            $id = (int) ($params['id'] ?? 0);
            $newName = trim((string) ($params['new_name'] ?? ''));

            if ($id <= 0 || $newName === '') {
                return [
                    'success' => 0,
                    'message' => 'ID de media o nombre inválido.',
                    'code' => 422,
                ];
            }

            $updated = $this->mediaModel->updateMediaName(
                $id,
                (int) $this->empresaSelected['id'],
                $newName,
                (int) $this->user['id'],
            );

            if (!$updated) {
                return [
                    'success' => 0,
                    'message' => 'El archivo no existe o no pertenece a la empresa.',
                    'code' => 404,
                ];
            }

            return [
                'success' => 1,
                'message' => 'Archivo renombrado correctamente.',
            ];
        } catch (Exception $exception) {
            return [
                'success' => 0,
                'message' => 'Error al renombrar archivo multimedia.',
                'error' => $exception->getMessage(),
                'code' => 500,
            ];
        }
    }

    private function deleteMediaPhysicalFile(array $media): bool
    {
        $relativePath = trim((string) ($media['relative_path'] ?? ''));
        if ($relativePath === '') {
            return true;
        }

        $absolutePath =
            dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));

        if (!file_exists($absolutePath)) {
            return true;
        }

        if (!is_file($absolutePath)) {
            return false;
        }

        return unlink($absolutePath);
    }

    private function buildStoredFilenameFromName(string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $extension = $extension !== '' ? '.' . $extension : '';

        return '_media_' . time() . '_' . bin2hex(random_bytes(6)) . $extension;
    }

    /**
     * @return array{absolute_directory:string,relative_directory:string}
     */
    private function getStoragePaths(#[SensitiveParameter] string $empresaToken): array
    {
        $normalizedDist = trim(str_replace('\\', '/', $this->PathMedia), './');
        $rootPath = dirname(__DIR__, 2);
        $companyFolder = 'media/' . $empresaToken;
        $yearMonth = date('Y/m');
        $relativeDirectory = $normalizedDist . '/' . $companyFolder . '/' . $yearMonth;
        $absoluteDirectory =
            $rootPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);

        if (!is_dir($absoluteDirectory)) {
            mkdir($absoluteDirectory, 0o775, true);
        }

        return [
            'absolute_directory' => $absoluteDirectory,
            'relative_directory' => $relativeDirectory,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function processZipUpload(
        File $uploadedFile,
        string $mediaType,
        #[SensitiveParameter]
        string $empresaToken,
        ?int $folderId,
        array $storagePaths,
    ): array {
        $results = [];
        if (!class_exists('ZipArchive')) {
            return [
                [
                    'success' => false,
                    'name' => $uploadedFile->getName(),
                    'message' => 'El servidor no tiene soporte ZipArchive habilitado.',
                ],
            ];
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($uploadedFile->getTmpName());
        if ($openResult !== true) {
            return [
                [
                    'success' => false,
                    'name' => $uploadedFile->getName(),
                    'message' => 'No se pudo abrir el archivo ZIP.',
                ],
            ];
        }

        $maxTotalExtractedBytes = 250 * 1024 * 1024;
        $totalExtractedBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryStat = $zip->statIndex($index);
            $entryName = is_array($entryStat) ? (string) ($entryStat['name'] ?? '') : '';
            if ($entryName === '') {
                continue;
            }

            $normalizedName = str_replace('\\', '/', $entryName);
            if (str_ends_with($normalizedName, '/')) {
                continue;
            }

            if (
                str_starts_with($normalizedName, '/')
                || str_contains($normalizedName, '../')
                || str_contains($normalizedName, '..\\')
            ) {
                $results[] = [
                    'success' => false,
                    'name' => $entryName,
                    'message' => 'Entrada ZIP inválida por ruta insegura.',
                ];

                continue;
            }

            $uncompressedSize = (int) (is_array($entryStat) ? $entryStat['size'] ?? 0 : 0);
            $totalExtractedBytes += $uncompressedSize;
            if ($totalExtractedBytes > $maxTotalExtractedBytes) {
                $results[] = [
                    'success' => false,
                    'name' => $entryName,
                    'message' => 'ZIP rechazado: supera el límite total permitido (250MB).',
                ];

                continue;
            }

            $safeOriginalName = basename($normalizedName);
            if ($safeOriginalName === '' || $this->isBlockedExtension($safeOriginalName)) {
                $results[] = [
                    'success' => false,
                    'name' => $entryName,
                    'message' => 'Archivo bloqueado por extensión no permitida.',
                ];

                continue;
            }

            $entryStream = $zip->getStream($entryName);
            if ($entryStream === false) {
                $results[] = [
                    'success' => false,
                    'name' => $entryName,
                    'message' => 'No se pudo leer la entrada ZIP.',
                ];

                continue;
            }

            $tmpExtractPath = tempnam(sys_get_temp_dir(), 'mszip_');
            if ($tmpExtractPath === false) {
                fclose($entryStream);
                $results[] = [
                    'success' => false,
                    'name' => $entryName,
                    'message' => 'No se pudo crear un archivo temporal para extraer ZIP.',
                ];

                continue;
            }

            $tmpHandle = fopen($tmpExtractPath, 'wb');
            if ($tmpHandle === false) {
                fclose($entryStream);
                @unlink($tmpExtractPath);
                $results[] = [
                    'success' => false,
                    'name' => $entryName,
                    'message' => 'No se pudo escribir el archivo temporal extraído.',
                ];

                continue;
            }

            stream_copy_to_stream($entryStream, $tmpHandle);
            fclose($tmpHandle);
            fclose($entryStream);

            $validation = $this->validateExtractedFile($tmpExtractPath, $safeOriginalName, $mediaType);
            if (!$validation['valid']) {
                @unlink($tmpExtractPath);
                $results[] = [
                    'success' => false,
                    'name' => $safeOriginalName,
                    'message' => $validation['message'],
                ];

                continue;
            }

            $safeStoredName = $this->buildStoredFilenameFromName($safeOriginalName);
            $finalAbsolutePath = $storagePaths['absolute_directory'] . DIRECTORY_SEPARATOR . $safeStoredName;

            $moved = @rename($tmpExtractPath, $finalAbsolutePath);
            if (!$moved) {
                $moved = @copy($tmpExtractPath, $finalAbsolutePath);
                @unlink($tmpExtractPath);
            }

            if (!$moved) {
                $results[] = [
                    'success' => false,
                    'name' => $safeOriginalName,
                    'message' => 'No se pudo guardar el archivo extraído en destino final.',
                ];

                continue;
            }

            @chmod($finalAbsolutePath, 0o664);

            $relativeFilePath = $storagePaths['relative_directory'] . '/' . $safeStoredName;
            $publicUrl = '/' . ltrim(str_replace('\\', '/', $relativeFilePath), '/');
            $sizeBytes = (int) (filesize($finalAbsolutePath) ?: 0);

            $mediaId = $this->mediaModel->createMedia([
                'empresa_id' => (int) $this->empresaSelected['id'],
                'token_empresa' => $empresaToken,
                'media_type' => $mediaType,
                'original_name' => $safeOriginalName,
                'stored_name' => $safeStoredName,
                'relative_path' => $relativeFilePath,
                'public_url' => $publicUrl,
                'mime' => (string) ($validation['real_mime'] ?? ''),
                'size_bytes' => $sizeBytes,
                'created_by_user_id' => (int) $this->user['id'],
                'updated_by_user_id' => (int) $this->user['id'],
                'folder_id' => $folderId,
            ]);

            $results[] = [
                'success' => true,
                'id' => $mediaId,
                'name' => $safeOriginalName,
                'media_type' => $mediaType,
                'url' => $publicUrl,
                'mime' => (string) ($validation['real_mime'] ?? ''),
                'size_bytes' => $sizeBytes,
            ];
        }

        $zip->close();

        return $results;
    }

    private function validateMediaFile(File $uploadedFile, string $mediaType, bool $allowZip): array
    {
        $maxSizeBytes = 50 * 1024 * 1024;
        $realMime = $this->detectRealMime($uploadedFile->getTmpName());
        $extension = strtolower(pathinfo($uploadedFile->getName(), PATHINFO_EXTENSION));

        if ($this->isBlockedExtension($uploadedFile->getName())) {
            return [
                'valid' => false,
                'message' => 'Archivo bloqueado por extensión no permitida.',
            ];
        }

        if ($uploadedFile->getSize() > $maxSizeBytes) {
            return [
                'valid' => false,
                'message' => 'El archivo supera el máximo permitido de 50MB.',
            ];
        }

        $zipMimes = ['application/zip', 'application/x-zip-compressed'];
        $isZip = $extension === 'zip' || in_array($realMime, $zipMimes, true);
        if ($isZip) {
            if (!$allowZip) {
                return [
                    'valid' => false,
                    'message' => 'No se permite ZIP en este contexto.',
                ];
            }

            return [
                'valid' => true,
                'message' => 'OK',
                'real_mime' => $realMime,
                'is_zip' => true,
            ];
        }

        $allowedMimes = $this->getAllowedMimesByType($mediaType);
        $allowedExtensions = $this->getAllowedExtensionsByType($mediaType);

        if ($extension !== '' && $allowedExtensions !== [] && !in_array($extension, $allowedExtensions, true)) {
            return [
                'valid' => false,
                'message' => 'La extensión del archivo no corresponde al tipo de media seleccionado.',
            ];
        }

        if ($allowedMimes !== [] && !in_array($realMime, $allowedMimes, true)) {
            return [
                'valid' => false,
                'message' => 'El contenido real del archivo no coincide con el tipo de media permitido.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'OK',
            'real_mime' => $realMime,
            'is_zip' => false,
        ];
    }

    private function validateExtractedFile(string $filePath, string $originalName, string $mediaType): array
    {
        if ($this->isBlockedExtension($originalName)) {
            return [
                'valid' => false,
                'message' => 'Archivo bloqueado por extensión no permitida.',
            ];
        }

        $maxSizeBytes = 50 * 1024 * 1024;
        $sizeBytes = (int) (filesize($filePath) ?: 0);
        if ($sizeBytes > $maxSizeBytes) {
            return [
                'valid' => false,
                'message' => 'El archivo extraído supera el máximo permitido de 50MB.',
            ];
        }

        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = $this->getAllowedExtensionsByType($mediaType);
        if ($extension !== '' && $allowedExtensions !== [] && !in_array($extension, $allowedExtensions, true)) {
            return [
                'valid' => false,
                'message' => 'La extensión del archivo extraído no corresponde al tipo seleccionado.',
            ];
        }

        $realMime = $this->detectRealMime($filePath);
        $allowedMimes = $this->getAllowedMimesByType($mediaType);
        if ($allowedMimes !== [] && !in_array($realMime, $allowedMimes, true)) {
            return [
                'valid' => false,
                'message' => 'El contenido real del archivo extraído no es válido para el tipo seleccionado.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'OK',
            'real_mime' => $realMime,
        ];
    }

    /**
     * @return string[]
     */
    private function getAllowedMimesByType(string $mediaType): array
    {
        return match ($mediaType) {
            'image' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'],
            'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
            'audio' => ['audio/mpeg', 'audio/mp3', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/mp4'],
            'file' => [
                'application/pdf',
                'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            default => [],
        };
    }

    /**
     * @return string[]
     */
    private function getAllowedExtensionsByType(string $mediaType): array
    {
        return match ($mediaType) {
            'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'],
            'video' => ['mp4', 'webm', 'ogv', 'ogg', 'mov'],
            'audio' => ['mp3', 'ogg', 'wav', 'm4a'],
            'file' => ['pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
            default => [],
        };
    }

    private function detectRealMime(string $filePath): string
    {
        if ($filePath === '' || !file_exists($filePath)) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return '';
        }

        $mime = (string) finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return strtolower($mime);
    }

    private function isBlockedExtension(string $fileName): bool
    {
        $blockedExtensions = [
            'php',
            'phtml',
            'phar',
            'cgi',
            'pl',
            'py',
            'sh',
            'bash',
            'zsh',
            'exe',
            'dll',
            'bin',
            'cmd',
            'bat',
            'com',
            'js',
            'jsp',
            'asp',
            'aspx',
        ];

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($extension, $blockedExtensions, true);
    }
}
