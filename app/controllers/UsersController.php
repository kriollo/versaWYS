<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Perfil;
use app\models\Users;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use versaWYS\kernel\CacheUsers;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\NotFound;
use versaWYS\kernel\Response;

class UsersController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function index(): string
    {
        return $this->template->render('dashboard/loader', [
            'm' => '/dashboard/js/usuarios/dashUsers',
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function addUserTemplate(): string
    {
        return $this->template->render('dashboard/usuarios/addUser', [
            'perfiles' => (new Perfil())->all((int) $this->empresaSelected['id'], 1),
        ]);
    }

    /** El {id} de la ruta es el tokenid del usuario. Uno de otra empresa responde 404, como uno inexistente. */
    public function editUserTemplate(string $id): string
    {
        $user = $this->findManagedUser($id);
        if ($user === []) {
            throw new NotFound();
        }

        unset($user['password'], $user['restore_token']);
        $perfiles = (new Perfil())->all((int) $this->empresaSelected['id'], 1);

        return $this->template->render('dashboard/usuarios/editUser', [
            'user' => $user,
            'perfiles' => $perfiles,
        ]);
    }

    public function getUsersPaginated(): void
    {
        try {
            global $request;

            $params = $this->getParamsPaginate(
                $request,
                [
                    'name',
                    'versa_users.email',
                    'role',
                    'versa_users.pagina_inicio',
                    'vp.nombre',
                    'e.nombre',
                ],
                allowedFilters: ['status' => 'versa_users.status'],
            );
            $filter = $params['filter'] ?? '';
            $order = $params['order'] ? '' . $params['order'] : 'id DESC';
            $result = (new Users())->getUsersPaginate(
                (int) $this->empresaSelected['id'],
                [
                    'versa_users.id',
                    'name',
                    'versa_users.email',
                    'role',
                    'status',
                    'tokenid',
                    'versa_users.pagina_inicio',
                    'vp.nombre as perfil',
                    'e.nombre as empresa',
                ],
                $filter,
                $order,
                (int) $params['limitNumber'],
                (int) $params['offset'],
                $params['bindings'],
            );
            $count = (int) $result['total'];

            $total_pages = ceil($count / $params['per_page']);
            if ($total_pages === 1 || $total_pages === 0) {
                $total_pages = 1;
                if ($params['page'] >= 1) {
                    $params['page'] = 0;
                }
            }

            $columns = [
                ['field' => 'id', 'title' => 'ID', 'export' => true],
                ['field' => 'name', 'title' => 'Nombre', 'export' => true],
                ['field' => 'email', 'title' => 'Correo', 'export' => true],
                ['field' => 'perfil', 'title' => 'Perfil', 'export' => true],
                ['field' => 'pagina_inicio', 'title' => 'Página de inicio', 'export' => true],
                ['field' => 'role', 'title' => 'Rol', 'export' => true],
                ['field' => 'empresa', 'title' => 'Empresa', 'export' => true],
                ['field' => 'status', 'title' => 'Estado', 'type' => 'status', 'export' => true],
                [
                    'field' => 'actions',
                    'title' => 'Acciones',
                    'type' => 'actions',
                    'buttons' => [
                        [
                            'title' => 'Editar',
                            'icon' => 'bi bi-pencil-fill text-xl text-yellow-500',
                            'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                            'action' => 'editUser',
                            'condition' => 'status',
                            'condition_value' => '1',
                        ],
                        [
                            'title' => 'Cambiar contraseña',
                            'icon' => 'bi bi-key-fill text-xl text-blue-500',
                            'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                            'action' => 'changePassword',
                            'condition' => 'status',
                            'condition_value' => '1',
                        ],
                        [
                            'title' => 'Desactivar Usuario',
                            'icon' => 'bi bi-trash-fill text-xl text-red-500',
                            'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                            'action' => 'changeStatus',
                            'condition' => 'status',
                            'condition_value' => '1',
                        ],
                        [
                            'title' => 'Activar Usuario',
                            'icon' => 'bi bi-recycle text-xl text-green-500',
                            'class' => 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                            'action' => 'changeStatus',
                            'condition' => 'status',
                            'condition_value' => '0',
                        ],
                    ],
                ],
            ];

            Response::json([
                'success' => 1,
                'data' => $result['data'],
                'columns' => $columns,
                'meta' => [
                    'filter' => $params['filtro'],
                    'total' => $count,
                    'from' => $params['page'] + 1,
                    'to' => $params['page'] + count($result['data']),
                    'page' => $params['page'],
                    'total_pages' => (int) $total_pages,
                ],
            ], 200);
        } catch (Exception $exception) {
            Response::json([
                'success' => 0,
                'message' => 'Error al obtener los usuarios',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    private const array ROLES = ['user', 'admin', 'superadmin'];

    private const array AVATAR_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function registerUser(): void
    {
        global $request;

        $params = $request->getAllParams();

        $scope = $this->resolveRoleAndEmpresa($params);
        if ($scope === null) {
            $this->forbidden();

            return;
        }

        if ((new Users())->findUserByEmail($params['email']) !== []) {
            Response::json([
                'success' => 0,
                'message' => 'Error al registrar',
                'errors' => ['email' => 'El correo ya existe'],
            ], 422);

            return;
        }

        global $config;
        $expiration_days_password = $config['auth']['expiration_days_password'] ?? 30;
        $params['expiration_pass'] = date('Y-m-d', strtotime(sprintf('+%s days', $expiration_days_password)));
        $params['password'] = Functions::hash($params['password']);
        $params['role'] = $scope['role'];
        $params['empresa'] = $scope['empresa'];
        $params['status'] = ($params['status'] ?? null) !== null ? 0 : 1;

        if ((new Users())->createUser($params)) {
            Response::json(['success' => 1, 'message' => 'Usuario creado correctamente'], 200);

            return;
        }

        Response::json(['success' => 0, 'message' => 'No se pudo crear el usuario'], 500);
    }

    public function editUser(): void
    {
        global $request;

        $params = $request->getAllParams();

        $target = $this->findManagedUser((string) $params['tokenid']);
        $scope = $this->resolveRoleAndEmpresa($params);
        if ($target === [] || $scope === null) {
            $this->forbidden();

            return;
        }

        $find = (new Users())->findUserByEmail($params['email']);
        if ($find !== [] && $find['tokenid'] !== $params['tokenid']) {
            Response::json([
                'success' => 0,
                'message' => 'Error al registrar',
                'errors' => ['email' => 'El correo ya existe'],
            ], 422);

            return;
        }

        $params['role'] = $scope['role'];
        $params['empresa'] = $scope['empresa'];

        $result = (new Users())->updateUser($params);
        CacheUsers::invalidateUserCache((int) $target['id']);

        if ($result) {
            Response::json(['success' => 1, 'message' => 'Usuario actualizado correctamente'], 200);

            return;
        }

        Response::json(['success' => 0, 'message' => 'No se pudo actualizar el usuario'], 500);
    }

    /**
     * Activa o desactiva un usuario (no se eliminan registros).
     */
    public function deleteUser(): void
    {
        global $request;

        $target = $this->findManagedUser($request->string('tokenid'));
        if ($target === [] || (int) $target['id'] === $this->id_user) {
            $this->forbidden();

            return;
        }

        $result = (new Users())->delete($target['tokenid']);
        CacheUsers::invalidateUserCache((int) $target['id']);

        if ($result) {
            Response::json(['success' => 1, 'message' => 'Estado del usuario actualizado correctamente'], 200);

            return;
        }

        Response::json(['success' => 0, 'message' => 'No se pudo actualizar el usuario'], 500);
    }

    public function changePassword(): array
    {
        global $request;

        $params = $request->getAllParams();

        if ($this->findManagedUser((string) $params['tokenid']) === []) {
            return ['success' => 0, 'message' => 'No tiene permisos para modificar este usuario', 'code' => 403];
        }

        if ((new Users())->updatePasswordByTokenId($params)) {
            return ['success' => 1, 'message' => 'Contraseña actualizada correctamente'];
        }

        return ['success' => 0, 'message' => 'No se pudo actualizar la contraseña', 'code' => 500];
    }

    /**
     * El usuario en sesión cambia su propia contraseña (perfil o contraseña expirada).
     */
    public function resetPassByIdUser(): array
    {
        global $request, $cookie;

        // El id siempre es el de la sesión: nunca se confía en el que venga en la petición
        $params = ['id' => $this->id_user, 'password' => $request->get('password')];

        if ($this->id_user > 0 && (new Users())->updatePassworById($params)) {
            CacheUsers::invalidateUserCache($this->id_user);
            $cookie->set('expiratePass', 'false', time() + (60 * 60 * 24 * 30));

            return ['success' => 1, 'message' => 'Contraseña actualizada correctamente'];
        }

        return ['success' => 0, 'message' => 'No se pudo actualizar la contraseña', 'code' => 500];
    }

    /**
     * El usuario en sesión actualiza su propio avatar.
     */
    public function updateAvatarById(): void
    {
        global $request, $config;

        $fileAvatar = $request->file('fileAvatar');

        // Tipo real del contenido: ni la extensión ni el MIME enviados por el cliente son confiables
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($fileAvatar->getTmpName());
        $ext = self::AVATAR_TYPES[$mime] ?? null;
        if ($this->id_user <= 0 || $ext === null || $fileAvatar->getSize() > (2 * 1024 * 1024)) {
            Response::json([
                'success' => 0,
                'message' => 'El avatar debe ser una imagen JPEG, PNG o WebP de hasta 2MB',
            ], 422);

            return;
        }

        $dir = $config['assets']['dashboard']['avatars']['dist'];
        $avatars = glob(sprintf('%s/%d.*', $dir, $this->id_user));
        array_map(unlink(...), $avatars === false ? [] : $avatars);

        $avatar = $this->id_user . '.' . $ext;
        $pathAvatar = $dir . '/' . $avatar;
        $fileAvatar->moveToNewName($pathAvatar);

        if ((new Users())->updateAvatar(['id' => $this->id_user, 'avatar' => $avatar])) {
            CacheUsers::invalidateUserCache($this->id_user);
            Response::json(['success' => 1, 'message' => 'Avatar actualizado correctamente', 'path' => $pathAvatar]);

            return;
        }

        Response::json(['success' => 0, 'message' => 'No se pudo actualizar el avatar'], 500);
    }

    /**
     * Rol y empresa que el usuario en sesión puede asignar; null si no está autorizado.
     * Solo un superadmin crea superadmins o asigna usuarios a otra empresa.
     *
     * @return array{role: string, empresa: int}|null
     */
    private function resolveRoleAndEmpresa(array $params): ?array
    {
        $isSuperAdmin = ($this->user['role'] ?? '') === 'superadmin';
        // Sin convertir tipos: un rol que no es texto, o una empresa como "1e3" (que (int) vuelve 1000), se rechazan
        $role = $params['rol'] ?? 'user';
        $empresa = $params['empresa'] ?? null;
        $empresaValida = is_int($empresa) || is_string($empresa) && preg_match('/^\d+$/', $empresa) === 1;

        if (
            !is_string($role)
            || !in_array($role, self::ROLES, strict: true)
            || $role === 'superadmin' && !$isSuperAdmin
            || $isSuperAdmin && !$empresaValida
        ) {
            return null;
        }

        return [
            'role' => $role,
            'empresa' => $isSuperAdmin ? (int) $empresa : (int) $this->empresaSelected['id'],
        ];
    }

    /**
     * Usuario objetivo por tokenid, solo si el usuario en sesión puede gestionarlo:
     * misma empresa y nunca un superadmin, salvo que quien gestiona sea superadmin.
     */
    private function findManagedUser(string $tokenid): array
    {
        $target = $tokenid === '' ? [] : (new Users())->findWithCompany($tokenid, 'versa_users.tokenid');
        if ($target === [] || ($this->user['role'] ?? '') === 'superadmin') {
            return $target;
        }

        $sameEmpresa = (int) $target['id_empresa'] === (int) $this->empresaSelected['id'];

        return $sameEmpresa && $target['role'] !== 'superadmin' ? $target : [];
    }

    private function forbidden(): void
    {
        Response::json(['success' => 0, 'message' => 'No tiene permisos para realizar esta acción'], 403);
    }
}
