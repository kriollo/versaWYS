<?php

declare(strict_types=1);

namespace app\controllers;

use app\services\MailService;
use versaWYS\kernel\cli\ConfigManager;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\helpers\Functions;

class ConfigSettingsController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    /**
     * Renders the index page for the ConfigSettingsController.
     *
     * @return string the rendered template for the index page
     */
    public function index(): string
    {
        return $this->template->render('dashboard/loader', [
            'm' => '/dashboard/js/configSettings/dashConfigSettings',
        ]);
    }

    /**
     * Devuelve el subconjunto editable (whitelisted) de la configuración de la app.
     * mail.password nunca se devuelve al frontend.
     */
    public function getSettings(): array
    {
        $config = ConfigManager::getConfig();

        return [
            'success' => 1,
            'data' => [
                'mail' => [
                    'host' => $config['mail']['host'] ?? '',
                    'port' => (int) ($config['mail']['port'] ?? 0),
                    'transport' => $config['mail']['transport'] ?? '',
                    'secure' => $config['mail']['secure'] ?? '',
                    'name_from' => $config['mail']['name_from'] ?? '',
                    'username' => $config['mail']['username'] ?? '',
                    'password' => '',
                ],
                'login_attempt' => [
                    'max' => (int) ($config['login_attempt']['max'] ?? 5),
                    'time' => (int) ($config['login_attempt']['time'] ?? 120),
                ],
                'auth' => [
                    'expiration_days_password' => (int) ($config['auth']['expiration_days_password'] ?? 90),
                    'inactive_account_days' => (int) ($config['auth']['inactive_account_days'] ?? 30),
                    'password_policy' => [
                        'large' => (int) ($config['auth']['password_policy']['large'] ?? 8),
                        'uppercase' => (bool) ($config['auth']['password_policy']['uppercase'] ?? true),
                        'lowercase' => (bool) ($config['auth']['password_policy']['lowercase'] ?? true),
                        'number' => (bool) ($config['auth']['password_policy']['number'] ?? true),
                        'special_chars' => (bool) ($config['auth']['password_policy']['special_chars'] ?? true),
                    ],
                ],
                'build' => [
                    'name' => $config['build']['name'] ?? '',
                    'version' => $config['build']['version'] ?? '',
                    'timezone' => $config['build']['timezone'] ?? '',
                    'charset' => $config['build']['charset'] ?? '',
                    'debug' => (bool) ($config['build']['debug'] ?? false),
                ],
                'admin' => [
                    'custom_url' => $config['admin']['custom_url'] ?? '',
                    'hide_default' => (bool) ($config['admin']['hide_default'] ?? false),
                    'redirect_404' => (bool) ($config['admin']['redirect_404'] ?? false),
                ],
            ],
        ];
    }

    /**
     * Guarda el subconjunto editable de la configuración, mezclándolo sobre el config.json actual.
     * Las claves no whitelisted (database, session, twig, redis, rate_limiting, assets, sp) no se tocan.
     * mail.password solo se sobrescribe si llega no vacío; en blanco conserva el valor actual.
     */
    public function saveSettings(): array
    {
        global $request;

        $params = $request->getAllParams();
        $config = ConfigManager::getConfig();

        $config['mail']['host'] = (string) $params['mail']['host'];
        $config['mail']['port'] = (int) $params['mail']['port'];
        $config['mail']['transport'] = (string) $params['mail']['transport'];
        $config['mail']['secure'] = (string) $params['mail']['secure'];
        $config['mail']['name_from'] = (string) $params['mail']['name_from'];
        $config['mail']['username'] = (string) $params['mail']['username'];
        // Sin sanear: la contraseña SMTP debe guardarse exacta
        $mailPassword = (string) ($request->getRawParams()['mail']['password'] ?? '');
        if ($mailPassword !== '') {
            $config['mail']['password'] = $mailPassword;
        }

        $config['login_attempt']['max'] = (int) $params['login_attempt']['max'];
        $config['login_attempt']['time'] = (int) $params['login_attempt']['time'];

        $config['auth']['expiration_days_password'] = (int) $params['auth']['expiration_days_password'];
        $config['auth']['inactive_account_days'] = (int) $params['auth']['inactive_account_days'];
        $config['auth']['password_policy']['large'] = (int) $params['auth']['password_policy']['large'];
        $config['auth']['password_policy']['uppercase'] = $this->toBool(
            $params['auth']['password_policy']['uppercase'] ?? false,
        );
        $config['auth']['password_policy']['lowercase'] = $this->toBool(
            $params['auth']['password_policy']['lowercase'] ?? false,
        );
        $config['auth']['password_policy']['number'] = $this->toBool(
            $params['auth']['password_policy']['number'] ?? false,
        );
        $config['auth']['password_policy']['special_chars'] = $this->toBool(
            $params['auth']['password_policy']['special_chars'] ?? false,
        );

        $config['build']['name'] = (string) $params['build']['name'];
        $config['build']['version'] = (string) $params['build']['version'];
        $config['build']['timezone'] = (string) $params['build']['timezone'];
        $config['build']['charset'] = (string) $params['build']['charset'];

        $config['admin']['custom_url'] = (string) $params['admin']['custom_url'];
        $config['admin']['hide_default'] = $this->toBool($params['admin']['hide_default'] ?? false);
        $config['admin']['redirect_404'] = $this->toBool($params['admin']['redirect_404'] ?? false);

        ConfigManager::saveConfig($config);

        return ['success' => 1, 'message' => 'Configuración guardada correctamente'];
    }

    /**
     * Envía un correo de prueba al usuario en sesión con la configuración guardada.
     */
    public function testMail(): array
    {
        $sent = (new MailService())->enviar(
            ['email' => $this->user['email'], 'name' => $this->user['name']],
            'Correo de prueba',
            '<p>La configuración de correo saliente funciona correctamente.</p>',
        );

        return $sent
            ? ['success' => 1, 'message' => 'Correo de prueba enviado a ' . $this->user['email']]
            : ['success' => 0, 'message' => 'No se pudo enviar el correo de prueba. Revise el log de correo.', 'code' => 502];
    }

    private function toBool(mixed $value): bool
    {
        return in_array($value, [true, 'true', 1, '1'], true);
    }
}
