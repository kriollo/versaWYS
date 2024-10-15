<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models as Models;
use RedBeanPHP\Cursor;
use Twig\Environment;
use versaWYS\kernel\helpers\Functions;

//TODO: implementar politica de contraseñas (tiempo de expiración)

class GlobalControllers
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
            $this->user = (new Models\Users())->find($session->get('id_user'));
            $this->id_user = (int) $session->get('id_user');

            if ($this->user['role'] == 'admin') {
                $this->menu_user = (new Models\Dashboard())->getMenuAdmin();
            } else {
                $this->menu_user = (new Models\Dashboard())->getMenuUser(
                    (int) $this->id_user,
                    (int) $this->user['id_perfil']
                );
            }
        }
        $twig->addGlobal('current_user', $this->user);
        $twig->addGlobal('menu_user', $this->menu_user);
        $twig->addGlobal('url_actual', $request->getUrl());
    }

    /**
     * Retrieves the parameters for pagination.
     *
     * @param Request $http The HTTP request object.
     * @param array $filedsToFilters An array of fields to filter.
     * @return array The pagination parameters.
     */
    public function getParamsPaginate(
        Request $http,
        array $filedsToFilters,
        bool $inmediateExternalFilter = true
    ): array {
        $page = (int) ($http->get('page') ?? 1);
        $per_page = (float) ($http->get('per_page') ?? 15);
        $fitro = (string) ($http->get('filter') ?? '');
        $order = (string) ($http->get('order') ?? 'id,asc');
        $order = str_replace(',', ' ', $order);

        $externalFilters = (string) ($http->get('externalFilters') ?? '');

        if ($externalFilters != '') {
            $externalFilters = "{$externalFilters}";
        } else {
            $externalFilters = '';
        }

        if ($page == '' && !is_numeric($page)) {
            $page = 1;
        }
        if ($per_page == '' && !is_numeric($per_page)) {
            $per_page = 15;
        }

        if ($page > 1) {
            $page = (int) ($page - 1) * $per_page;
        } else {
            $page = 0;
        }

        $limit = "LIMIT $page , $per_page";

        $filter = '';
        if ($inmediateExternalFilter) {
            $filter = " {$externalFilters} ";
        }
        if (trim($fitro) != '') {
            if (trim($filter) != '') {
                $filter .= ' AND ';
            }

            $filter .= ' ( ';
            foreach ($filedsToFilters as $key => $value) {
                if ($key == 0) {
                    $filter .= " $value LIKE '%$fitro%' ";
                } else {
                    $filter .= " OR $value LIKE '%$fitro%' ";
                }
            }
            $filter .= ' ) ';
        }

        return [
            'page' => $page,
            'per_page' => $per_page,
            'filtro' => $fitro,
            'order' => $order,
            'externalFilters' => $externalFilters,
            'limit' => $limit,
            'filter' => $filter,
            'allParams' => $http->getAllParams(),
        ];
    }
}
