<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\helpers\Functions;

class UsersMiddleware
{
    public function validateRegisterParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
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
    public function validateEditParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'tokenid' => 'required',
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
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
    public function validateChangePasswordParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'tokenid' => 'required',
            'new_password' => 'required|min:8',
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

    public function validateParamsResetPassByUser(): bool|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8',
        ]);
        if (count($result) > 0) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 401,
            ];
        }

        if ($params['password'] !== $params['password_confirmation']) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => [
                    'error' => 'Las contraseñas no coinciden',
                ],
                'code' => 401,
            ];
        }
        return true;
    }

    public function validateParamsUpdateAvatar(): bool|array
    {
        global $request;
        $params = $request->getAllParams();
        $fileAvatar = $request->file('fileAvatar');
        if (empty($fileAvatar)) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => [
                    'error' => 'No se ha enviado el archivo',
                ],
                'code' => 401,
            ];
        }
        $params['avatar'] = $fileAvatar->getName();

        $result = Functions::validateParams($params, [
            'id' => 'required',
            'avatar' => 'required',
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
