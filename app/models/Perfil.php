<?php

declare(strict_types=1);

namespace app\models;

use RedBeanPHP\R;
use RedBeanPHP\SimpleModel;
use versaWYS\kernel\RedBeanCnn;

class Perfil extends SimpleModel
{
    protected static string $table = 'versaperfil';

    /**
     * Get all perfil.
     *
     * Retrieves all the perfil from the database.
     *
     * @return array An array of user records.
     */
    public function all(): array
    {
        return R::getAll('SELECT * FROM versaperfil');
    }

    public function __construct()
    {
        (new RedBeanCnn())->setup();
    }

    public function __destruct()
    {
        R::close();
    }
}
