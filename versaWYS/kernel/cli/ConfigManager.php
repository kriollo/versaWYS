<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use versaWYS\kernel\Helpers as helpers;

class ConfigManager
{
    protected static string $pathConfig = 'versaWYS/kernel/config/config.json';

    public static function getConfig(): array
    {
        $config = file_get_contents(self::$pathConfig);
        return json_decode($config, true);
    }

    public static function saveConfig(array $config): void
    {
        $config = json_encode($config, JSON_PRETTY_PRINT);
        file_put_contents(self::$pathConfig, $config);
    }

    public static function setDebug($params): void
    {
        if (!isset($params)) {
            echo "Error: Debe especificar un valor para debug\n";
            exit();
        }

        if (!in_array($params, [true, false])) {
            echo "Error: El valor de debug debe ser true o false\n";
            exit();
        }

        $config = self::getConfig();
        $config['build']['debug'] = $params === 'true';
        self::saveConfig($config);
    }

    public static function setTemplateCache($params): void
    {
        if (!isset($params)) {
            echo "Error: Debe especificar un valor para templateCache\n";
            exit();
        }

        if (!in_array($params, ['true', 'false'])) {
            echo "Error: El valor de templateCache debe ser true o false\n";
            exit();
        }

        $config = self::getConfig();
        $config['twig']['cache'] = $params === 'true';
        self::saveConfig($config);
    }

    public static function clearCache(): void
    {
        $config = self::getConfig();

        $pathCahe = $config['twig']['compiled_dir'];

        helpers\FilesFunction::destroyAllDir($pathCahe);

        if (!is_dir($pathCahe)) {
            mkdir($pathCahe, 0777, true);
        }

        echo "Cache eliminado\n";
    }

    public static function initConfig(): void
    {
        if (file_exists(self::$pathConfig)) {
            echo "El archivo de configuración ya existe en: " . self::$pathConfig . "\n";
            return;
        }

        $template = <<<'EOT'
{
    "framework": "versaWYS-PHP 1.0.0",
    "DB": {
        "DB_HOST": "localhost",
        "DB_USER": "root",
        "DB_PASS": "",
        "DB_NAME": "versademo"
    },
    "twig": {
        "cache": false,
        "compiled_dir": "./app/templates/.cache",
        "templates_dir": "./app/templates",
        "strict_variables": true,
        "auto_escape": true,
        "default_template_404": "versaWYS/404"
    },
    "build": {
        "debug": true,
        "name": "VERSAWYS-PHP-APP",
        "version": "0.0.1",
        "timezone": "America/Santiago",
        "charset": "utf-8"
    },
    "auth": {
        "inactive_account_days": 30,
        "expiration_days_password": 90,
        "password_policy": {
            "large": 8,
            "uppercase": true,
            "lowercase": true,
            "number": true,
            "special_chars": true
        }
    },
    "session": {
        "key": "VERSAWYS.NEW",
        "lifetime": 36000,
        "user_cookie": {
            "domain": "localhost",
            "enable": true,
            "key_encript": "GenerateANewKeyHere",
            "lifetime": 36000,
            "lifetime_remember": 2592000,
            "secure": false,
            "http_only": false
        }
    },
    "api": {
        "auth": true
    },
    "login_attempt": {
        "max": 5,
        "time": 120
    },
    "mail": {
        "host": "",
        "port": 25,
        "transport": "smtp",
        "secure": "tls",
        "name_from": "MyApp",
        "username": "",
        "password": ""
    },
    "assets": {
        "dashboard": {
            "js": {
                "src": "src/dashboard/js",
                "dist": "public/dashboard/js"
            },
            "css": {
                "src": "src/dashboard/css",
                "dist": "public/dashboard/css"
            },
            "img": {
                "dist": "public/dashboard/img"
            },
            "avatars": {
                "dist": "public/dashboard/img/avatars"
            },
            "store": {
                "dist": "public/dashboard/store"
            },
            "temp": {
                "dist": "public/temp"
            }
        }
    }
}
EOT;

        // Asegurarse de que el directorio de configuración exista
        $configDir = dirname(self::$pathConfig);
        if (!is_dir($configDir)) {
            if (!mkdir($configDir, 0777, true) && !is_dir($configDir)) {
                echo "Error: No se pudo crear el directorio de configuración: " . $configDir . "\n";
                return;
            }
        }

        if (file_put_contents(self::$pathConfig, $template) !== false) {
            echo "Archivo de configuración creado exitosamente en: " . self::$pathConfig . "\n";
        } else {
            echo "Error: No se pudo crear el archivo de configuración en: " . self::$pathConfig . "\n";
        }
    }
}
