<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models as models;
use versaWYS\kernel\helpers\Functions;

//TODO: implementar politica de contraseñas (tiempo de expiración)

class Globalcontrollers
{


    /**
     * @var array $user
     * This variable stores user information.
     */
    protected $user = [
        'name' => 'Guest',
        'email' => 'guest@localhost',
    ];

    protected $menu_user = [];


    /**
     * @var int $id
     * This variable stores user id.
     */
    protected $id_user = 0;

    /**
     * Obtiene el objeto del template
     *
     * @var \Twig\Environment
     */
    protected $template;

    public function __construct($twig, $session)
    {

        global $request;

        $this->template = $twig;
        if ($session->get('id_user') !== null) {
            $this->user = (new models\Users())->find($session->get('id_user'));
            $this->id_user = $session->get('id_user');

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
