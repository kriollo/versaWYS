<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\helpers\Functions;

class UsersMiddleware
{

    public function validateRegisterParams()
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
                'code' => 401
            ];
        }
    }
    public function validateEditParams()
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
                'code' => 401
            ];
        }
    }
    public function validateChangePasswordParams()
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
                'code' => 401
            ];
        }
    }
}