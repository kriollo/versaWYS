<?php

declare(strict_types=1);

namespace app\models;

use Random\RandomException;
use RedBeanPHP\R;
use RedBeanPHP\RedException\SQL;
use RedBeanPHP\SimpleModel;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\RedBeanCnn;

/**
 * Class versausers
 *
 * This class represents the Users model in the application.
 * It provides methods to interact with the users table in the database.
 */
class Users extends SimpleModel
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
    public function pagination(string $filter = ''): array
    {
        $filter = $filter ? "WHERE $filter" : '';
        $result = R::getAll("SELECT SQL_CALC_FOUND_ROWS * FROM versausers $filter");
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
    public function find(int|string $id, string $field = 'id'): array
    {
        $result = R::findOne('versausers', "$field = ?", [$id]);
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
    public function findUserByEmail(string $email): array
    {
        return $this->find($email, 'email');
    }

    /**
     * Save the reset password token for a user.
     *
     * Updates the restore_token field of a user in the database with the specified token.
     *
     * @param string $email The email of the user.
     * @param string $token The reset password token.
     * @return void
     * @throws SQL
     */
    public function saveTokenResetPass(string $email, string $token): void
    {
        $user = R::findOne('versausers', 'email = ?', [$email]);
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
    public function findUserByToken(string $token): array
    {
        return $this->find($token, 'restore_token');
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
     * @throws SQL
     */
    public function updatePassword(string $email, string $password): void
    {
        $user = R::findOne('versausers', 'email = ?', [$email]);
        $user->password = $password;
        $user->restore_token = null;
        R::store($user);
    }

    /**
     * @throws SQL|RandomException
     */
    public function create($params): int|string
    {
        $user = R::dispense('versausers');
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

    /**
     * @throws SQL
     */
    public function update($params): int|string
    {
        $user = R::findOne('versausers', 'tokenid = ?', [$params['tokenid']]);
        $user->name = $params['name'];
        $user->password = $params['password'];
        $user->role = $params['role'];
        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }

    /**
     * @throws SQL
     */
    public function delete($id): int|string
    {
        $user = R::findOne('versausers', 'tokenid = ?', [$id]);
        $user->status = $user->status === '1' ? '0' : '1';
        return R::store($user);
    }

    /**
     * @throws SQL
     */
    public function updatePassworByTokenId($params): int|string
    {
        $user = R::findOne('versausers', 'tokenid = ?', [$params['tokenid']]);
        $user->password = $params['new_password'];
        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }

    public function updatePassworById($params): int|string
    {
        $user = R::findOne('versausers', 'id = ?', [$params['id']]);
        $user->password = $params['password'];
        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }

    public function updateAvatar($params): int|string
    {
        $user = R::findOne('versausers', 'id = ?', [$params['id']]);
        $user->avatar = $params['avatar'];
        $user->updated_at = date('Y-m-d H:i:s');
        return R::store($user);
    }
}
