<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Twig\Environment;

class GlobalControllers
{
    public array $empresaSelected = [
        'id' => 0,
        'token_empresa' => '',
    ];

    /**
     * @var array
     *            This variable stores user information
     */
    protected array $user = [
        'name' => 'Guest',
        'email' => 'guest@localhost',
        'avatar' => 'default.png',
        'role' => 'guest',
        'empresa' => 'VersaWYS',
    ];

    protected array|int|null $menu_user = [];

    /**
     * @var int
     *          This variable stores user id
     */
    protected int $id_user = 0;

    protected array $empresas = [];

    protected $expiratePass = null;

    public function __construct(
        /**
         * Obtiene el objeto del template.
         */
        protected Environment $template,
        $session,
    ) {
        global $request, $cookie, $config;

        $userData = GlobalMiddleware::getLoadedUserData();
        if ($userData !== null) {
            $this->user = $userData['user'];
            $this->menu_user = $userData['menu_user'];
            $this->empresas = $userData['empresas'];
            $this->empresaSelected = $userData['empresaSelected'] ?? [];
            $this->id_user = (int) $userData['id_user'];
            $this->expiratePass = $userData['password_expired'];
        }

        // El resto de la lógica para pasar las variables a Twig permanece igual.
        $cookie->set(
            'expiratePass',
            $this->expiratePass ? 'true' : 'false',
            0,
            $config['session']['user_cookie']['domain'],
            $config['session']['user_cookie']['secure'],
            $config['session']['user_cookie']['http_only'],
        );

        $hasActiveSession = $session->get('id_user') !== null;

        $this->template->addGlobal('expiratePass', $this->expiratePass);
        $this->template->addGlobal('current_user', $this->user);
        $this->template->addGlobal('menu_user', $this->menu_user);
        $this->template->addGlobal('url_actual', $request->getUrl());
        $this->template->addGlobal('empresaSelected', $this->empresaSelected);
        $this->template->addGlobal('empresas', $this->empresas);
        $this->template->addGlobal('hasActiveSession', $hasActiveSession);
        $this->template->addGlobal('ws_url', $config['websocket_service']['ws_url'] ?? '');
        $this->template->addGlobal('config', $config);
    }

    /**
     * Retrieves the parameters for pagination.
     *
     * @param Request $http the HTTP request object
     * @param array $filedsToFilters an array of fields to filter
     *
     * @return array the pagination parameters
     */
    public function getParamsPaginate(
        Request $http,
        array $filedsToFilters,
        bool $inmediateExternalFilter = true,
        string $orderDefault = 'id,asc',
    ): array {
        $page = (int) ($http->get('page') ?? 1);
        $per_page = (float) ($http->get('per_page') ?? 15);
        $fitro = (string) ($http->get('filter') ?? '');
        $order = (string) ($http->get('order') ?? $orderDefault);
        $order = str_replace(',', ' ', $order);

        $externalFilters = (string) ($http->get('externalFilters') ?? '');

        // $externalFilters = $externalFilters;

        if ($page === 0 && !is_numeric($page)) {
            $page = 1;
        }

        if ($per_page === 0.0 && !is_numeric($per_page)) {
            $per_page = 15;
        }

        $page = $page > 1 ? (int) ($page - 1) * $per_page : 0;

        $limit = sprintf('LIMIT %s , %s', $page, $per_page);
        $limitNumber = (int) $per_page;
        $offset = (int) $page;

        $filter = '';
        if ($inmediateExternalFilter) {
            $filter = sprintf(' %s ', $externalFilters);
        }

        if (trim($fitro) !== '') {
            if (trim($filter) !== '') {
                $filter .= ' AND ';
            }

            $filter .= ' ( ';
            foreach ($filedsToFilters as $key => $value) {
                $filter .= match ($key) {
                    0 => sprintf(" %s LIKE '%%%s%%' ", $value, $fitro),
                    default => sprintf(" OR %s LIKE '%%%s%%' ", $value, $fitro),
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
            'offset' => $offset,
            'limitNumber' => $limitNumber,
        ];
    }
}
