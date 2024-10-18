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
}
