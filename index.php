<?php

use versaWYS\kernel\Router;

require_once 'versaWYS/kernel/config/config.php';

// Manejo de errores global
set_exception_handler(function ($e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'Error interno del servidor';
});

// Cabeceras de seguridad adicionales
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: no-referrer');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

// Inicio de la aplicación
try {
    (new Router())->resolve();
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo 'Error interno del servidor';
}
