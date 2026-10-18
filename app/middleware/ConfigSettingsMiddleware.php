<?php

declare(strict_types=1);

namespace app\middleware;

use versaWYS\kernel\helpers\Functions;

class ConfigSettingsMiddleware
{
    public function validateSaveSettingsParams(): true|array
    {
        global $request;

        $params = $request->getAllParams();

        $flat = [
            'mail_host' => $params['mail']['host'] ?? null,
            'mail_port' => $params['mail']['port'] ?? null,
            'mail_transport' => $params['mail']['transport'] ?? null,
            'mail_secure' => $params['mail']['secure'] ?? null,
            'mail_name_from' => $params['mail']['name_from'] ?? null,
            'mail_username' => $params['mail']['username'] ?? null,
            'login_max' => $params['login_attempt']['max'] ?? null,
            'login_time' => $params['login_attempt']['time'] ?? null,
            'expiration_days_password' => $params['auth']['expiration_days_password'] ?? null,
            'inactive_account_days' => $params['auth']['inactive_account_days'] ?? null,
            'password_large' => $params['auth']['password_policy']['large'] ?? null,
            'build_name' => $params['build']['name'] ?? null,
            'build_version' => $params['build']['version'] ?? null,
            'build_timezone' => $params['build']['timezone'] ?? null,
            'build_charset' => $params['build']['charset'] ?? null,
            'admin_custom_url' => $params['admin']['custom_url'] ?? null,
        ];

        // El correo es opcional: sin servidor SMTP no se exige el resto, y así se puede guardar
        // cualquier otra pestaña (antes, sin correo configurado no se podía guardar nada)
        $mailConfigured = trim(Functions::text($flat['mail_host'])) !== '';
        $errors = Functions::validateParams($flat, [
            'mail_host' => 'nullable|string',
            'mail_port' => 'required|int:1-65535',
            'mail_transport' => 'required|string',
            'mail_secure' => $mailConfigured ? 'required|string' : 'nullable|string',
            'mail_name_from' => $mailConfigured ? 'required|string' : 'nullable|string',
            'mail_username' => 'nullable|string',
            'login_max' => 'required|int:positive',
            'login_time' => 'required|int:positive',
            'expiration_days_password' => 'required|int:positive',
            'inactive_account_days' => 'required|int:positive',
            'password_large' => 'required|int:positive',
            'build_name' => 'required|string',
            'build_version' => 'required|string',
            'build_timezone' => 'required|string',
            'build_charset' => 'required|string',
            'admin_custom_url' => 'required|string',
        ]);

        // Valores que PHP y el Router usan directamente: deben ser reales, no solo no vacíos
        if (!in_array($flat['build_timezone'], timezone_identifiers_list(), strict: true)) {
            $errors['build_timezone'] = 'La zona horaria no es válida';
        }
        if (!in_array(
            strtoupper(Functions::text($flat['build_charset'])),
            array_map(strtoupper(...), mb_list_encodings()),
            strict: true,
        )) {
            $errors['build_charset'] = 'El charset no es válido';
        }
        if (preg_match('/^[A-Za-z0-9_\-]+$/', Functions::text($flat['admin_custom_url'])) !== 1) {
            $errors['admin_custom_url'] = 'La URL del panel solo admite letras, números, guiones y guion bajo';
        }

        if ($errors !== []) {
            return [
                'success' => 0,
                'message' => 'Los datos enviados no son correctos',
                'errors' => $errors,
                'code' => 422,
            ];
        }

        return true;
    }
}
