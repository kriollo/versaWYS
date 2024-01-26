<?php

declare(strict_types=1);

namespace app\Models;

use versaWYS\kernel\helpers\Functions;
use RedBeanPHP\R;
use versaWYS\kernel\RedBeanCnn;

/**
 * Class Users
 *
 * This class represents the Users model in the application.
 * It provides methods to interact with the users table in the database.
 */
class Users extends \RedBeanPHP\SimpleModel
{
    /**
     * Users constructor.
     *
     * Initializes a new instance of the Users class.
     * Sets up the RedBeanPHP connection.
     */
    public function __construct()
    {
        (new RedBeanCnn())->setup();
    }

    /**
     * Users destructor.
     *
     * Closes the RedBeanPHP connection.
     */
    public function __destruct()
    {
        R::close();
    }

    /**
     * Get all eventos.
     *
     * Retrieves all the eventos from the database.
     *
     * @return array An array of user records.
     */
    public function pagination(string $filter = '')
    {
        $filter = $filter ? "WHERE $filter" : '';
        $result = R::getAll("SELECT SQL_CALC_FOUND_ROWS * FROM users $filter");
        $total = R::getCell('SELECT FOUND_ROWS()');
        return ['total' => $total, 'data' => $result];
    }


    /**
     * Find a user by ID or a specific field.
     *
     * @param int|string $id The ID or value of the field to search for.
     * @param string $field The field to search in. Defaults to 'id'.
     * @return array The user data if found, an empty array otherwise.
     */
    public function find($id, $field = 'id')
    {
        $result = R::findOne('users', "{$field} = ?", [$id]);
        return $result ? $result->export() : [];
    }

    /**
     * Find a user by email.
     *
     * Retrieves a user from the database based on the specified email.
     *
     * @param string $email The email of the user to find.
     * @return array The user record if found, an empty array otherwise.
     */
    public function findUserByEmail($email)
    {
        $result = $this->find($email, 'email');
        return $result;
    }

    /**
     * Save the reset password token for a user.
     *
     * Updates the restore_token field of a user in the database with the specified token.
     *
     * @param string $email The email of the user.
     * @param string $token The reset password token.
     * @return void
     */
    public function saveTokenResetPass($email, $token)
    {
        $user = R::findOne('users', 'email = ?', [$email]);
        $user->restore_token = $token;
        R::store($user);
    }

    /**
     * Find a user by reset password token.
     *
     * Retrieves a user from the database based on the specified reset password token.
     *
     * @param string $token The reset password token.
     * @return array The user record if found, an empty array otherwise.
     */
    public function findUserByToken($token)
    {
        $result = $this->find($token, 'restore_token');
        return $result;
    }

    /**
     * Update the password for a user.
     *
     * Updates the password field of a user in the database with the specified password.
     * Resets the restore_token field to null.
     *
     * @param string $email The email of the user.
     * @param string $password The new password.
     * @return void
     */
    public function updatePassword($email, $password)
    {
        $user = R::findOne('users', 'email = ?', [$email]);
        $user->password = $password;
        $user->restore_token = null;
        R::store($user);
    }

    public function create($params)
    {
        $user = R::dispense('users');
        $user->tokenid = Functions::generateCSRFToken();
        $user->name = $params['name'];
        $user->email = $params['email'];
        $user->password = $params['password'];
        $user->role = $params['role'];
        $user->status = $params['status'];
        $user->created_at = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }

    public function update($params)
    {
        $user = R::findOne('users', 'tokenid = ?', [$params['tokenid']]);
        $user->name = $params['name'];
        $user->password = $params['password'];
        $user->role = $params['role'];
        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }

    public function delete($id)
    {
        $user = R::findOne('users', 'tokenid = ?', [$id]);
        $user->status = $user->status === '1' ? '0' : '1';
        return R::store($user);
    }

    public function updatePassworByTokenId($params)
    {
        $user = R::findOne('users', 'tokenid = ?', [$params['tokenid']]);
        $user->password = $params['new_password'];
        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }
}
