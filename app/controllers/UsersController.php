<?php

declare(strict_types=1);

namespace app\controllers;

use versaWYS\kernel\Globalcontrollers;
use app\Models as Models;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Response;

class UsersController extends Globalcontrollers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }
    public function index()
    {
        return $this->template->render('dashboard/usuarios/dashUsers');
    }

    public function addUserTemplate()
    {
        return $this->template->render('dashboard/usuarios/addUser');
    }

    public function getAllUsers()
    {
        $result = (new models\Users)->all();
        Response::json([
            'success' => 1,
            'data' => $result
        ], 200);
    }

    public function registerUser()
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
        $params['role'] = isset($params['role']) ? 'admin' : 'user';
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

    public function editUserTemplate($slug = null)
    {
        $user = (new models\Users)->find($slug, 'tokenid');
        return $this->template->render('dashboard/usuarios/editUser', [
            'user' => $user
        ]);
    }

    public function editUser()
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
        $params['role'] = isset($params['role']) ? 'admin' : 'user';
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

    public function deleteUser()
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

    public function changePassword()
    {
        global $request;

        $params = $request->getAllParams();

        $params['new_password'] = Functions::hash($params['new_password']);

        $result = (new models\Users)->updatePassworByTokenId($params);

        if ($result) {
            Response::json([
                'success' => 1,
                'message' => 'Contraseña actualizada correctamente'
            ], 200);
        } else {
            Response::json([
                'success' => 0,
                'message' => 'No se pudo actualizar la contraseña'
            ], 500);
        }
    }
}