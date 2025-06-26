<?php

declare(strict_types=1);

/**
 * Archivo de inicialización del manejo mejorado de errores para versaWYS
 * Incluir este archivo al inicio de tu aplicación (en index.php o autoload.php)
 */

use versaWYS\kernel\ErrorHandler;

// Activar el manejo mejorado de errores solo si no está ya inicializado
if (class_exists('versaWYS\kernel\ErrorHandler')) {
    ErrorHandler::initialize();
}

// Configurar el reporte de errores según el modo debug
global $config;

if (isset($config['build']['debug']) && $config['build']['debug']) {
    // Modo debug: mostrar todos los errores
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    // Modo producción: ocultar errores del usuario pero loggearlos
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}
