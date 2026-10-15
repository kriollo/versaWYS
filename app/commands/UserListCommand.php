<?php

declare(strict_types=1);

namespace app\commands;

/**
 * `php versaCLI app:user:list [--empresa=ID]`: los usuarios con su rol, perfil, empresa y estado.
 * Nunca muestra contraseñas ni tokens.
 */
class UserListCommand extends ConsoleCommand
{
    /**
     * @param array<mixed> $params
     */
    #[\Override]
    public function execute(array $params = []): int
    {
        $empresa = $this->options($params)['empresa'] ?? null;
        $rows = $this->versaOrm->exec(
            'SELECT u.id, u.name, u.email, u.role, u.status, u.last_login, p.nombre AS perfil, e.nombre AS empresa
             FROM versa_users u
             LEFT JOIN versa_perfil p ON p.id = u.id_perfil
             LEFT JOIN empresas e ON e.id = u.id_empresa'
            . ($empresa !== null ? ' WHERE u.id_empresa = ?' : '')
            . ' ORDER BY u.id',
            $empresa !== null ? [(int) $empresa] : [],
        );

        if ($rows === []) {
            $this->info('No hay usuarios.');

            return 0;
        }

        $line = "%-5s %-24s %-32s %-11s %-14s %-20s %-8s %s\n";
        printf($line, 'ID', 'Nombre', 'Correo', 'Rol', 'Perfil', 'Empresa', 'Estado', 'Último ingreso');
        foreach ($rows as $u) {
            printf(
                $line,
                $u['id'],
                mb_strimwidth((string) $u['name'], 0, 24, '…'),
                mb_strimwidth((string) $u['email'], 0, 32, '…'),
                $u['role'],
                mb_strimwidth((string) $u['perfil'], 0, 14, '…'),
                mb_strimwidth((string) $u['empresa'], 0, 20, '…'),
                (bool) $u['status'] ? 'activo' : 'inactivo',
                substr((string) $u['last_login'], 0, 16),
            );
        }

        return 0;
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Lista los usuarios (sin contraseñas); --empresa=ID filtra por empresa';
    }
}
