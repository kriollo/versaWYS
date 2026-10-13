<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../autoload.php';

use VersaORM\VersaModel;
use versaWYS\kernel\Cookie;
use versaWYS\kernel\Loggin;
use versaWYS\kernel\model\OrmFactory;
use versaWYS\kernel\Request;
use versaWYS\kernel\Session;
use versaWYS\kernel\versaTwig;

$configPath = __DIR__ . '/config.json';
if (!file_exists($configPath)) {
    throw new Exception('Configuration file not found: ' . $configPath);
}

$config = json_decode(file_get_contents($configPath), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('Error parsing configuration file: ' . json_last_error_msg());
}
$config = array_replace_recursive(require __DIR__ . '/config.default.php', $config);

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    Loggin::error('Error inicializando VersaORM: La versión de PHP debe ser 8.3 o superior.', 'VersaWYS');
    throw new Exception('La versión de PHP debe ser 8.3 o superior. VersaWYS no puede continuar.');
}
if (!extension_loaded('intl')) {
    Loggin::error('Error inicializando VersaORM: La extensión PHP "intl" no está cargada.', 'VersaWYS');
    throw new Exception('La extensión PHP "intl" no está cargada. Por favor, instálala para continuar.');
}

registerRoutes('app/routes');

$session = new Session($config['session']['lifetime']);

$twig = new versaTwig($config);
$twig->addGlobal('session', $session);
$twig->addGlobal('config', $config);

// Cargar variables de entorno desde .env si existe en la raíz
$envPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

ini_set('display_errors', $config['build']['debug'] ? '1' : '0');
ini_set('display_startup_errors', $config['build']['debug'] ? '1' : '0');
$cookie = new Cookie();

$debug = $config['build']['debug'] ?? false;

if ($config['build']['debug']) {
    // $cookie->set('debug', '1', 0, $config['session']['user_cookie']['domain'], false, false, 'None', false);

    $cookie->set(
        'debug',
        '1',
        0,
        $config['session']['user_cookie']['domain'],
        $config['session']['user_cookie']['secure'],
        $config['session']['user_cookie']['http_only'],
    );
    set_error_handler(versaTwig::class . '::errorHandler', E_ALL);
    error_reporting(E_ALL);
} else {
    $cookie->delete('debug');
}

date_default_timezone_set($config['build']['timezone']);
setlocale(LC_ALL, $config['build']['locale']);

mb_internal_encoding($config['build']['charset']);
mb_http_output($config['build']['charset']);

// Sin base de datos configurada (instalación pendiente) no se intenta conectar: /install debe poder abrirse
if (($config['database']['database'] ?? '') !== '') {
    try {
        global $versaOrm;
        $versaOrm = OrmFactory::make(
            $config
            + [
                'versaorm' => [
                    'debug' => $debug,
                ],
            ],
        );
        $versaOrm->setTimezone($config['build']['timezone']);
        VersaModel::setORM($versaOrm);
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        Loggin::error('Error inicializando VersaORM: ' . $errorMsg, 'VersaORM');

        if ($debug) {
            // En modo debug, usar plantilla especializada de error de BD del framework
            try {
                $errorData = [
                    'type' => 'Database Configuration Error',
                    'message' => $errorMsg,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'real_file' => 'versaWYS/kernel/config/config.json',
                    'context' => [
                        'framework' => $config['framework'] ?? 'versaWYS-PHP',
                        'mode' => 'Debug (desarrollo)',
                        'database_config' => [
                            'driver' => $config['database']['driver'] ?? 'unknown',
                            'host' => $config['database']['host'] ?? 'unknown',
                            'port' => $config['database']['port'] ?? 'unknown',
                            'database' => $config['database']['database'] ?? 'unknown',
                        ],
                    ],
                ];

                http_response_code(500);
                echo $twig->render('versaWYS/databaseError.twig', ['data' => $errorData]);
                exit(1);
            } catch (Exception $twigError) {
                // Si falla Twig, usar formato simple HTML
                http_response_code(500);
                echo
                    sprintf(
                        "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error</title></head>"
                        . "<body style='font-family:sans-serif;padding:20px;'>"
                        . '<h1>⚠️ Error de Base de Datos</h1>'
                        . '<p><strong>Mensaje:</strong> %s</p>'
                        . '<p><strong>Framework:</strong> %s</p>'
                        . "<hr><p style='color:#666;font-size:12px;'>También falló la renderización de la plantilla de error: %s</p>"
                        . '</body></html>',
                        htmlspecialchars($errorMsg),
                        htmlspecialchars($config['framework'] ?? 'versaWYS-PHP'),
                        htmlspecialchars($twigError->getMessage()),
                    )
                ;
                exit(1);
            }
        }

        // En producción, continuar sin BD (para páginas que no la necesiten)
    }
}

$request = new Request();
