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
        $params['icono'] = Functions::cleanString(Functions::text($params['icono'] ?? null));

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

    public function validateChangeStatusParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'estado' => 'required',
        ]);

        // Un middleware detiene la petición devolviendo un arreglo: con false seguiría de largo
        return (
            $result === []
                ? true
                : [
                    'success' => 0,
                    'message' => 'Los datos enviados no son correctos',
                    'errors' => $result,
                    'code' => 422,
                ]
        );
    }

    public function validateMovePositionParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'direction' => 'required',
        ]);

        // Un middleware detiene la petición devolviendo un arreglo: con false seguiría de largo
        return (
            $result === []
                ? true
                : [
                    'success' => 0,
                    'message' => 'Los datos enviados no son correctos',
                    'errors' => $result,
                    'code' => 422,
                ]
        );
    }

    // submodules
    public function validateChangeStatusSubModulesParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'id_menu' => 'required',
            'estado' => 'required',
        ]);

        // Un middleware detiene la petición devolviendo un arreglo: con false seguiría de largo
        return (
            $result === []
                ? true
                : [
                    'success' => 0,
                    'message' => 'Los datos enviados no son correctos',
                    'errors' => $result,
                    'code' => 422,
                ]
        );
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
