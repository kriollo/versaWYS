<?php

declare(strict_types=1);

namespace app\middleware;

use app\models\Empresas;
use versaWYS\kernel\helpers\Functions;

class EmpresasMiddleware
{
    public function validateParamsEmpresa(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $params['nombre'] = Functions::cleanString(Functions::text($params['nombre'] ?? null));
        // Un arreglo llega tal cual a la regla email, que lo rechaza (no como el texto "Array")
        $params['email'] = is_array($params['email'] ?? null)
            ? $params['email']
            : Functions::cleanString(Functions::text($params['email'] ?? null));

        $result = Functions::validateParams($params, [
            'action' => 'required',
            'nombre' => 'required',
            'rut' => 'rut',
            'email' => 'email',
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

    public function validateParamsEditEmpresa(): true|array
    {
        global $request;

        $params = $request->getAllParams();
        $params['nombre'] = Functions::cleanString(Functions::text($params['nombre'] ?? null));
        // Un arreglo llega tal cual a la regla email, que lo rechaza (no como el texto "Array")
        $params['email'] = is_array($params['email'] ?? null)
            ? $params['email']
            : Functions::cleanString(Functions::text($params['email'] ?? null));

        $result = Functions::validateParams($params, [
            'id' => 'required|integer',
            'action' => 'required',
            'nombre' => 'required',
            'rut' => 'rut',
            'email' => 'email',
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

    public function validateParamsChangeStatus(): true|array
    {
        global $request;

        $params = $request->getAllParams();
        $params['id'] = Functions::cleanString(Functions::text($params['id'] ?? null));

        $result = Functions::validateParams($params, [
            'id' => 'required|integer',
            'estado' => 'int:0-1',
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

    public function validateParamsSaveModuloEmpresaAsociado(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required|integer',
            'modulos' => 'required|array',
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

    public function validateSlugEmpresa(string $tokenEmpresa): array|true
    {
        global $request;
        $empresasModel = new Empresas();
        $empresa = $empresasModel->getEmpresaByToken($tokenEmpresa);

        if (!$empresa) {
            return [
                'success' => 0,
                'message' => 'Empresa no encontrada',
                'code' => 404,
            ];
        }

        $request->setParam('id_empresa', $empresa['id']);
        $request->setParam('token_empresa', (string) ($empresa['token_empresa'] ?? ''));

        return true;
    }
}
