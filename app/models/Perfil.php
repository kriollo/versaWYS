<?php

declare(strict_types=1);

namespace app\Models;

use RedBeanPHP\R;
use versaWYS\kernel\RedBeanCnn;

class Perfil extends \RedBeanPHP\SimpleModel
{
    protected static $table = 'versaperfil';

    /**
     * Get all perfil.
     *
     * Retrieves all the perfil from the database.
     *
     * @return array An array of user records.
     */
    public function all()
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