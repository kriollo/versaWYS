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
        if (count($result) > 0) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 401,
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
        if (count($result) > 0) {
            return false;
        }

        return true;
    }

    public function validateMovePositionParams(): bool
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'accion' => 'required',
        ]);
        if (count($result) > 0) {
            return false;
        }

        return true;
    }

    //submodules
    public function validateChangeStatusSubModulesParams(): bool
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'id_menu' => 'required',
            'estado' => 'required',
        ]);
        if (count($result) > 0) {
            return false;
        }

        return true;
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
        if (count($result) > 0) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 401,
            ];
        }

        return true;
    }
}
