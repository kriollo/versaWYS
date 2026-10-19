<?php

declare(strict_types=1);

namespace app\commands;

use versaWYS\kernel\cli\BaseCommand;
use versaWYS\kernel\cli\CLIErrorHandler;
use versaWYS\kernel\GlobalMiddleware;

/**
 * Lo común de los comandos que piden datos al operador: opciones `--clave=valor` y contraseñas que no
 * se muestran al escribirlas. Abstracta: versaCLI no la lista como comando.
 */
abstract class ConsoleCommand extends BaseCommand
{
    /**
     * --admin-email=a@b.cl → ['admin_email' => 'a@b.cl']; sin `=` vale "1" (--activar).
     *
     * @param array<mixed> $args
     *
     * @return array<string, string>
     */
    protected function options(array $args): array
    {
        $values = [];
        foreach ([...$args, ...($_SERVER['argv'] ?? [])] as $arg) {
            if (!is_string($arg) || preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $arg, $m) !== 1) {
                continue;
            }

            $values[str_replace('-', '_', $m[1])] = $m[2] ?? '1';
        }

        return $values;
    }

    /** @param array<mixed> $args los argumentos que no son opciones */
    protected function arguments(array $args): array
    {
        return array_values(array_filter($args, static fn($a): bool => is_string($a) && !str_starts_with($a, '-')));
    }

    protected function askSecret(string $question): string
    {
        echo CLIErrorHandler::colorize($question, 'green') . ': ';
        $tty = function_exists('posix_isatty') && posix_isatty(STDIN);
        if ($tty) {
            shell_exec('stty -echo');
        }
        $value = trim((string) fgets(STDIN));
        if ($tty) {
            shell_exec('stty echo');
            echo "\n";
        }

        return $value;
    }

    /**
     * La contraseña nueva: de `--password=` (para scripts; queda en el historial de la terminal) o
     * preguntada dos veces sin mostrarla. null si no cumple la política de contraseñas o no coincide.
     *
     * @param array<string, string> $options
     */
    protected function newPassword(array $options): ?string
    {
        $password = $options['password'] ?? $this->askSecret('Contraseña nueva');
        if (
            ($options['password'] ?? null) === null
            && !hash_equals($password, $this->askSecret('Repite la contraseña'))
        ) {
            $this->error('Las contraseñas no coinciden.');

            return null;
        }

        $policy = new GlobalMiddleware();
        if (!$policy->validatePolicyPassword($password)) {
            $this->error($policy->getMessagePolicyPassword());

            return null;
        }

        return $password;
    }
}
