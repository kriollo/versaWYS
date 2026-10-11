<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\helpers\Functions;

class MediaMiddleware
{
    public function validateUploadMedia(string $tokenEmpresa): array|true
    {
        global $request;

        $params = $request->getAllParams();
        $result = Functions::validateParams($params, [
            'media_type' => 'required|string',
            'csrf_token' => 'required|string|min:32',
        ]);

        if ($result !== []) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos para subir archivos.',
                'errors' => $result,
                'code' => 422,
            ];
        }

        $validTypes = ['image', 'video', 'audio', 'file'];
        if (!in_array((string) $params['media_type'], $validTypes, true)) {
            return [
                'success' => 0,
                'message' => 'Tipo de media no permitido.',
                'code' => 422,
            ];
        }

        return true;
    }

    public function validateDeleteMedia(string $tokenEmpresa): array|true
    {
        global $request;

        $params = $request->getAllParams();
        $result = Functions::validateParams($params, [
            'id' => 'required|numeric|min:1',
            'csrf_token' => 'required|string|min:32',
        ]);

        if ($result !== []) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos para eliminar archivos.',
                'errors' => $result,
                'code' => 422,
            ];
        }

        return true;
    }

    public function validateRenameMedia(string $tokenEmpresa): array|true
    {
        global $request;

        $params = $request->getAllParams();
        $result = Functions::validateParams($params, [
            'id' => 'required|numeric',
            'new_name' => 'required|string|min:1',
            'csrf_token' => 'required|string|min:32',
        ]);

        if ($result !== []) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos para renombrar el archivo.',
                'errors' => $result,
                'code' => 422,
            ];
        }

        return true;
    }
}
