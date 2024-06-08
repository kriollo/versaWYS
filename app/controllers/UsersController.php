<?php

declare(strict_types=1);

namespace app\controllers;

use app\Models as Models;
use RedBeanPHP\RedException\SQL;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use versaWYS\kernel\Globalcontrollers;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Response;

class UsersController extends Globalcontrollers
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
        return $this->template->render('dashboard/usuarios/dashUsers', [
            'menu_op' => ['id_menu' => 0]
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    public function addUserTemplate(): string
    {
        return $this->template->render('dashboard/usuarios/addUser');
    }

    public function getUsersPaginated(): void
    {
        global $request;

        $page = (int) ($request->get('page') ?? 1);
        $per_page = (int) ($request->get('per_page') ?? 15);
        $fitro = (string) ($request->get('filter') ?? '');

        if ($page == "" && !is_numeric($page)) {
            $page = 1;
        }
        if ($per_page == "" && !is_numeric($per_page)) {
            $per_page = 15;
        }

        if ($page > 1) {
            $page = (int) ($page - 1) * $per_page;
        } else {
            $page = 0;
        }
        $limit = "LIMIT $page , $per_page";

        $filter = "1 = 1";
        if ($fitro != "") {
            $filter = "name LIKE '%$fitro%' OR email LIKE '%$fitro%' OR role LIKE '%$fitro%' ";
        }

        $filter = "$filter ORDER BY id asc ";

        $users = (new Models\Users())->pagination($filter . $limit);
        $count = (int) $users['total'];

        $total_pages = ceil($count / $per_page);

        $columns = [
            ["field" => "id", "title" => "ID"],
            ["field" => "name", "title" => "Nombre"],
            ["field" => "email", "title" => "Correo"],
            ["field" => "role", "title" => "Rol"],
            ["field" => "status", "title" => "Estado", "type" => "status"],
            ["field" => "actions", "title" => "Acciones", "type" => "actions", "buttons" => [
                ["title" => "Editar", "icon" => "bi bi-pencil-fill text-xl text-yellow-500", "class" => "text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white", "action" => "editUser", "condition" => "status", "condition_value" => "1"],
                ["title" => "Cambiar contraseña", "icon" => "bi bi-key-fill text-xl text-blue-500", "class" => "text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white", "action" => "changePassword", "condition" => "status", "condition_value" => "1"],
                ["title" => "Desactivar Usuario", "icon" => "bi bi-trash-fill text-xl text-red-500", "class" => "text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white", "action" => "changeStatus", "condition" => "status", "condition_value" => "1"],
                ["title" => "Activar Usuario", "icon" => "bi bi-recycle text-xl text-green-500", "class" => "text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white", "action" => "changeStatus", "condition" => "status", "condition_value" => "0"]
            ]],
        ];

        Response::json([
            'success' => 1,
            'data' => $users['data'],
            'columns' => $columns,
            'filter' => $fitro,
            'total' => $count,
            'from' => ($page) + 1,
            'to' => ($page) + count($users['data']),
            'page' => $page,
            'total_pages' => (int) $total_pages,
        ], 200);
    }

    public function registerUser(): void
    {
        global $request;

        $params = $request->getAllParams();

        $find = (new models\Users)->findUserByEmail($params['email']);
        if ($find) {
            Response::json([
                'success' => 0,
                'message' => 'Error al registrar',
                'errors' => [
                    'email' => 'El correo ya existe'
                ]
            ], 401);
            exit;
        }

        $params['password'] = Functions::hash($params['password']);
        $params['role'] = isset($params['rol']) && $params['rol'] === 'on' ? 'admin' : 'user';
        $params['status'] = isset($params['status']) ? 0 : 1;

        $result = (new models\Users)->create($params);

        if ($result) {
            Response::json([
                'success' => 1,
                'message' => 'Usuario creado correctamente'
            ], 200);
        } else {
            Response::json([
                'success' => 0,
                'message' => 'No se pudo crear el usuario'
            ], 500);
        }
    }

    public function editUserTemplate($slug = null): string
    {
        $user = (new models\Users)->find($slug, 'tokenid');
        return $this->template->render('dashboard/usuarios/editUser', [
            'user' => $user
        ]);
    }

    public function editUser(): void
    {
        global $request;

        $params = $request->getAllParams();

        $find = (new models\Users)->findUserByEmail($params['email']);
        if ($find != [] && $find['tokenid'] != $params['tokenid']) {
            Response::json([
                'success' => 0,
                'message' => 'Error al registrar',
                'errors' => [
                    'email' => 'El correo ya existe'
                ]
            ], 401);
            exit;
        }

        $params['password'] = Functions::hash($params['password']);
        $params['role'] =  isset($params['rol']) && $params['rol'] === 'on' ? 'admin' : 'user';
        $params['status'] = isset($params['status']) ? 0 : 1;

        $result = (new models\Users)->update($params);

        if ($result) {
            Response::json([
                'success' => 1,
                'message' => 'Usuario actualizado correctamente'
            ], 200);
        } else {
            Response::json([
                'success' => 0,
                'message' => 'No se pudo actualizar el usuario'
            ], 500);
        }
    }

    public function deleteUser(): void
    {
        global $request;

        $tokenid = $request->get('tokenid');

        $result = (new models\Users)->delete($tokenid);
        if ($result) {
            Response::json([
                'success' => 1,
                'message' => 'Usuario actualizado correctamente'
            ], 200);
        } else {
            Response::json([
                'success' => 0,
                'message' => 'No se pudo eliminar el usuario'
            ], 500);
        }
    }

    /**
     * @throws SQL
     */
    public function changePassword(): void
    {
        global $request;

        $params = $request->getAllParams();

        $params['new_password'] = Functions::hash($params['new_password']);

        $result = (new models\Users)->updatePassworByTokenId($params);

        if ($result) {
            Response::json([
                'success' => 1,
                'message' => 'Contraseña actualizada correctamente'
            ]);
        } else {
            Response::json([
                'success' => 0,
                'message' => 'No se pudo actualizar la contraseña'
            ], 500);
        }
    }
}
