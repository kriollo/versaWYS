<?php

declare(strict_types=1);

namespace app\models;

use DateTime;
use Override;
use SensitiveParameter;
use VersaORM\VersaModel;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\model\VersaORMModel;

class Users extends VersaORMModel
{
    protected string $table = 'versa_users'; // Example table name

    protected array $fillable = ['name', 'email', 'password']; // Example fillable fields

    protected array $guarded = [];

    protected array $rules = [
        'name' => ['required', 'min:2', 'max:100'],
        'email' => ['required', 'email'],
        'password' => ['required', 'min:8'],
        'status' => ['required'], // se marca como requerido explícitamente
    ];

    #[Override]
    public static function definePropertyTypes(): array
    {
        return [
            'id' => ['type' => 'int', 'nullable' => false, 'auto_increment' => true],
            'id_empresa' => ['type' => 'int', 'nullable' => false],
            'tokenid' => ['type' => 'string', 'max_length' => 255, 'nullable' => false],
            'name' => ['type' => 'string', 'max_length' => 100, 'nullable' => false],
            'password' => ['type' => 'string', 'max_length' => 255, 'nullable' => false],
            'expiration_pass' => ['type' => 'varchar', 'nullable' => true],
            'last_login' => ['type' => 'varchar', 'nullable' => true],
            'role' => ['type' => 'string', 'max_length' => 50, 'nullable' => false],
            'status' => ['type' => 'bool', 'nullable' => false],
            'restore_token' => ['type' => 'string', 'max_length' => 255, 'nullable' => true],
            'avatar' => ['type' => 'string', 'max_length' => 255, 'nullable' => true],
            'id_perfil' => ['type' => 'int', 'nullable' => false],
            'pagina_inicio' => ['type' => 'string', 'max_length' => 255, 'nullable' => true],
            'created_at' => ['type' => 'varchar', 'nullable' => false],
            'updated_at' => ['type' => 'varchar', 'nullable' => false],
        ];
    }

    public function getUsersPaginate(
        int $id_empresa = 0,
        array $fields = [],
        string $where = '',
        string $order = 'id ASC',
        int $limit = 15,
        int $offset = 0,
        array $bindings = [],
    ): array {
        // La empresa siempre la impone el servidor; los filtros del cliente solo pueden acotar más
        $filter = 'versa_users.id_empresa = ?' . (trim($where) !== '' ? ' AND ' . $where : '');
        $bindings = [$id_empresa, ...$bindings];
        // `id` existe en las tres tablas del join
        $order = (string) preg_replace('/^id\b/i', 'versa_users.id', $order);

        $result = $this
            ->qb()
            ->lazy()
            ->select($fields)
            ->selectRaw('COUNT(*) OVER() AS total_count')
            ->leftJoin('versa_perfil as vp', 'vp.id', '=', 'versa_users.id_perfil')
            ->leftJoin('empresas as e', 'e.id', '=', 'versa_users.id_empresa')
            ->whereRaw($filter, $bindings)
            ->orderByRaw($order)
            ->limit($limit)
            ->offset($offset)
            ->collect();

        return [
            'total' => isset($result[0]['total_count']) ? (int) $result[0]['total_count'] : 0,
            'data' => $result,
        ];
    }

    /**
     * Find a user by ID or a specific field.
     *
     * @param  int|string  $id  the ID or value of the field to search for
     * @param  string  $field  The field to search in. Defaults to 'id'.
     * @return array the user data if found, an empty array otherwise
     */
    public function findWithCompany(int|string $id, string $field = 'versa_users.id'): array
    {
        $result = $this
            ->qb()
            ->select(['versa_users.*', 'e.nombre as empresa', 'e.token_empresa'])
            ->join('empresas as e', 'e.id', '=', 'versa_users.id_empresa')
            ->where($field, '=', $id)
            ->first();

        return $result instanceof VersaModel ? $result->export() : [];
    }

