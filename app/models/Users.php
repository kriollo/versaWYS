<?php

declare(strict_types=1);

namespace app\models;

use RedBeanPHP\RedException\SQL;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\RedBeanCnn;

/**
 * Class versausers
 *
 * Thi    public function updatePassworById($params): int|string
    {
        $user = $this->findOne('versausers', 'id = ?', [$params['id']]);
        $user->password = $params['password'];
        $user->expiration_pass = $params['expiration_pass'];
        $user->last_login = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        return $this->store($user);
    }epresents the Users model in the application.
 * It provides methods to interact with the users table in the database.
 */
class Users extends RedBeanCnn
{
    /**
     * Users constructor.
     *
     * Initializes a new instance of the Users class.
     * Sets up the RedBeanPHP connection.
     */
    public function __construct()
    {
        $this->connet();
    }

    /**
     * Users destructor.
     *
     * Closes the RedBeanPHP connection.
     */
    public function __destruct()
    {
        $this->closeDB();
    }

    public function getUsersPaginate(
        array $fields = [],
        string $where = '',
        string $order = 'ORDER BY id ASC',
        string $limit = 'LIMIT 0, 15'
    ): array {
        $filter = trim($where) != '' ? "WHERE $where" : '';
        $fields = $fields ? implode(',', $fields) : '*';

        $result = $this->getAll("SELECT SQL_CALC_FOUND_ROWS $fields
        FROM versausers vu
        LEFT JOIN versaperfil vp ON vp.id = vu.id_perfil
        $filter $order $limit");
        $total = $this->getCell('SELECT FOUND_ROWS()');
        return ['total' => $total, 'data' => $result];
    }

    /**
     * Find a user by ID or a specific field.
     *
     * @param int|string $id The ID or value of the field to search for.
     * @param string $field The field to search in. Defaults to 'id'.
     * @return array The user data if found, an empty array otherwise.
     */
    public function find(int|string $id, string $field = 'u.id'): array
    {
        $result = $this->getRow("SELECT u.* FROM versausers as u  WHERE $field = ?", [$id]);
        return $result ? $result : [];
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
        return $this->find($email, 'u.email');
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
    public function saveTokenResetPass(string $email, string $token): void
    {
        $user = $this->findOne('versausers', 'email = ?', [$email]);
        $user->restore_token = $token;
        $this->store($user);
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
        return $this->find($token, 'u.restore_token');
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
    public function updatePassword(string $email, string $password): void
    {
        $user = $this->findOne('versausers', 'email = ?', [$email]);
        $user->password = $password;
        $user->last_login = date('Y-m-d H:i:s');
        $user->restore_token = null;
        $this->store($user);
    }

    /**
     * Creates a new user record in the database.
     *
     * @param array $params An associative array containing user details:
     *                      - 'name' (string): The name of the user.
     *                      - 'email' (string): The email address of the user.
     *                      - 'password' (string): The password for the user.
     *                      - 'expiration_pass' (string): The password expiration date.
     *                      - 'role' (string): The role assigned to the user.
     *                      - 'status' (string): The status of the user.
     *                      - 'perfil' (int): The profile ID associated with the user.
     *
     * @return int|string The ID of the newly created user record, or an error message.
     */
    public function create($params): int|string
    {
        $user = $this->dispense('versausers');
        $user->tokenid = Functions::generateCSRFToken();
        $user->name = $params['name'];
        $user->email = $params['email'];
        $user->password = $params['password'];
        $user->expiration_pass = $params['expiration_pass'];
        $user->last_login = date('Y-m-d H:i:s');
        $user->role = $params['role'];
        $user->status = $params['status'];
        $user->id_perfil = $params['perfil'];
        $user->created_at = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        $result = $this->store($user);

        if ($result) {
            $this->setPerfilUser($user->id, $params['perfil']);
        }

        return $result;
    }

    /**
     * @throws SQL
     */
    public function update($params): int|string
    {
        $user = $this->findOne('versausers', 'tokenid = ?', [$params['tokenid']]);
        $user->name = $params['name'];
        $user->password = $params['password'];
        $user->expiration_pass = $params['expiration_pass'];
        $user->role = $params['role'];
        $user->id_perfil = $params['perfil'];
        $user->updated_at = date('Y-m-d H:i:s');
        $result = $this->store($user);

        if ($result) {
            $this->setPerfilUser($user->id, $params['perfil']);
        }

        return $result;
    }

    private function setPerfilUser($idUser, $idPerfil): void
    {
        $this->exec('DELETE FROM versaperfildetalleuser WHERE id_user = ?', [$idUser]);
        $this->exec(
            'INSERT INTO versaperfildetalleuser (id_user, menu_id, submenu_id) SELECT ?, menu_id, submenu_id FROM versaperfildetalle WHERE perfil_id = ?',
            [$idUser, $idPerfil]
        );
    }

    /**
     * @throws SQL
     */
    public function delete($id): int|string
    {
        $user = $this->findOne('versausers', 'tokenid = ?', [$id]);
        $user->status = $user->status === '1' ? '0' : '1';
        if ($user->status === '1') {
            $user->last_login = date('Y-m-d H:i:s');
        }
        return $this->store($user);
    }

    /**
     * @throws SQL
     */
    public function updatePassworByTokenId($params): int|string
    {
        $user = $this->findOne('versausers', 'tokenid = ?', [$params['tokenid']]);
        $user->password = $params['new_password'];
        $user->expiration_pass = $params['expiration_pass'];
        $user->updated_at = date('Y-m-d H:i:s');
        return $this->store($user);
    }

    public function updatePassworById($params): int|string
    {
        $user = $this->findOne('versausers', 'id = ?', [$params['id']]);
        $user->password = $params['password'];
        $user->expiration_pass = $params['expiration_pass'];
        $user->last_login = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        return $this->store($user);
    }

    public function updateAvatar($params): int|string
    {
        $user = $this->findOne('versausers', 'id = ?', [$params['id']]);
        $user->avatar = $params['avatar'];
        $user->updated_at = date('Y-m-d H:i:s');
        return $this->store($user);
    }

    public function updateLastLogin($idUser): void
    {
        $user = $this->findOne('versausers', 'id = ?', [$idUser]);
        $user->last_login = date('Y-m-d H:i:s');
        $this->store($user);
    }

    public function updateStatusInactiveAccount($daysInactive): void
    {
        $this->exec('UPDATE versausers SET status = 0 WHERE last_login < DATE_SUB(NOW(), INTERVAL ? DAY) AND status = 1', [
            $daysInactive,
        ]);
    }
}
