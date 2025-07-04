<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models as Models;
use RedBeanPHP\Cursor;
use Twig\Environment;

class GlobalControllers
{
    /**
     * @var array $user
     * This variable stores user information.
     */
    protected array $user = [
        'name' => 'Guest',
        'email' => 'guest@localhost',
        'avatar' => 'default.png',
        'role' => 'guest',
    ];

    protected array|int|null|Cursor $menu_user = [];

    /**
     * @var int $id
     * This variable stores user id.
     */
    protected int $id_user = 0;

    protected $expiratePass = null;

    /**
     * Obtiene el objeto del template
     *
     * @var Environment
     */
    protected Environment $template;

    public function __construct(Environment $twig, $session)
    {
        global $request, $cookie, $config;

        $this->template = $twig;
        if ($session->get('id_user') !== null) {
            $this->user = (new Models\Users())->find($session->get('id_user'));
            $this->id_user = (int) $session->get('id_user');
            $this->menu_user =
                $this->user['role'] == 'admin'
                ? (new Models\Dashboard())->getMenuAdmin()
                : (new Models\Dashboard())->getMenuUser((int) $this->id_user, (int) $this->user['id_perfil']);

            if (isset($config['auth']['inactive_account_days']) && $config['auth']['inactive_account_days'] > 0) {
                $inactive_account_days = $config['auth']['inactive_account_days'];
                (new Models\Users())->updateStatusInactiveAccount($inactive_account_days);
            }

            $this->expiratePass = $this->expiratePass($this->user['expiration_pass']);
            if ($request->getUrl() !== '/admin/perfiluser' && $this->expiratePass) {
                Response::redirect('perfiluser');
            }
        }
        $cookie->set(
            'expiratePass',
            $this->expiratePass ? 'true' : 'false',
            0,
            $config['session']['user_cookie']['domain'],
            false,
            false
        );
        $twig->addGlobal('expiratePass', $this->expiratePass);
        $twig->addGlobal('current_user', $this->user);
        $twig->addGlobal('menu_user', $this->menu_user);
        $twig->addGlobal('url_actual', $request->getUrl());
    }

    public function expiratePass($dateExpiration): bool
    {
        $expiration = strtotime($dateExpiration);
        $today = strtotime(date('Y-m-d H:i:s'));
        if ($expiration < $today) {
            return true;
        }
        return false;
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

        $externalFilters = ($externalFilters != '') ? "{$externalFilters}" : '';

        if ($page == '' && !is_numeric($page)) {
            $page = 1;
        }
        if ($per_page == '' && !is_numeric($per_page)) {
            $per_page = 15;
        }

        $page = ($page > 1) ? (int) ($page - 1) * $per_page : 0;

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
                $filter = match ($key) {
                    0 => " $value LIKE '%$fitro%' ",
                    default => " OR $value LIKE '%$fitro%' ",
                };
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
