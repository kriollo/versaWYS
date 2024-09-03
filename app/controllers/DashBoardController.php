<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use InvalidArgumentException;
use Throwable;
use versaWYS\kernel\ContentType;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\MailSender;
use versaWYS\kernel\Response;

class DashBoardController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    public function index(): void
    {
        Response::redirect('/admin');
    }

    /**
     * Renders the login view for the dashboard.
     *
     * @return string The rendered login view.
     */
    public function login(): string
    {
        return $this->template->render('dashboard/login/login');
    }

    /**
     * Authenticates the user based on the provided email and password.
     *
     * @throws \Exception If the provided data is incorrect.
     *
     * @return void
     */
    public function autentication()
    {
        global $request, $session;

        try {
            $params = $request->getAllParams();

            $user = (new Models\Users())->findUserByEmail($params['email']);

            if (
                $user === [] ||
                !Functions::verifyHash($params['password'], $user['password']) ||
                $user['status'] === 0
            ) {
                throw new InvalidArgumentException('Los datos enviados no son correctos', 401);
            }

            if (isset($params['remember']) && $params['remember'] === 'on') {
                $session->set('remember', true);
            }

            $remember = $params['remember'] ?? 'off';

            $session->setUserSession($user, $remember);

            $attemps = $session->getAllAttempts();
            $session->restoreAttempts($attemps, $params['email']);

            $session->set('id_user', $user['id']);

            Response::json(
                [
                    'success' => 1,
                    'message' => 'Autenticación correcta',
                    'redirect' => '/admin/dashboard',
                ],
                200
            );
        } catch (Throwable $th) {
            Response::json(
                [
                    'success' => 0,
                    'message' => $th->getMessage(),
                    'errors' => [
                        'error' => 'Vuelva a intentarlo nuevamente...',
                    ],
                ],
                $th->getCode()
            );
        }
    }

    /**
     * Logout the user and redirect to the login page.
     *
     * @return void
     */
    public function logout()
    {
        global $session, $cookie;

        foreach ($session->getAll() as $key => $value) {
            $session->delete($key);
        }
        foreach ($cookie->getAll() as $key => $value) {
            $cookie->delete($key);
        }

        $session->destroy();
        $cookie->clear();

        Response::redirect('/admin/login');
    }

    /**
     * Renders the dashboard view.
     *
     * @return string The rendered dashboard view.
     */
    public function dashboard()
    {
        return $this->template->render('dashboard/dashboard');
    }

    /**
     * Renders the lost password view.
     *
     * @return string The rendered lost password view.
     */
    public function lostPassword()
    {
        return $this->template->render('dashboard/login/lost-password');
    }

    /**
     * Sends a lost password email to the user.
     *
     * @return void
     */
    public function sendLostPassword()
    {
        global $request, $config;

        try {
            $params = $request->getAllParams();

            $user = (new models\Users())->findUserByEmail($params['email']);

            if ($user === []) {
                throw new InvalidArgumentException('Los datos enviados no son correctos', 401);
            }

            $token = Functions::generateCSRFToken();

            (new models\Users())->saveTokenResetPass($user['email'], $token);
            $mail = new MailSender();
            if (
                $mail->send(
                    ['email' => $user['email'], 'name' => $user['name']],
                    'Recuperar contraseña',
                    'mail/layout',
                    ContentType::TWIG,
                    [
                        'url_logo' => $request->getBaseUrl() . '/admin',
                        'content' => 'Para recuperar su contraseña, haga clic en el siguiente enlace:',
                        'btnhref' => "{$request->getBaseUrl()}/admin/reset-password?token=$token",
                        'copyright' =>
                            '&copy; ' .
                            date('Y') .
                            ' <a href="' .
                            $request->getBaseUrl() .
                            '">' .
                            $config['build']['name'] .
                            '</a> - Todos los derechos reservados.',
                        'title' => "Recuperar contraseña - {$request->getBaseUrl()}",
                        'btnname' => 'Recuperar contraseña',
                    ],
                    [
                        [
                            'path' => Functions::getAssets('dashboard', 'img', '/favicon.png', false),
                            'id' => 'logo',
                            'mime' => 'image/png',
                        ],
                    ]
                )
            ) {
                Response::json([
                    'success' => 1,
                    'message' =>
                        'Se ha enviado un correo electrónico a su cuenta de correo electrónico para restablecer su contraseña.',
                ]);
                exit();
            }

            throw new InvalidArgumentException('No se pudo enviar el correo electrónico', 500);
        } catch (Throwable $th) {
            Response::json(
                [
                    'success' => 0,
                    'message' => $th->getMessage(),
                    'errors' => [
                        'error' => 'Vuelva a intentarlo nuevamente...',
                    ],
                ],
                $th->getCode()
            );
        }
    }

    /**
     * Renders Reset the password for a user page.
     *
     * @return string The rendered view for resetting the lost password.
     */
    public function resetPassword(): string
    {
        global $request;

        $token = $request->get('token');

        $user = (new models\Users())->findUserByToken($token);

        if ($user === []) {
            return $this->template->render('versaWYS/404');
        }

        return $this->template->render('dashboard/login/ResetLostPassword', [
            'token_reset' => $token,
        ]);
    }

    /**
     * Apply change password.
     *
     * This method is responsible for applying a change password request.
     * It retrieves the necessary parameters from the request, finds the user by the provided token,
     * updates the user's password, and returns a JSON response indicating the success or failure of the operation.
     *
     * @throws \Exception If the provided token is invalid.
     *
     * @return void
     */
    public function applyChangePassword(): void
    {
        global $request;

        try {
            $params = $request->getAllParams();

            $user = (new models\Users())->findUserByToken($params['tokenReset']);

            if ($user === []) {
                throw new InvalidArgumentException('Los datos enviados no son correctos', 401);
            }

            $password = Functions::hash($params['new_password']);

            (new models\Users())->updatePassword($user['email'], $password);

            Response::json([
                'success' => 1,
                'message' => 'Se ha cambiado la contraseña correctamente, se redirigirá al inicio de sesión...',
            ]);
        } catch (Throwable $th) {
            Response::json(
                [
                    'success' => 0,
                    'message' => $th->getMessage(),
                    'errors' => [
                        'error' => 'Vuelva a intentarlo nuevamente...',
                    ],
                ],
                $th->getCode()
            );
        }
    }

    public function perfilUser()
    {
        return $this->template->render('dashboard/usuarios/perfilUser', [
            'owner_user' => $this->user,
        ]);
    }
}
