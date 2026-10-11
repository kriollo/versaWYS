<?php

declare(strict_types=1);

namespace app\models;

use Override;
use versaWYS\kernel\model\VersaORMModel;

class MediaStore extends VersaORMModel
{
    protected string $table = 'media_store';

    #[Override]
    public static function definePropertyTypes(): array
    {
        return [
            'id' => ['type' => 'int', 'nullable' => false, 'auto_increment' => true],
            'empresa_id' => ['type' => 'int', 'nullable' => false],
            'token_empresa' => ['type' => 'string', 'nullable' => false],
            'media_type' => [
                'type' => 'enum',
                'values' => ['image', 'video', 'audio', 'file'],
                'default' => 'file',
            ],
            'original_name' => ['type' => 'string', 'nullable' => false],
            'stored_name' => ['type' => 'string', 'nullable' => false],
            'relative_path' => ['type' => 'string', 'nullable' => false],
            'public_url' => ['type' => 'string', 'nullable' => false],
            'mime' => ['type' => 'string', 'nullable' => false],
            'size_bytes' => ['type' => 'int', 'nullable' => false],
            'status' => [
                'type' => 'enum',
                'values' => ['active', 'deleted'],
                'default' => 'active',
            ],
            'created_by_user_id' => ['type' => 'int', 'nullable' => false],
            'updated_by_user_id' => ['type' => 'int', 'nullable' => true],
            'created_at' => ['type' => 'datetime', 'nullable' => false],
            'updated_at' => ['type' => 'datetime', 'nullable' => true],
        ];
    }

    public function getMediaPaginate(
        int $empresaId,
        string $mediaType,
        int $limit,
        int $offset,
        ?int $folderId = -1,
        string $search = '',
    ): array {
        $filter = 'cms.empresa_id = ? AND cms.status = ?';
        $params = [$empresaId, 'active'];

        if ($mediaType !== '') {
            $filter .= ' AND cms.media_type = ?';
            $params[] = $mediaType;
        }

        // -1 = sin filtro de carpeta (todos los archivos)
        // null = solo archivos en raíz (folder_id IS NULL)
        // int > 0 = archivos de carpeta específica
        if ($folderId === null) {
            $filter .= ' AND cms.folder_id IS NULL';
        } elseif ($folderId > 0) {
            $filter .= ' AND cms.folder_id = ?';
            $params[] = $folderId;
        }

        if (trim($search) !== '') {
            $filter .= ' AND cms.original_name LIKE ?';
            $params[] = '%' . trim($search) . '%';
        }

        $result = $this
            ->qb()
            ->lazy()
            ->select([
                'cms.id',
                'cms.media_type',
                'cms.original_name',
                'cms.public_url',
                'cms.mime',
                'cms.size_bytes',
                'cms.created_at',
                'vu.name as created_by',
            ])
            ->selectRaw('COUNT(*) OVER() AS total_count')
            ->from('media_store as cms')
            ->leftJoin('versa_users as vu', 'vu.id', '=', 'cms.created_by_user_id')
            ->whereRaw($filter, $params)
            ->orderByRaw('cms.id DESC')
            ->limit($limit)
            ->offset($offset)
            ->collect();

        return [
            'total' => isset($result[0]['total_count']) ? (int) $result[0]['total_count'] : 0,
            'data' => $result,
        ];
    }

    public function createMedia(array $params): int
    {
        $media = $this->dispense($this->table);
        $media->empresa_id = (int) $params['empresa_id'];
        $media->token_empresa = (string) $params['token_empresa'];
        $media->media_type = (string) $params['media_type'];
        $media->original_name = (string) $params['original_name'];
        $media->stored_name = (string) $params['stored_name'];
        $media->relative_path = (string) $params['relative_path'];
        $media->public_url = (string) $params['public_url'];
        $media->mime = (string) $params['mime'];
        $media->size_bytes = (int) $params['size_bytes'];
        $media->status = 'active';
        $media->created_by_user_id = (int) $params['created_by_user_id'];
        $media->updated_by_user_id = (int) $params['updated_by_user_id'];
        $media->folder_id = isset($params['folder_id']) && $params['folder_id'] > 0 ? (int) $params['folder_id'] : null;

        return (int) $media->save();
    }

    public function moveToFolder(int $mediaId, int $empresaId, ?int $folderId): bool
    {
        $media = $this->getMediaByIdAndEmpresa($mediaId, $empresaId);
        if (!$media) {
            return false;
        }

        $record = $this->load($this->table, $mediaId);
        $record->folder_id = $folderId > 0 ? $folderId : null;
        $record->save();

        return true;
    }

    public function getMediaByIdAndEmpresa(int $mediaId, int $empresaId): ?array
    {
        $result = $this
            ->qb()
            ->select([
                'id',
                'empresa_id',
                'token_empresa',
                'media_type',
                'original_name',
                'stored_name',
                'relative_path',
                'public_url',
                'mime',
                'size_bytes',
                'status',
            ])
            ->from('media_store')
            ->whereRaw('id = ? AND empresa_id = ?', [$mediaId, $empresaId])
            ->findOne();

        if (!is_array($result) && !method_exists($result, 'export')) {
            return null;
        }

        $media = is_array($result) ? $result : $result->export();

        return is_array($media) ? $media : null;
    }

    public function softDeleteMedia(int $mediaId, int $empresaId, int $userId): bool
    {
        $media = $this->getMediaByIdAndEmpresa($mediaId, $empresaId);
        if (!$media) {
            return false;
        }

        $record = $this->load($this->table, $mediaId);
        $record->status = 'deleted';
        $record->updated_by_user_id = $userId;
        $record->save();

        return true;
    }

    public function updateMediaName(int $mediaId, int $empresaId, string $newName, int $userId): bool
    {
        $media = $this->getMediaByIdAndEmpresa($mediaId, $empresaId);
        if (!$media) {
            return false;
        }

        $record = $this->load($this->table, $mediaId);
        $record->original_name = $newName;
        $record->updated_by_user_id = $userId;
        $record->save();

        return true;
    }
}
