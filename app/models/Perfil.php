<?php

declare(strict_types=1);

namespace app\models;

use RedBeanPHP\R;
use versaWYS\kernel\RedBeanCnn;

class Perfil extends RedBeanCnn
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
        $this->connet();
    }

    public function __destruct()
    {
        $this->closeDB();
    }
}
