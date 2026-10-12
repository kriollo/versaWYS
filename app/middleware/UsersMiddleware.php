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
            'empresa' => 'required',
            'name' => 'required|min:2',
            'email' => 'required|email',
            'password' => 'required',
            'id_perfil' => 'required|integer',
        ]);

        if (!$this->validatePolicyPassword($params['password'])) {
            return [
                'success' => 0,
                'message' => 'La contraseña no cumple con las politicas de seguridad',
                'errors' => [
                    'error' => $this->getMessagePolicyPassword(),
                ],
                'code' => 422,
            ];
        }

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

    public function validateEditParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'tokenid' => 'required',
            'empresa' => 'required',
            'name' => 'required|min:2',
            'email' => 'required|email',
            'id_perfil' => 'required|integer',
        ]);

        // Al editar, la contraseña es opcional: vacía significa conservar la actual
        if (($params['password'] ?? '') !== '' && !$this->validatePolicyPassword($params['password'])) {
            return [
                'success' => 0,
                'message' => 'La contraseña no cumple con las politicas de seguridad',
                'errors' => [
                    'error' => $this->getMessagePolicyPassword(),
                ],
                'code' => 422,
            ];
        }

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

    public function validateChangePasswordParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'tokenid' => 'required',
            'new_password' => 'required',
        ]);

        if (!$this->validatePolicyPassword($params['new_password'])) {
            return [
                'success' => 0,
                'message' => 'La contraseña no cumple con las politicas de seguridad',
                'errors' => [
                    'error' => $this->getMessagePolicyPassword(),
                ],
                'code' => 422,
            ];
        }

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

    public function validateParamsResetPassByUser(): bool|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'password' => 'required',
            'password_confirmation' => 'required',
        ]);

        if (!hash_equals((string) ($params['password'] ?? ''), (string) ($params['password_confirmation'] ?? ''))) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => [
                    'error' => 'Las contraseñas no coinciden',
                ],
                'code' => 422,
            ];
        }

        if ($result !== []) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 422,
            ];
        }

        if (
            !$this->validatePolicyPassword($params['password'])
            || !$this->validatePolicyPassword($params['password_confirmation'])
        ) {
            return [
                'success' => 0,
                'message' => 'La contraseña no cumple con las politicas de seguridad',
                'errors' => [
                    'error' => $this->getMessagePolicyPassword(),
                ],
                'code' => 422,
            ];
        }

        return true;
    }

    public function validateParamsUpdateAvatar(): bool|array
    {
        global $request;
        $params = $request->getAllParams();
        $fileAvatar = $request->file('fileAvatar');
        if ($fileAvatar === null) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => [
                    'error' => 'No se ha enviado el archivo',
                ],
                'code' => 422,
            ];
        }

        $params['avatar'] = $fileAvatar->getName();

        $result = Functions::validateParams($params, [
            'avatar' => 'required',
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
