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

    public function save(array $params): int
    {
        $perfil = R::dispense(self::$table);
        $perfil->nombre = $params['nombre'];
        $perfil->estado = $params['estado'];
        return R::store($perfil);
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
