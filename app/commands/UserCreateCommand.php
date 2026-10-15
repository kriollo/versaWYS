<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Users;
use versaWYS\kernel\cli\CLIHelper;
use versaWYS\kernel\Validator;

/**
 * `php versaCLI app:user:create`: crea un usuario del panel. Pregunta lo que falte o lo toma de las
 * opciones (--name, --email, --role, --empresa, --perfil, --password).
 */
class UserCreateCommand extends ConsoleCommand
{
    private const array ROLES = ['user', 'admin', 'superadmin'];

    /**
     * @param array<mixed> $params
     */
    #[\Override]
    public function execute(array $params = []): int
    {
        $o = $this->options($params);
        $data = [
            'name' => $o['name'] ?? CLIHelper::ask('Nombre'),
            'email' => strtolower(trim($o['email'] ?? CLIHelper::ask('Correo'))),
            'role' => $o['role'] ?? CLIHelper::ask('Rol (user, admin o superadmin)', 'user'),
            'empresa' => $o['empresa'] ?? CLIHelper::ask('ID de la empresa', '1'),
        ];
        // El instalador le da al superadministrador el perfil 1; al resto, «Usuario» (2)
        $data['perfil'] = $o['perfil'] ?? CLIHelper::ask('ID del perfil', $data['role'] === 'user' ? '2' : '1');

        $errors = Validator::make($data, [
            'name' => 'required|max:120',
            'email' => 'required|email|max:120',
            'role' => 'required|in:' . implode(',', self::ROLES),
            'empresa' => 'required|int:positive',
            'perfil' => 'required|int:positive',
        ])->errors();
        $orm = $this->versaOrm;
        if (!isset($errors['email']) && $orm->table('versa_users')->where('email', '=', $data['email'])->count() > 0) {
            $errors['email'] = 'Ya existe un usuario con ese correo';
        }
        if (
            !isset($errors['empresa'])
            && $orm->table('empresas')->where('id', '=', (int) $data['empresa'])->count() === 0
        ) {
            $errors['empresa'] = 'La empresa no existe (ver app:user:list)';
        }
        if (
            !isset($errors['perfil'])
            && $orm->table('versa_perfil')->where('id', '=', (int) $data['perfil'])->count() === 0
        ) {
            $errors['perfil'] = 'El perfil no existe';
        }
        if ($errors !== []) {
            $this->error('Revisa los datos:');
            foreach ($errors as $field => $message) {
                echo "  - {$field}: {$message}\n";
            }

            return 1;
        }

        $password = $this->newPassword($o);
        if ($password === null) {
            return 1;
        }

        global $config;
        $days = $config['auth']['expiration_days_password'] ?? 90;
        $id = (new Users())->createUser([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => \versaWYS\kernel\helpers\Functions::hash($password),
            'expiration_pass' => date('Y-m-d', strtotime("+{$days} days")),
            'role' => $data['role'],
            'status' => 1,
            'id_perfil' => (int) $data['perfil'],
            'empresa' => (int) $data['empresa'],
        ]);

        $this->success("Usuario #{$id} creado: {$data['email']} ({$data['role']})");

        return 0;
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Crea un usuario del panel (pregunta los datos o usa --name, --email, --role, --empresa, --perfil)';
    }
}
