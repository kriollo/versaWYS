<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\helpers\Functions;

class UsersMiddleware extends AuthMiddleware
{
    public function validateRegisterParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($this->validatePolicyPassword($params['password']) === false) {
            return [
                'success' => 0,
                'message' => 'La contraseña no cumple con las politicas de seguridad',
                'errors' => [
                    'error' => $this->getMessagePolicyPassword(),
                ],
                'code' => 401,
            ];
        }

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
            'password' => 'required',
        ]);

        if ($this->validatePolicyPassword($params['password']) === false) {
            return [
                'success' => 0,
                'message' => 'La contraseña no cumple con las politicas de seguridad',
                'errors' => [
                    'error' => $this->getMessagePolicyPassword(),
                ],
                'code' => 401,
            ];
        }

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
            'new_password' => 'required',
        ]);

        if ($this->validatePolicyPassword($params['new_password']) === false) {
            return [
                'success' => 0,
                'message' => 'La contraseña no cumple con las politicas de seguridad',
                'errors' => [
                    'error' => $this->getMessagePolicyPassword(),
                ],
                'code' => 401,
            ];
        }

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
            'password' => 'required',
            'password_confirmation' => 'required',
        ]);

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

        if (count($result) > 0) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 401,
            ];
        }

        if (
            $this->validatePolicyPassword($params['password']) === false ||
            $this->validatePolicyPassword($params['password_confirmation']) === false
        ) {
            return [
                'success' => 0,
                'message' => 'La contraseña no cumple con las politicas de seguridad',
                'errors' => [
                    'error' => $this->getMessagePolicyPassword(),
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
