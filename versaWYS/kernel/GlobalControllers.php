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
        $this->template = $twig;
        if ($session->get('id_user') !== null) {
            $this->user = (new models\Users())->find($session->get('id_user'));
            $this->id_user = $session->get('id_user');
        }
        $twig->addGlobal('current_user', $this->user);

    }
}