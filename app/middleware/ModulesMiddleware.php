<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\helpers\Functions;

class ModulesMiddleware
{
    public function validateSaveModuleParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();
        $params['icono'] = Functions::cleanString($params['icono']);

        $result = Functions::validateParams($params, [
            'seccion' => 'required',
            'nombre' => 'required',
            'descripcion' => 'required',
            'icono' => 'required',
            'estado' => 'required',
        ]);
        if ($result !== []) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 422,
            ];
        }

        return true;
    }

    public function validateChangeStatusParams(): bool
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'estado' => 'required',
        ]);

        return count($result) <= 0;
    }

    public function validateMovePositionParams(): bool
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'direction' => 'required',
        ]);

        return count($result) <= 0;
    }

    // submodules
    public function validateChangeStatusSubModulesParams(): bool
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'id_menu' => 'required',
            'estado' => 'required',
        ]);

        return count($result) <= 0;
    }

    public function validateSaveSubModuleParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id_menu' => 'required',
            'nombre' => 'required',
            'descripcion' => 'required',
            'url' => 'required',
            'estado' => 'required',
        ]);
        if ($result !== []) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 422,
            ];
        }

        return true;
    }

    public function validateMovePositionSubModuleParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'numeric',
            'id_menu' => 'numeric',
            'direction' => 'required',
        ]);
        if ($result !== []) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 422,
            ];
        }

        return true;
    }
}
