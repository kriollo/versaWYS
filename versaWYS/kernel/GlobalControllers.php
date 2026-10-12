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
            unset($this->user['password'], $this->user['restore_token']);
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
     * Parámetros de paginación, búsqueda y orden de un listado.
     *
     * Nada de lo que envía el cliente llega como SQL: `filter` devuelve un fragmento con
     * marcadores `?` y sus valores van en `bindings`, para usarse como whereRaw($filter, $bindings).
     *
     * @param array $filedsToFilters columnas sobre las que busca el texto libre (`filter`)
     * @param array $allowedFilters columnas que el cliente puede filtrar con `externalFilters`
     *                              (`col=valor AND col2>valor2`). Acepta `'col'` o `'nombreCliente' => 'tabla.col'`.
     *
     * @return array<string, mixed>
     */
    public function getParamsPaginate(
        Request $http,
        array $filedsToFilters,
        bool $inmediateExternalFilter = true,
        string $orderDefault = 'id,asc',
        array $allowedFilters = [],
    ): array {
        // ponytail: tope fijo de filas por página; exportaciones mayores necesitan un endpoint por streaming
        $per_page = min(max((int) $http->get('per_page', 15), 1), 5000);
        $page = max((int) $http->get('page', 1), 1);
        $offset = ($page - 1) * $per_page;

        $fitro = trim((string) $http->get('filter', ''));
        $externalFilters = (string) $http->get('externalFilters', '');

        $conditions = [];
        $bindings = [];

        if ($inmediateExternalFilter) {
            [$conditions, $bindings] = self::parseExternalFilters($externalFilters, $allowedFilters);
        }

        if ($fitro !== '' && $filedsToFilters !== []) {
            $likes = [];
            foreach ($filedsToFilters as $field) {
                $likes[] = $field . ' LIKE ?';
                $bindings[] = '%' . $fitro . '%';
            }
            $conditions[] = '(' . implode(' OR ', $likes) . ')';
        }

        return [
            'page' => $offset,
            'per_page' => $per_page,
            'filtro' => $fitro,
            'order' => self::sanitizeOrder((string) $http->get('order', $orderDefault), $orderDefault),
            'externalFilters' => $externalFilters,
            'limit' => sprintf('LIMIT %d , %d', $offset, $per_page),
            'filter' => implode(' AND ', $conditions),
            'bindings' => $bindings,
            'allParams' => $http->getAllParams(),
            'offset' => $offset,
            'limitNumber' => $per_page,
        ];
    }

    /**
     * Convierte `col=valor AND col2>=valor2` en condiciones con marcadores. Las condiciones con
     * formato inválido o sobre columnas no permitidas se descartan.
     *
     * @return array{0: list<string>, 1: list<mixed>}
     */
    private static function parseExternalFilters(string $raw, array $allowed): array
    {
        $columns = [];
        foreach ($allowed as $key => $column) {
            $columns[is_int($key) ? $column : $key] = $column;
        }

        $conditions = [];
        $bindings = [];

        foreach (preg_split('/\s+AND\s+/i', trim($raw)) ?: [] as $part) {
            if (preg_match('/^([A-Za-z_][\w.]*)\s*(=|!=|<>|>=|<=|>|<)\s*(.*)$/s', trim($part), $m) !== 1) {
                continue;
            }

            if (!isset($columns[$m[1]])) {
                continue;
            }

            $value = trim($m[3], " \t\"'");
            $conditions[] = $columns[$m[1]] . ' ' . $m[2] . ' ?';
            $bindings[] = match (true) {
                strtolower($value) === 'true' => true,
                strtolower($value) === 'false' => false,
                is_numeric($value) => $value + 0,
                default => $value,
            };
        }

        return [$conditions, $bindings];
    }

    /**
     * Solo `columna [asc|desc]`: la columna debe ser un identificador simple.
     */
    private static function sanitizeOrder(string $order, string $default): string
    {
        foreach ([$order, $default] as $candidate) {
            // ponytail: lista negra mínima para no ordenar por secretos; pasar a lista blanca por
            // listado si aparecen más columnas sensibles
            if (
                preg_match('/^([A-Za-z_][\w.]*)[,\s]*(asc|desc)?$/i', trim($candidate), $m) === 1
                && preg_match('/password|token/i', $m[1]) !== 1
            ) {
                return $m[1] . ' ' . strtoupper($m[2] ?? 'ASC');
            }
        }

        return 'id ASC';
    }
}
