<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models\Dashboard;
use app\models\Users;
use versaWYS\kernel\Response;

class GlobalMiddleWare
{
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
        if ($user === []) {
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
}
