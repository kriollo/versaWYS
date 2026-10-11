<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\GlobalMiddleware;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Response;

/**
 * Protege las rutas del instalador de primera ejecución.
 */
class InstallMiddleware extends GlobalMiddleware
{
    public const string LOCK_FILE = 'versaWYS/kernel/config/installed.lock';

    public const string TOKEN_FILE = 'versaWYS/kernel/config/install.token';

    public const array DRIVERS = ['mysql', 'postgresql'];

    /**
     * Si la aplicación ya fue instalada, redirige al login.
     */
    public function checkNotInstalled(): void
    {
        global $config;

        if (file_exists(self::LOCK_FILE)) {
            Response::redirect('/' . $config['admin']['custom_url'] . '/login');
            exit();
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

        $expected = file_exists(self::TOKEN_FILE) ? trim((string) file_get_contents(self::TOKEN_FILE)) : '';
        $received = trim((string) $request->get('install_token', ''));

        if ($expected === '' || $received === '' || !hash_equals($expected, $received)) {
            return [
                'success' => 0,
                'message' => 'El token de instalación no es válido.',
                'code' => 403,
            ];
        }

        return true;
    }

    public function validateParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

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
            if (preg_match('/^[A-Za-z0-9._\-]+$/', (string) ($params[$field] ?? '')) !== 1) {
                $errors[$field] = "El campo {$field} contiene caracteres no permitidos";
            }
        }

        $password = (string) ($params['admin_password'] ?? '');
        if (!hash_equals($password, (string) ($params['admin_password_confirm'] ?? ''))) {
            $errors['admin_password_confirm'] = 'Las contraseñas no coinciden';
        } elseif (!$this->validatePolicyPassword($password)) {
            $errors['admin_password'] = $this->getMessagePolicyPassword();
        }

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
}
