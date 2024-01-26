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
        $config = json_decode($config, true);
        return $config;
    }

    public static function saveConfig(array $config): void
    {
        $config = json_encode($config, JSON_PRETTY_PRINT);
        file_put_contents(self::$pathConfig, $config);
    }

    public static function setDebug($params)
    {
        if (!isset($params)) {
            echo "Error: Debe especificar un valor para debug\n";
            exit;
        }

        if (!in_array($params, [true, false])) {
            echo "Error: El valor de debug debe ser true o false\n";
            exit;
        }

        $config = self::getConfig();
        $config['build']['debug'] = $params === 'true' ? true : false;
        self::saveConfig($config);
    }

    public static function setTemplateCache($params)
    {
        if (!isset($params)) {
            echo "Error: Debe especificar un valor para templateCache\n";
            exit;
        }

        if (!in_array($params, ['true', 'false'])) {
            echo "Error: El valor de templateCache debe ser true o false\n";
            exit;
        }

        $config = self::getConfig();
        $config['twig']['cache'] = $params === 'true' ? true : false;
        self::saveConfig($config);
    }

    public static function clearCache()
    {
        $config = self::getConfig();

        $pathCahe = $config['twig']['compiled_dir'];

        helpers\FilesFunction::DestroyAllDir($pathCahe);


        if (!is_dir($pathCahe))
            mkdir($pathCahe, 0777, true);

        echo "Cache eliminado\n";
    }
}
