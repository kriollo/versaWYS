<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\Response;
use versaWYS\kernel\helpers\Functions;

/**
 * Class AuthMiddleware
 *
 * This class represents a middleware for authentication.
 */
class AuthMiddleware
{
    /**
     * Check session and authentication for API calls and user sessions.
     *
     * @return void
     */
    public function checkSession()
    {
        global $session, $request, $config;


        if ($request->isApiCall()) {

            if (!$config['api']['auth'])
                return;

            if ($request->getHeader('Authorization') === null) {

                Response::json([
                    'success' => 0,
                    'message' => 'No se ha enviado el token de autenticación'
                ], 401);
                exit;
            }
        } else {
            if (!$session->checkUserSession()) {
                Response::redirect('/admin/login');
                exit;
            }
        }
    }

    /**
     * Validates the CSRF token in the request.
     *
     * @return array|null Returns an array with error details if the CSRF token is invalid or missing, or null if the token is valid.
     */
    public function validateCSRFToken()
    {
        global $session, $request;

        $token = $request->get('_csrf_token') ? $request->get('_csrf_token') : ($request->get('csrf_token') ? $request->get('csrf_token') : null);

        if ($token === null) {
            return [
                'success' => 0,
                'message' => 'los datos enviados no son correctos',
                'errors' => [
                    'error' => 'Algo no salió bien, Refresque y vuelva a intentarlo nuevamente...'
                ],
                'code' => 403
            ];
        }

        if (!Functions::validateCSRFToken($session->get('csrf_token'), $token)) {
            return [
                'success' => 0,
                'message' => 'los datos enviados no son correctos',
                'errors' => [
                    'error' => 'Algo no salió bien, Refresque y vuelva a intentarlo nuevamente...'
                ],
                'code' => 403
            ];
        }
    }

    /**
     * Validates the parameters for the login request.
     *
     * @return array|null Returns an array with error details if the parameters are invalid, or null if the parameters are valid.
     */
    public function validateParamsLogin()
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'email' => 'required|email',
            'password' => 'required|min:8'
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

    /**
     * Redirects the user to the dashboard if a session is active.
     *
     * @return void
     */
    public function redirectIfSession()
    {
        global $session;

        if ($session->checkUserSession()) {
            Response::redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Checks if the user session is valid and redirects to the login page if not.
     */
    public function protectedRoute()
    {
        global $session;

        if (!$session->checkUserSession()) {
            Response::redirect('/admin/login');
            exit;
        }
    }

    /**
     * Validates the number of login attempts and returns an error message if the maximum number of attempts has been exceeded.
     *
     * @return array|null An array with the error message and code if the maximum number of attempts has been exceeded, or null if the validation passes.
     */
    public function validateAttemps()
    {
        global $session, $request;

        $attemps = $session->setAttempts();

        $attemps = $session->setAttemptsSession($attemps, $request->get('email'));

        if (!$session->maximumAttempts($attemps, $request->get('email'))) {

            $attemps = $session->getAttempts($request->get('email'));

            $timestampFuturo = $attemps['time'];
            $tiempoActual = time();
            $diferenciaEnSegundos = $timestampFuturo - $tiempoActual;
            $diferenciaEnMinutos = gmdate("H:i:s", $diferenciaEnSegundos);

            return [
                'success' => 0,
                'message' => 'Ha superado el número máximo de intentos',
                'errors' => [
                    'error' => "Vuelva a intentarlo en un momento...",
                    'attemps' => "intentos: {$attemps['attempts']}",
                    'time' => "tiempo de espera {$diferenciaEnMinutos}"
                ],
                'code' => 401
            ];
        }
    }

    /**
     * Check if the email parameter is present and valid in the request.
     *
     * @return array|null Returns an array with error details if the email parameter is missing or invalid, or null if the email parameter is present and valid.
     */
    public function checkMail()
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'email' => 'required|email'
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

    /**
     * Validates the parameters for applying a password reset.
     *
     * @return array|null Returns an array with error details if the parameters are invalid, or null if the parameters are valid.
     */
    public function validateParamsApplyResetPassword()
    {
        global $request;

        $params = $request->getAllParams();

        $result = Functions::validateParams($params, [
            'new_password' => 'required|min:8',
            'comfirm_new_password' => 'required|min:8',
            'tokenReset' => 'required',
            'email' => 'required|email'
        ]);

        if (count($result) > 0) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $result,
                'code' => 401
            ];
        }

        if ($params['new_password'] !== $params['comfirm_new_password']) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => [
                    'error' => 'Las contraseñas no coinciden'
                ],
                'code' => 401
            ];
        }
    }

    public function onlyAdmin()
    {
        global $session;
        $iduser = $session->get('id_user');
        $user = (new \app\models\Users())->find($iduser);

        if ($user['role'] !== 'admin') {
            Response::redirect('/admin/dashboard');
            exit;
        }
    }
}
