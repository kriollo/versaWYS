<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'versaWYS/kernel/autoload.php';

use versaWYS\kernel\Cookie;
use versaWYS\kernel\Request;
use versaWYS\kernel\Session;
use versaWYS\kernel\versaTwig;

$configPath = __DIR__ . '/config.json';
if (!file_exists($configPath)) {
    throw new Exception("Configuration file not found: $configPath");
}

$config = json_decode(file_get_contents($configPath), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('Error parsing configuration file: ' . json_last_error_msg());
}

// TODO: Crear funcionamiento para el caché de rutas, crear comando en versaCLI para activar o desactivar el caché de rutas, crear comando en versaCLI para limpiar el caché de rutas, crear comando en versaCLI para mostrar el estado del caché de rutas
// TODO: crear logica de carga de rutas, si el caché de rutas esta activado, cargar las rutas desde el caché, si no esta activado, cargar las rutas desde los archivos de rutas

registerRoutes('app/routes');

$session = new Session($config['session']['lifetime']);

$twig = new versaTwig($config);
$twig->addGlobal('session', $session);
$twig->addGlobal('config', $config);

ini_set('display_errors', $config['build']['debug'] ? '1' : '0');
ini_set('display_startup_errors', $config['build']['debug'] ? '1' : '0');
$cookie = new Cookie();

$debug = $config['build']['debug'] ?? false;

if ($config['build']['debug']) {
    $cookie->set('debug', '1', 0, $config['session']['user_cookie']['domain'], false, false);
    set_error_handler('versaWYS\kernel\versaTwig::errorHandler', E_ALL);
    error_reporting(E_ALL);
} else {
    $cookie->delete('debug');
}

date_default_timezone_set($config['build']['timezone']);
setlocale(LC_ALL, 'es_CL.UTF-8');
setlocale(LC_TIME, 'es_CL.UTF-8');

ini_set('memory_limit', '-1');
ini_set('max_execution_time', '0');

mb_internal_encoding($config['build']['charset']);
mb_http_output($config['build']['charset']);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, X-Frame-Options');
header('Access-Control-Allow-Credentials: true');
header('X-Frame-Options: SAMEORIGIN');
header('Access-Control-Max-Age: 86400');
header('X-Powered-By: none');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

$request = new Request();
