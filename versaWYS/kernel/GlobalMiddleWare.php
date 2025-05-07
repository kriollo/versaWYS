<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models\Dashboard;
use app\models\Users;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Response;

class GlobalMiddleWare
{
    /**
     * Check session and authentication for API calls and user sessions.
     *
     * @return void
     */
    public function checkSession(): bool|array
    {
        global $session, $request, $config, $debug, $cookie;

        // Verificar si es una llamada API
        if ($request->isApiCall()) {
            // Si la autenticación de la API está deshabilitada o estamos en modo debug, permitir la solicitud
            if (!$config['api']['auth'] || $debug) {
                return true;
            }

            // Obtener el token de autenticación desde la cookie o el encabezado
            $tokenHash = $cookie->get('tknHash') ?? '';

            // obtener de $request->getHeader('Authorization') el token de autenticación y eliminar la palabra Bearer
            $authToken = $request->getHeader('Authorization') ?? '';
            $authToken = str_replace('Bearer ', '', $authToken);

            // Si no se envió ningún token, devolver un error
            if ($tokenHash === null && $authToken === null) {
                return [
                    'success' => 0,
                    'message' => 'No se ha enviado el token de autenticación',
                    'code' => 401,
                ];
            }

            // Verificar los tokens
            $isTokenValid = $session->checkTokenHash($tokenHash) || $session->checkTokenHash($authToken);
            return $isTokenValid
                ? true
                : [
                    'success' => 0,
                    'message' => 'Token de autenticación inválido',
                    'code' => 401,
                ];
        } else {
            // Verificar la sesión del usuario para solicitudes no API
            if (!$session->checkUserSession()) {
                Response::redirect('/admin/login');
            }
            return true;
        }
    }

    /**
     * Validates the CSRF token in the request.
     *
     * @return array|null Returns an array with error details if the CSRF token is invalid or missing, or null if the token is valid.
     */
    public function validateCSRFToken($slug = null): true|array
    {
        global $session, $request, $debug;

        if ($debug) {
            return true;
        }

        $token = $request->get('_csrf_token') ?: ($request->get('csrf_token') ?: ($slug ?: null));

        if ($token === null) {
            return [
                'success' => 0,
                'message' => 'los datos enviados no son correctos',
                'errors' => [
                    'error' => 'El formulario no es valido, refresque y vuelva a intentarlo nuevamente...',
                ],
                'code' => 403,
            ];
        }

        if (!Functions::validateCSRFToken($session->get('csrf_token'), $token)) {
            return [
                'success' => 0,
                'message' => 'los datos enviados no son correctos',
                'errors' => [
                    'error' => 'Algo no salió bien, Refresque y vuelva a intentarlo nuevamente...',
                ],
                'code' => 403,
            ];
        }
        return true;
    }

    public function onlyAdmin(): void
    {
        global $session;
        $iduser = (int) $session->get('id_user');
        $user = (new Users())->find($iduser);
        if ($user === []) {
            Response::redirect('/admin/login');
            exit();
        }

        if ($user['role'] !== 'admin') {
            Response::redirect('/admin/dashboard');
            exit();
        }
    }

    public function onlyUserWithAccess(): void
    {
        global $session, $request;

        $iduser = (int) $session->get('id_user');
        $user = (new Users())->find($iduser);
        if (!$user) {
            Response::redirect('/admin/login');
            exit();
        }

        if ($user['role'] === 'admin') {
            return;
        }

        $flat = true;
        $url = $request->getUrl();
        $menuUser = (new Dashboard())->getMenuUser($iduser);
        foreach ($menuUser as $value) {
            if ($value['url_menu'] === $url || $value['url_submenu'] === $url) {
                return;
            }
        }

        Response::redirect('/e404');
    }

    public function validatePolicyPassword($password): bool
    {
        global $config;

        //politicas de contraseñas por defecto
        $large = $config['auth']['password_policy']['large'] ?? 8;
        $uppercase = $config['auth']['password_policy']['uppercase'] ?? false;
        $lowercase = $config['auth']['password_policy']['lowercase'] ?? false;
        $number = $config['auth']['password_policy']['number'] ?? false;
        $specialChars = $config['auth']['password_policy']['special_chars'] ?? false;

        if (strlen($password) < $large) {
            return false;
        }

        if ($uppercase && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if ($lowercase && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        if ($number && !preg_match('/\d/', $password)) {
            return false;
        }

        if ($specialChars && !preg_match('/[^a-zA-Z\d]/', $password)) {
            return false;
        }

        return true;
    }

    public function getMessagePolicyPassword(): string
    {
        global $config;

        //politicas de contraseñas por defecto
        $large = $config['auth']['password_policy']['large'] ?? 8;
        $uppercase = $config['auth']['password_policy']['uppercase'] ?? false;
        $lowercase = $config['auth']['password_policy']['lowercase'] ?? false;
        $number = $config['auth']['password_policy']['number'] ?? false;
        $specialChars = $config['auth']['password_policy']['special_chars'] ?? false;

        $message = 'La contraseña debe tener al menos ' . $large . ' caracteres';

        if ($uppercase) {
            $message .= ', una letra mayúscula';
        }

        if ($lowercase) {
            $message .= ', una letra minúscula';
        }

        if ($number) {
            $message .= ', un número';
        }

        if ($specialChars) {
            $message .= ', un carácter especial';
        }

        return $message;
    }

    public function onlyDebug(): void
    {
        global $debug;
        if (!$debug) {
            Response::redirect('/admin/dashboard');
            exit();
        }
    }
}
