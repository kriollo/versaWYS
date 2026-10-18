<?php

declare(strict_types=1);

namespace app\models;

use SensitiveParameter;
use VersaORM\VersaModel;
use versaWYS\kernel\model\VersaORMModel;

class MediaFolder extends VersaORMModel
{
    protected string $table = 'media_folders';

    /**
     * Lista carpetas activas de un nivel específico para una empresa.
     * Si $parentFolderId es null, retorna las carpetas raíz.
     */
    public static function getFolders(int $empresaId, ?int $parentFolderId): array
    {
        $instance = new self();

        $filter = 'empresa_id = ? AND parent_folder_id IS NULL AND status = ?';
        $params = [$empresaId, 'active'];
        if ($parentFolderId !== null) {
            $filter = 'empresa_id = ? AND parent_folder_id = ? AND status = ?';
            $params = [$empresaId, $parentFolderId, 'active'];
        }

        $result = $instance
            ->qb()
            ->select(['id', 'name', 'parent_folder_id'])
            ->from('media_folders')
            ->whereRaw($filter, $params)
            ->orderByRaw('name ASC')
            ->collect();

        return is_array($result) ? $result : [];
    }

    /**
     * Crea una nueva carpeta y retorna su ID, o null si falla.
     */
    public static function createFolder(
        int $empresaId,
        #[SensitiveParameter]
        string $token,
        string $name,
        ?int $parentId,
        int $userId,
    ): ?int {
        $instance = new self();
        $bean = $instance->dispense('media_folders');
        $bean->empresa_id = $empresaId;
        $bean->token_empresa = $token;
        $bean->name = $name;
        $bean->parent_folder_id = $parentId;
        $bean->status = 'active';
        $bean->created_by_user_id = $userId;

        $id = (int) $bean->store();

        return $id > 0 ? $id : null;
    }

    /** ¿La carpeta existe, está activa y es de la empresa? */
    public static function isOwnedBy(int $id, int $empresaId): bool
    {
        $record = (new self())->load('media_folders', $id);

        return $record !== null && (int) $record->empresa_id === $empresaId && $record->status !== 'deleted';
    }

    /**
     * Renombra una carpeta. Retorna true si fue encontrada y actualizada.
     */
    public static function renameFolder(int $id, int $empresaId, string $name): bool
    {
        $instance = new self();
        $record = $instance->load('media_folders', $id);

        if (!$record || (int) $record->empresa_id !== $empresaId || $record->status === 'deleted') {
            return false;
        }

        $record->name = $name;
        $record->save();

        return true;
    }

    /**
     * Elimina lógicamente una carpeta solo si está vacía
     * (sin archivos activos ni subcarpetas activas).
     */
    public static function deleteFolder(int $id, int $empresaId): bool
    {
        $instance = new self();
        $record = $instance->load('media_folders', $id);

        if (!$record || (int) $record->empresa_id !== $empresaId || $record->status === 'deleted') {
            return false;
        }

        // Verificar subcarpetas activas
        $subFolders = $instance
            ->qb()
            ->select(['id'])
            ->from('media_folders')
            ->whereRaw('parent_folder_id = ? AND empresa_id = ? AND status = ?', [$id, $empresaId, 'active'])
            ->limit(1)
            ->collect();

        if ($subFolders !== []) {
            return false;
        }

        // Verificar archivos activos en la carpeta
        $files = $instance
            ->qb()
            ->select(['id'])
            ->from('media_store')
            ->whereRaw('folder_id = ? AND empresa_id = ? AND status = ?', [$id, $empresaId, 'active'])
            ->limit(1)
            ->collect();

        if ($files !== []) {
            return false;
        }

        $record->status = 'deleted';
        $record->save();

        return true;
    }

    /**
     * Retorna el breadcrumb de una carpeta en formato [{id, name}, ...].
     * El primer elemento es la raíz (carpeta más alta) y el último es la carpeta indicada.
     */
    public static function getFolderPath(int $folderId, int $empresaId): array
    {
        $instance = new self();
        $path = [];
        $currentId = $folderId;

        // Máximo de 20 niveles para prevenir loops infinitos
        for ($i = 0; $i < 20; $i++) {
            $record = $instance
                ->qb()
                ->select(['id', 'name', 'parent_folder_id'])
                ->from('media_folders')
                ->whereRaw('id = ? AND empresa_id = ? AND status = ?', [$currentId, $empresaId, 'active'])
                ->findOne();

            if (!$record instanceof VersaModel) {
                break;
            }

            $data = is_array($record) ? $record : $record->export();
            array_unshift($path, ['id' => (int) $data['id'], 'name' => (string) $data['name']]);

            if ((int) ($data['parent_folder_id'] ?? 0) === 0) {
                break;
            }

            $currentId = (int) $data['parent_folder_id'];
        }

        return $path;
    }
}
