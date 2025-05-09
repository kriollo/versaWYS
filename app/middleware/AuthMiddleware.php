<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\GlobalMiddleWare;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Response;

/**
 * Class AuthMiddleware
 *
 * This class represents a middleware for authentication.
 */
class AuthMiddleware extends GlobalMiddleWare
{
    /**
     * Validates the parameters for the login request.
     *
     * @return true|array Returns an array with error details if the parameters are invalid, or null if the parameters are valid.
     */
    public function validateParamsLogin(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
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

    /**
     * Redirects the user to the dashboard if a session is active.
     *
     * @return void
     */
    public function redirectIfSession(): void
    {
        global $session;

        if ($session->checkUserSession()) {
            Response::redirect('/admin/dashboard');
            exit();
        }
    }

    /**
     * Checks if the user session is valid and redirects to the login page if not.
     */
    public function protectedRoute(): void
    {
        global $session;

        if (!$session->checkUserSession()) {
            Response::redirect('/admin/login');
            exit();
        }
    }

    /**
     * Validates the number of login attempts and returns an error message if the maximum number of attempts has been exceeded.
     *
     * @return array|null An array with the error message and code if the maximum number of attempts has been exceeded, or null if the validation passes.
     */
    public function validateAttemps(): true|array
    {
        global $session, $request;

        $attemps = $session->setAttempts();

        $attemps = $session->setAttemptsSession($attemps, $request->get('email'));

        if (!$session->maximumAttempts($attemps, $request->get('email'))) {
            $attemps = $session->getAttempts($request->get('email'));

            $timestampFuturo = $attemps['time'];
            $tiempoActual = time();
            $diferenciaEnSegundos = $timestampFuturo - $tiempoActual;
            $diferenciaEnMinutos = gmdate('H:i:s', $diferenciaEnSegundos);

            return [
                'success' => 0,
                'message' => 'Ha superado el número máximo de intentos',
                'errors' => [
                    'error' => 'Vuelva a intentarlo en un momento...',
                    'attemps' => "intentos: {$attemps['attempts']}",
                    'time' => "tiempo de espera $diferenciaEnMinutos",
                ],
                'code' => 401,
            ];
        }
        return true;
    }

    /**
     * Check if the email parameter is present and valid in the request.
     *
     * @return array|null Returns an array with error details if the email parameter is missing or invalid, or null if the email parameter is present and valid.
     */
    public function checkMail(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'email' => 'required|email',
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

    /**
     * Validates the parameters for applying a password reset.
     *
     * @return array|null Returns an array with error details if the parameters are invalid, or null if the parameters are valid.
     */
    public function validateParamsApplyResetPassword(): true|array
    {
        global $request, $config;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'new_password' => 'required',
            'comfirm_new_password' => 'required',
            'tokenReset' => 'required',
            'email' => 'required|email',
        ]);

        if ($params['new_password'] !== $params['comfirm_new_password']) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => [
                    'error' => 'Las contraseñas no coinciden',
                ],
                'code' => 401,
            ];
        }

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


}
