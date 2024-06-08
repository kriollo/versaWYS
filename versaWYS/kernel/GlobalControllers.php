<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models as models;
use RedBeanPHP\Cursor;
use Twig\Environment;
use versaWYS\kernel\helpers\Functions;

//TODO: implementar politica de contraseñas (tiempo de expiración)

class Globalcontrollers
{


    /**
     * @var array $user
     * This variable stores user information.
     */
    protected array $user = [
        'name' => 'Guest',
        'email' => 'guest@localhost',
    ];

    protected array|int|null|Cursor $menu_user = [];


    /**
     * @var int $id
     * This variable stores user id.
     */
    protected int $id_user = 0;

    /**
     * Obtiene el objeto del template
     *
     * @var Environment
     */
    protected Environment $template;

    public function __construct(Environment $twig, $session)
    {

        global $request;

        $this->template = $twig;
        if ($session->get('id_user') !== null) {
            $this->user = (new models\Users())->find($session->get('id_user'));
            $this->id_user = (int)$session->get('id_user');

            if ($this->user['role'] == 'admin') {
                $this->menu_user = (new models\Dashboard())->getMenuAdmin();
            } else {
            }
        }
        $twig->addGlobal('current_user', $this->user);
        $twig->addGlobal('menu_user', $this->menu_user);
        $twig->addGlobal('url_actual', $request->getUrl());
    }
}
