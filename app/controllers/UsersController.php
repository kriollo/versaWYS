<?php

declare(strict_types=1);

namespace app\controllers;

use app\models as Models;
use RedBeanPHP\RedException\SQL;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\helpers\Functions;
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
        // return $this->template->render('dashboard/usuarios/dashUsers', [
        //     'menu_op' => ['id_menu' => 0],
        // ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function addUserTemplate(): string
    {
        return $this->template->render('dashboard/usuarios/addUser', [
            'perfiles' => (new Models\Perfil())->all(1),
        ]);
    }
    public function editUserTemplate($slug = null): string
    {
        $user = (new models\Users())->find($slug, 'u.tokenid');
        $perfiles = (new models\Perfil())->all(1);
        return $this->template->render('dashboard/usuarios/editUser', [
            'user' => $user,
            'perfiles' => $perfiles,
        ]);
    }

    public function getUsersPaginated(): void
    {
        global $request;

        $params = $this->getParamsPaginate($request, [
            'name',
            'email',
            'role',
            'status',
            'vu.pagina_inicio',
            'vp.nombre',
            'e.nombre'
        ]);
        $filter = $params['filter'] ? $params['filter'] : '';
        $order = $params['order'] ? "ORDER BY $params[order]" : 'ORDER BY id DESC';
        $result = (new Models\Users())->getUsersPaginate(
            ['vu.id', 'name', 'vu.email', 'role', 'status', 'tokenid', 'vu.pagina_inicio', 'vp.nombre as perfil'],
            $filter,
            $order,
            $params['limit']
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

        Response::json(
            [
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
            ],
            200
        );
    }

    public function registerUser(): void
    {
        global $request, $config;

        $params = $request->getAllParams();

        $find = (new models\Users())->findUserByEmail($params['email']);
        if ($find) {
            Response::json(
                [
                    'success' => 0,
                    'message' => 'Error al registrar',
                    'errors' => [
                        'email' => 'El correo ya existe',
                    ],
                ],
                401
            );
            exit();
        }
        $expiration_days_password = $config['auth']['expiration_days_password'] ?? 30;
        $params['expiration_pass'] = date('Y-m-d', strtotime("+$expiration_days_password days"));
        $params['password'] = Functions::hash($params['password']);
        $params['role'] = isset($params['rol']) ? $params['rol'] : 'user';
        $params['status'] = isset($params['status']) ? 0 : 1;

        $result = (new models\Users())->create($params);

        if ($result) {
            Response::json(
                [
                    'success' => 1,
                    'message' => 'Usuario creado correctamente',
                ],
                200
            );
        } else {
            Response::json(
                [
                    'success' => 0,
                    'message' => 'No se pudo crear el usuario',
                ],
                500
            );
        }
    }

    public function editUser(): void
    {
        global $request, $config;

        $params = $request->getAllParams();

        $find = (new models\Users())->findUserByEmail($params['email']);
        if ($find != [] && $find['tokenid'] != $params['tokenid']) {
            Response::json(
                [
                    'success' => 0,
                    'message' => 'Error al registrar',
                    'errors' => [
                        'email' => 'El correo ya existe',
                    ],
                ],
                401
            );
            exit();
        }

        $expiration_days_password = $config['auth']['expiration_days_password'] ?? 30;
        $params['expiration_pass'] = date('Y-m-d', strtotime("+$expiration_days_password days"));

        $params['password'] = Functions::hash($params['password']);
        $params['role'] = isset($params['rol']) ? $params['rol'] : 'user';
        $params['status'] = isset($params['status']) ? 0 : 1;

        $result = (new models\Users())->update($params);

        if ($result) {
            Response::json(
                [
                    'success' => 1,
                    'message' => 'Usuario actualizado correctamente',
                ],
                200
            );
        } else {
            Response::json(
                [
                    'success' => 0,
                    'message' => 'No se pudo actualizar el usuario',
                ],
                500
            );
        }
    }

    public function deleteUser(): void
    {
        global $request;

        $tokenid = $request->get('tokenid');

        $result = (new models\Users())->delete($tokenid);
        if ($result) {
            Response::json(
                [
                    'success' => 1,
                    'message' => 'Usuario actualizado correctamente',
                ],
                200
            );
        } else {
            Response::json(
                [
                    'success' => 0,
                    'message' => 'No se pudo eliminar el usuario',
                ],
                500
            );
        }
    }

    /**
     * @throws SQL
     */
    public function changePassword(): void
    {
        global $request, $config;

        $params = $request->getAllParams();

        $expiration_days_password = $config['auth']['expiration_days_password'] ?? 30;
        $params['expiration_pass'] = date('Y-m-d', strtotime("+$expiration_days_password days"));
        $params['new_password'] = Functions::hash($params['new_password']);

        $result = (new models\Users())->updatePassworByTokenId($params);

        if ($result) {
            Response::json([
                'success' => 1,
                'message' => 'Contraseña actualizada correctamente',
            ]);
        } else {
            Response::json(
                [
                    'success' => 0,
                    'message' => 'No se pudo actualizar la contraseña',
                ],
                500
            );
        }
    }

    public function resetPassByIdUser(): void
    {
        global $request, $config;

        $params = $request->getAllParams();

        $expiration_days_password = $config['auth']['expiration_days_password'] ?? 30;
        $params['expiration_pass'] = date('Y-m-d', strtotime("+$expiration_days_password days"));
        $params['password'] = Functions::hash($params['password']);

        $result = (new models\Users())->updatePassworById($params);

        if ($result) {
            Response::json([
                'success' => 1,
                'message' => 'Contraseña actualizada correctamente',
            ]);
        } else {
            Response::json(
                [
                    'success' => 0,
                    'message' => 'No se pudo actualizar la contraseña',
                ],
                500
            );
        }
    }

    public function updateAvatarById(): void
    {
        global $request, $config;

        $params = $request->getAllParams();
        $fileAvatar = $request->file('fileAvatar');
        $params['avatar'] = $params['id'] . '.' . $fileAvatar->getExtension();

        $pathAvatar =
            $config['assets']['dashboard']['avatars']['dist'] . '/' . $params['id'] . '.' . $fileAvatar->getExtension();
        if (file_exists($pathAvatar)) {
            unlink($pathAvatar);
        }
        $fileAvatar->moveToNewName($pathAvatar);
        $result = (new models\Users())->updateAvatar($params);

        if ($result) {
            Response::json([
                'success' => 1,
                'message' => 'Avatar actualizado correctamente',
                'path' => $pathAvatar,
            ]);
        } else {
            Response::json(
                [
                    'success' => 0,
                    'message' => 'No se pudo actualizar el avatar',
                ],
                500
            );
        }
    }
}