    /**
     * Find a user by email.
     *
     * Retrieves a user from the database based on the specified email.
     *
     * @param  string  $email  the email of the user to find
     * @return array the user record if found, an empty array otherwise
     */
    public function findUserByEmail(string $email): array
    {
        $usuario = $this->findOne($this->table, $email, 'email');

        return $usuario instanceof VersaModel ? $usuario->export() : [];
    }

    /**
     * Save the reset password token for a user.
     *
     * Updates the restore_token field of a user in the database with the specified token.
     *
     * @param  string  $email  the email of the user
     * @param  string  $token  the reset password token
     */
    public function saveTokenResetPass(string $email, #[SensitiveParameter] string $token): void
    {
        $user = $this->findOne('versa_users', $email, 'email');
        $user->restore_token = $token;
        $user->store();
    }

    /**
     * Find a user by reset password token.
     *
     * Retrieves a user from the database based on the specified reset password token.
     *
     * @param  string  $token  the reset password token
     * @return array the user record if found, an empty array otherwise
     */
    public function findUserByToken(#[SensitiveParameter] string $token): array
    {
        // El token lleva su expiración como sufijo (ver newResetToken); al buscarse completo no es manipulable
        $expires = (int) substr((string) strrchr($token, '.'), 1);
        if ($token === '' || $expires < time()) {
            return [];
        }

        return $this->findWithCompany($token, 'versa_users.restore_token');
    }

    /**
     * Token de recuperación de contraseña de un solo uso, válido por una hora salvo que se indique otro plazo.
     */
    public static function newResetToken(int $ttlSeconds = 3600): string
    {
        return bin2hex(random_bytes(32)) . '.' . (time() + $ttlSeconds);
    }

    /**
     * Update the password for a user.
     *
     * Updates the password field of a user in the database with the specified password.
     * Resets the restore_token field to null.
     *
     * @param  string  $email  the email of the user
     * @param  string  $password  the new password
     */
    public function updatePassword(string $email, #[SensitiveParameter] string $password): void
    {
        $user = $this->load('versa_users', $email, 'email');
        $newParams = $this->setUpdatePassword($password);
        $user->password = $newParams['password'];
        $user->expiration_pass = $newParams['expiration_pass'];
        $user->restore_token = null;
        $user->store();
    }

    /**
     * Creates a new user record in the database.
     *
     * @param  array  $params  An associative array containing user details:
     *                         - 'name' (string): The name of the user.
     *                         - 'email' (string): The email address of the user.
     *                         - 'password' (string): The password for the user.
     *                         - 'expiration_pass' (string): The password expiration date.
     *                         - 'role' (string): The role assigned to the user.
     *                         - 'status' (string): The status of the user.
     *                         - 'perfil' (int): The profile ID associated with the user.
     * @return int|string the ID of the newly created user record, or an error message
     */
    public function createUser(array $params): int|string
    {
        $user = $this->dispense('versa_users');
        $user->tokenid = Functions::generateCSRFToken();
        $user->name = $params['name'];
        $user->email = $params['email'];
        $user->password = $params['password'];
        $user->expiration_pass = $params['expiration_pass'];
        $user->last_login = date('Y-m-d H:i:s');
        $user->role = $params['role'];
        $user->status = $params['status'];
        $user->id_perfil = $params['id_perfil'];
        $user->id_empresa = $params['empresa'] ?? null;
        $user->created_at = date('Y-m-d H:i:s');
        $user->updated_at = date('Y-m-d H:i:s');
        $user->store();

        if ($user->id) {
            $this->setPerfilUser($user->id, (int) $params['id_perfil']);
        }

        return $user->id;
    }

    public function updateUser(array $params): int|string
    {
        $user = $this->findOne('versa_users', $params['tokenid'], 'tokenid');
        $user->name = $params['name'];
        // La contraseña solo cambia si se envió una nueva
        if (($params['password'] ?? '') !== '') {
            $newParams = $this->setUpdatePassword($params['password']);
            $user->password = $newParams['password'];
            $user->expiration_pass = $newParams['expiration_pass'];
        }
        $user->role = $params['role'];
        $user->id_perfil = $params['id_perfil'];
        $user->updated_at = date('Y-m-d H:i:s');
        $user->id_empresa = $params['empresa'] ?? null;
        $user->store();

        if ($user->id) {
            $this->setPerfilUser($user->id, (int) $params['id_perfil']);
        }

        return $user->id;
    }

    public function delete(string $id): int|string
    {
        $user = $this->load('versa_users', $id, 'tokenid');

        $user->status = !$user->status;
        if ($user->status) {
            $user->last_login = date('Y-m-d H:i:s');
        }

        $user->updated_at = date('Y-m-d H:i:s');
        $this->executeWithErrorHandling($user->store(...), [
            'operation' => 'deleteUser',
            'params' => ['id' => $id],
            'table' => static::tableName(),
        ]);

        return $user->id;
    }

    public function updatePasswordByTokenId(array $params): int|string
    {
        $user = $this->load('versa_users', $params['tokenid'], 'tokenid');
        $newParams = $this->setUpdatePassword($params['new_password']);
        $user->password = $newParams['password'];
        $user->expiration_pass = $newParams['expiration_pass'];
        $user->store();

        return $user->id;
    }

    public function updatePassworById(array $params): int|string
    {
        $user = $this->load('versa_users', $params['id'], 'id');
        $newParams = $this->setUpdatePassword($params['password']);
        $user->password = $newParams['password'];
        $user->expiration_pass = $newParams['expiration_pass'];
        $user->store();

        return $user->id;
    }

    public function updateAvatar(array $params): int|string
    {
        $user = $this->load('versa_users', $params['id'], 'id');
        $user->avatar = $params['avatar'];
        $user->updated_at = date('Y-m-d H:i:s');
        $this->executeWithErrorHandling($user->store(...), [
            'operation' => 'updateAvatar',
            'params' => $params,
        ]);

        return $user->id;
    }

    public function updateLastLogin(int $idUser): void
    {
        $user = $this->load('versa_users', $idUser, 'id');
        $user->last_login = date('Y-m-d H:i:s');
        $user->store();
    }

    public function updateStatusInactiveAccount(int $daysInactive): void
    {
        $this
            ->qb()
            ->where(
                'last_login',
                '<',
                (new DateTime())
                    ->modify(sprintf('-%d days', $daysInactive))
                    ->format('Y-m-d H:i:s'),
            )
            ->where('status', '=', true)
            // El superadministrador nunca se desactiva: podría ser la única cuenta capaz de reactivar a las demás
            ->where('role', '!=', 'superadmin')
            ->update(['status' => false]);
    }

    public function getCountUserActives($idEmpresa): int
    {
        return $this->qb()->where('id_empresa', '=', $idEmpresa)->where('status', '=', true)->count();
    }

    /**
     * Reemplaza el hash por uno con el algoritmo y costo actuales, sin tocar la expiración.
     */
    public function rehashPassword(int $idUser, #[SensitiveParameter] string $password): void
    {
        $user = $this->load('versa_users', $idUser, 'id');
        $user->password = Functions::hash($password);
        $user->store();
    }

    private function setUpdatePassword(#[SensitiveParameter] string $newPassword): array
    {
        global $config;
        $expiration_days_password = $config['auth']['expiration_days_password'] ?? 30;

        return [
            'password' => Functions::hash($newPassword),
            'expiration_pass' => date('Y-m-d', strtotime(sprintf('+%s days', $expiration_days_password))),
        ];
    }

    private function setPerfilUser(int $idUser, int $idPerfil): void
    {
        $this->exec('DELETE FROM versa_perfildetalleuser WHERE id_user = ?', [$idUser]);
        $this->exec(
            'INSERT INTO versa_perfildetalleuser (id_user, menu_id, submenu_id) SELECT ?, menu_id, submenu_id FROM versa_perfildetalle WHERE perfil_id = ?',
            [$idUser, $idPerfil],
        );
    }
}
