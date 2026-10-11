<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\helpers\Functions;

class PerfilMiddleware
{
    public function validateParamsPerfil(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'nombre' => 'required',
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

    public function validateParamsChangeState(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
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

    public function validateParamsSavePermisos(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        // return [
        //     'params' => $params,
        //     'params_data' => json_decode($params['data'] ?? [], true),
        // ];

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'nombre' => 'required',
            'pagina_inicio' => 'required',
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
