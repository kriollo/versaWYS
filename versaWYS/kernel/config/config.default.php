<?php

declare(strict_types=1);

// Valores por defecto de la configuración. `config.json` solo necesita declarar lo que cambia:
// config.php los fusiona con array_replace_recursive y `versaCLI config:init` genera el
// config.json inicial a partir de este mismo array.
return [
    'framework' => 'versaWYS-PHP 2.0.0',
    'database' => [
        'engine' => 'pdo',
        'driver' => 'mysql', // mysql | postgresql
        'host' => 'localhost',
        'port' => 3306,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'database' => '',
        'username' => '',
        'password' => '',
        'log_path' => './versaWYS/logs/orm/',
    ],
    'admin' => [
        'custom_url' => 'admin',
        'hide_default' => false,
        'redirect_404' => true,
    ],
    'twig' => [
        'cache' => true,
        'compiled_dir' => './versaWYS/cache/twig/.cache',
        'templates_dir' => './app/templates',
        'strict_variables' => false,
        'auto_escape' => true,
        'default_template_404' => 'versaWYS/404',
    ],
    'build' => [
        'debug' => false,
        'name' => 'versaWYS',
        'version' => '1.0.0',
        'timezone' => 'America/Santiago',
        'locale' => 'es_CL.UTF-8',
        'charset' => 'utf-8',
        'cacheRoute' => false,
        'cacheRouteFile' => './versaWYS/cache/routes.compiled.php',
    ],
    'auth' => [
        'inactive_account_days' => 30,
        'expiration_days_password' => 90,
        'password_policy' => [
            'large' => 8,
            'uppercase' => true,
            'lowercase' => true,
            'number' => true,
            'special_chars' => true,
        ],
    ],
    'session' => [
        'key' => '', // config:init genera una clave aleatoria
        'lifetime' => 36000,
        'user_cookie' => [
            'domain' => '',
            'enable' => true,
            'key_encript' => '', // config:init genera una clave aleatoria
            'lifetime' => 36000,
            'lifetime_remember' => 2592000,
            'secure' => true,
            'http_only' => true,
            'same_site' => 'Lax',
            'strict_mode' => true,
        ],
    ],
    'api' => ['auth' => true],
    'login_attempt' => ['max' => 5, 'time' => 120],
    'mail' => [
        'host' => '',
        'port' => 587,
        'transport' => 'smtp',
        'secure' => 'tls',
        'verify_peer' => true, // false solo para SMTP interno con certificado autofirmado
        'name_from' => '',
        'username' => '',
        'password' => '',
    ],
    'cors' => ['allowed_origins' => []],
    'csp' => ['enabled' => true],
    'websocket_service' => ['ws_url' => ''],
    'logging' => [
        'dir' => './versaWYS/logs/',
        'min_level' => 'INFO',
        'default_module' => 'app',
        'use_redis' => false,
        'use_console' => false,
        'format' => 'json',
        'max_file_size' => 10485760,
        'retention_days' => 30,
        'include_stack_trace' => true,
        'timezone' => 'America/Santiago',
    ],
    'redis' => [
        'enabled' => false,
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => 0,
        'persistent' => true,
        'persistent_id' => 'versa_redis',
        'timeout' => 2.5,
        'default_expiration' => 3600,
    ],
    'rate_limiting' => [
        'enabled' => true,
        'storage' => 'file',
        'cache_dir' => './versaWYS/cache/rate_limits/',
        'cleanup_probability' => 100,
        'rules' => [
            'global' => ['requests' => 100, 'window' => 60, 'enabled' => true, 'description' => 'Límite global por IP'],
            'api' => ['requests' => 30, 'window' => 60, 'enabled' => true, 'description' => 'Límite para endpoints API'],
            'login' => ['requests' => 5, 'window' => 300, 'enabled' => true, 'description' => 'Límite para intentos de login'],
            'upload' => ['requests' => 10, 'window' => 120, 'enabled' => true, 'description' => 'Límite para subida de archivos'],
            'sensitive' => ['requests' => 3, 'window' => 600, 'enabled' => true, 'description' => 'Límite para endpoints sensibles'],
        ],
    ],
    'assets' => [
        'dashboard' => [
            'js' => ['src' => 'src/dashboard/js', 'dist' => 'public/dashboard/js'],
            'css' => ['src' => 'src/dashboard/css', 'dist' => 'public/dashboard/css'],
            'img' => ['dist' => 'public/dashboard/img'],
            'avatars' => ['dist' => 'public/dashboard/img/avatars'],
            'store' => ['dist' => 'public/dashboard/store'],
            'temp' => ['dist' => 'public/temp'],
        ],
    ],
];
