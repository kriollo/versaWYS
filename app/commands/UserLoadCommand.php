<?php

declare(strict_types=1);

namespace app\commands;

use app\models\Users;
use Override;
use versaWYS\kernel\cli\CLIHelper;
use versaWYS\kernel\helpers\Functions;

/**
 * `php versaCLI app:user:load --count=200`: usuarios para la prueba de capacidad (versaSecurity -load).
 * Crea carga-001@carga.test… con una contraseña aleatoria común y escribe email,password en un CSV.
 * `--delete` los borra. Solo para un entorno de prueba: son cuentas reales con una contraseña conocida.
 */
class UserLoadCommand extends ConsoleCommand
{
    private const string DOMAIN = '@carga.test';

    /**
     * @param array<mixed> $params
     */
    #[Override]
    public function execute(array $params = []): int
    {
        $o = $this->options($params);
        $orm = $this->versaOrm;

        if (array_key_exists('delete', $o)) {
            $ids = array_column(
                $orm
                    ->table('versa_users')
                    ->where('email', 'LIKE', '%' . self::DOMAIN)
                    ->get(),
                'id',
            );
            if ($ids !== []) {
                $orm->table('versa_perfildetalleuser')->whereIn('id_user', $ids)->delete();
                $orm->table('versa_users')->whereIn('id', $ids)->delete();
            }

            $this->success(count($ids) . ' usuarios de carga eliminados');

            return 0;
        }

        $count = (int) ($o['count'] ?? 0);
        $out = $o['out'] ?? 'versaWYS/cache/carga-usuarios.csv';
        if ($count < 1 || $count > 10_000) {
            $this->error('Indica cuántos usuarios crear: --count=1..10000');

            return 1;
        }

        if (
            !array_key_exists('yes', $o)
            && !CLIHelper::confirm("Se crearán {$count} cuentas con una contraseña conocida. ¿Es un entorno de prueba?")
        ) {
            return 1;
        }

        // Una sola contraseña y un solo hash: hashear miles de veces tardaría minutos
        $password = 'Carga#' . bin2hex(random_bytes(8)) . 'Aa1';
        $hash = Functions::hash($password);
        $existing = array_flip(array_column(
            $orm
                ->table('versa_users')
                ->where('email', 'LIKE', '%' . self::DOMAIN)
                ->get(),
            'email',
        ));

        CLIHelper::ensureDirectory(dirname($out));
        $csv = fopen($out, 'wb');
        if ($csv === false) {
            $this->error("No se pudo escribir {$out}");

            return 1;
        }

        fwrite($csv, "email,password\n");
        $created = 0;
        for ($i = 1; $i <= $count; $i++) {
            $email = sprintf('carga-%05d%s', $i, self::DOMAIN);
            if (array_key_exists($email, $existing)) {
                $orm->table('versa_users')->where('email', '=', $email)->update(['password' => $hash, 'status' => 1]);
                fwrite($csv, "{$email},{$password}\n");

                continue;
            }

            (new Users())->createUser([
                'name' => "Carga {$i}",
                'email' => $email,
                'password' => $hash,
                'expiration_pass' => date('Y-m-d', strtotime('+30 days')),
                'role' => 'user',
                'status' => 1,
                'id_perfil' => (int) ($o['perfil'] ?? 2),
                'empresa' => (int) ($o['empresa'] ?? 1),
            ]);
            $created++;

            fwrite($csv, "{$email},{$password}\n");
        }

        fclose($csv);
        chmod($out, 0o600);

        $this->success("{$count} usuarios listos ({$created} nuevos). Credenciales en {$out}");
        $this->info('Bórralos al terminar: php versaCLI app:user:load --delete');

        return 0;
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Usuarios para la prueba de capacidad: --count=N [--out=archivo.csv] [--yes] | --delete';
    }
}
