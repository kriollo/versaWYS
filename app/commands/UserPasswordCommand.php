<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Users;

/**
 * `php versaCLI app:user:password <correo>`: cambia la contraseña de un usuario, por ejemplo la del
 * administrador que la olvidó. La pide dos veces sin mostrarla (o `--password=` en un script) y cumple
 * la política de contraseñas. Con `--activar` también reactiva la cuenta.
 */
class UserPasswordCommand extends ConsoleCommand
{
    /**
     * @param array<mixed> $params
     */
    #[\Override]
    public function execute(array $params = []): int
    {
        $o = $this->options($params);
        $email = strtolower(trim($this->arguments($params)[0] ?? $o['email'] ?? ''));
        if ($email === '') {
            $this->error('Uso: php versaCLI app:user:password <correo> [--activar]');

            return 1;
        }

        $users = $this->versaOrm->exec('SELECT id, status FROM versa_users WHERE email = ?', [$email]);
        if ($users === []) {
            $this->error("No existe un usuario con el correo {$email} (ver app:user:list)");

            return 1;
        }

        $password = $this->newPassword($o);
        if ($password === null) {
            return 1;
        }

        // También borra un enlace de recuperación pendiente y renueva el vencimiento de la contraseña
        (new Users())->updatePassword($email, $password);
        $this->success("Contraseña de {$email} actualizada.");

        if ((bool) $users[0]['status']) {
            return 0;
        }

        if (!isset($o['activar'])) {
            $this->warning('La cuenta está inactiva: agrega --activar para reactivarla.');

            return 0;
        }

        // last_login al día: si no, la revisión de cuentas inactivas la volvería a desactivar
        $this->versaOrm
            ->table('versa_users')
            ->where('email', '=', $email)
            ->update([
                'status' => true,
                'last_login' => date('Y-m-d H:i:s'),
            ]);
        $this->success('Cuenta reactivada.');

        return 0;
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Cambia la contraseña de un usuario por su correo (--activar reactiva la cuenta)';
    }
}
