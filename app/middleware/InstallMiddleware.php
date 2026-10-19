<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\GlobalMiddleware;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Installation;
use versaWYS\kernel\Output;
use versaWYS\kernel\Response;

/**
 * Protege las rutas del instalador de primera ejecución.
 */
class InstallMiddleware extends GlobalMiddleware
{
    // Las mismas rutas que usa el kernel para decidir si la instalación está pendiente
    public static function lockFile(): string
    {
        return Installation::lockFile();
    }

    public static function tokenFile(): string
    {
        return Installation::tokenFile();
    }

    public const array DRIVERS = ['mysql', 'postgresql'];

    /**
     * Si la aplicación ya fue instalada, redirige al login.
     */
    public function checkNotInstalled(): void
    {
        global $config;

        if (file_exists(self::lockFile())) {
            Response::redirect('/' . $config['admin']['custom_url'] . '/login');
            Output::halt();
        }
    }

    /**
     * Exige el token generado por `versaCLI config:init`. Evita que un tercero que descubra
     * /install antes que el propietario pueda apuntar el sitio a su base de datos y crear
     * su propio superadministrador.
     */
    public function checkInstallToken(): true|array
    {
        global $request;

        $expected = file_exists(self::tokenFile()) ? trim((string) file_get_contents(self::tokenFile())) : '';
        $received = trim($request->string('install_token'));

        if ($expected === '' || $received === '' || !hash_equals($expected, $received)) {
            return [
                'success' => 0,
                'message' => 'El token de instalación no es válido.',
                'code' => 403,
            ];
        }

        return true;
    }

    /**
     * El servidor debe cumplir los requisitos (SystemCheck), aunque se salte el paso 1 del asistente.
     */
    public function checkRequirements(): true|array
    {
        $failing = array_values(array_filter(Installation::requirements(), static fn(array $c): bool => !$c['ok']));
        if ($failing === []) {
            return true;
        }

        return [
            'success' => 0,
            'message' => 'El servidor no cumple los requisitos para instalar.',
            'errors' => array_map(static fn(array $c): string => $c['label'] . ': ' . $c['fix'], $failing),
            'code' => 422,
        ];
    }

    public function validateParams(): true|array
    {
        global $request;

        $errors = $this->errors($request->getAllParams());
        if ($errors !== []) {
            return [
                'success' => 0,
                'message' => 'Revisa los datos ingresados.',
                'errors' => $errors,
                'code' => 422,
            ];
        }

        return true;
    }

    /**
     * Errores de los datos de instalación (vacío si están bien). Lo usan el asistente web y
     * `versaCLI app:install`.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, string>
     */
    public function errors(array $params): array
    {
        $errors = Functions::validateParams($params, [
            'db_host' => 'required',
            'db_port' => 'required|integer',
            'db_name' => 'required',
            'db_user' => 'required',
            'empresa_nombre' => 'required',
            'empresa_rut' => 'required|rut',
            'admin_name' => 'required',
            'admin_email' => 'required|email',
            'admin_password' => 'required',
        ]);

        if (!in_array($params['db_driver'] ?? '', self::DRIVERS, strict: true)) {
            $errors['db_driver'] = 'Motor de base de datos no soportado';
        }

        // Host, base y usuario terminan en la cadena de conexión: solo caracteres de identificador
        foreach (['db_host', 'db_name', 'db_user'] as $field) {
            if (preg_match('/^[A-Za-z0-9._\-]+$/', Functions::text($params[$field] ?? null)) === 1) {
                continue;
            }

            $errors[$field] = "El campo {$field} contiene caracteres no permitidos";
        }

        $password = Functions::text($params['admin_password'] ?? null);
        if (!hash_equals($password, Functions::text($params['admin_password_confirm'] ?? null))) {
            $errors['admin_password_confirm'] = 'Las contraseñas no coinciden';

            return $errors;
        }

        if (!$this->validatePolicyPassword($password)) {
            $errors['admin_password'] = $this->getMessagePolicyPassword();
        }

        return $errors;
    }
}
