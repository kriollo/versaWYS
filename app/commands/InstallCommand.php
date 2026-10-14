<?php

declare(strict_types=1);

namespace app\commands;

use app\controllers\InstallController;
use app\middleware\InstallMiddleware;
use versaWYS\kernel\cli\BaseCommand;
use versaWYS\kernel\cli\CLIErrorHandler;
use versaWYS\kernel\cli\CLIHelper;
use versaWYS\kernel\cli\ConfigManager;

/**
 * `php versaCLI app:install`: el mismo instalador que /install, desde la terminal. Pregunta lo que
 * falte o lo toma de las opciones (--db-host=...), para servidores sin navegador. No pide el token
 * de /install: quien tiene la terminal ya tiene acceso al servidor.
 */
class InstallCommand extends BaseCommand
{
    /** @var array<string, array{0: string, 1: string}> parámetro => [pregunta, valor por defecto] */
    private const array FIELDS = [
        'db_driver' => ['Motor de base de datos (mysql o postgresql)', 'mysql'],
        'db_host' => ['Host de la base de datos', 'localhost'],
        'db_port' => ['Puerto', ''],
        'db_name' => ['Nombre de la base de datos (vacía)', ''],
        'db_user' => ['Usuario de la base de datos', ''],
        'db_pass' => ['Contraseña de la base de datos', ''],
        'empresa_nombre' => ['Nombre de la empresa', ''],
        'empresa_rut' => ['RUT de la empresa', ''],
        'admin_name' => ['Nombre del superadministrador', ''],
        'admin_email' => ['Correo del superadministrador', ''],
        'admin_password' => ['Contraseña del superadministrador', ''],
    ];

    private const array SECRETS = ['db_pass', 'admin_password'];

    /**
     * @param array<mixed> $params
     */
    #[\Override]
    public function execute(array $params = []): int
    {
        global $config;

        if (file_exists(InstallMiddleware::lockFile())) {
            CLIErrorHandler::error('La aplicación ya está instalada (' . InstallMiddleware::lockFile() . ').');

            return 1;
        }

        // El instalador usa la configuración completa, como en una petición web
        $config = array_replace_recursive(ConfigManager::defaults(), ConfigManager::getConfig());

        $values = $this->options([...$params, ...($_SERVER['argv'] ?? [])]);
        foreach (self::FIELDS as $field => [$question, $default]) {
            if (isset($values[$field])) {
                continue;
            }
            if ($field === 'db_port') {
                $default = ($values['db_driver'] ?? '') === 'postgresql' ? '5432' : '3306';
            }
            $values[$field] = in_array($field, self::SECRETS, true) ? $this->askSecret($question) : CLIHelper::ask($question, $default);
            if ($values[$field] === '') {
                $values[$field] = $default;
            }
        }
        // Por opciones no se repite la contraseña; en modo interactivo se pide de nuevo
        $values['admin_password_confirm'] ??= isset($this->options($_SERVER['argv'] ?? [])['admin_password'])
            ? $values['admin_password']
            : $this->askSecret('Repite la contraseña del superadministrador');

        $errors = (new InstallMiddleware())->errors($values);
        if ($errors !== []) {
            CLIErrorHandler::error('Revisa los datos ingresados:');
            foreach ($errors as $field => $message) {
                echo "  - {$field}: {$message}\n";
            }

            return 1;
        }

        $result = (new InstallController())->install($values);
        foreach ((array) ($result['log'] ?? []) as $line) {
            CLIErrorHandler::info((string) $line);
        }
        if (($result['success'] ?? 0) !== 1) {
            CLIErrorHandler::error((string) $result['message']);

            return 1;
        }

        CLIErrorHandler::success("Instalación completada. Ingresa en /{$config['admin']['custom_url']}/login");

        return 0;
    }

    /**
     * --db-host=localhost → ['db_host' => 'localhost']
     *
     * @param array<mixed> $args
     *
     * @return array<string, string>
     */
    private function options(array $args): array
    {
        $values = [];
        foreach ($args as $arg) {
            if (is_string($arg) && preg_match('/^--([a-z-]+)=(.*)$/s', $arg, $m) === 1) {
                $key = str_replace('-', '_', $m[1]);
                if (array_key_exists($key, self::FIELDS)) {
                    $values[$key] = $m[2];
                }
            }
        }

        return $values;
    }

    private function askSecret(string $question): string
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

    #[\Override]
    public function getDescription(): string
    {
        return 'Instala la aplicación desde la terminal: base de datos, migraciones, empresa y superadministrador';
    }

    #[\Override]
    public function getHelp(): string
    {
        $options = implode(' ', array_map(static fn (string $f): string => '--' . str_replace('_', '-', $f) . '=...', array_keys(self::FIELDS)));

        return "Uso: php versaCLI app:install [opciones]\n\n"
            . "Sin opciones pregunta cada dato. Para servidores sin navegador o scripts:\n  {$options}\n\n"
            . "La base de datos debe existir y estar vacía. Si la aplicación ya está instalada (installed.lock), no hace nada.\n";
    }
}
