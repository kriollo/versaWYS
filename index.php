<?php

// Permitir que el grupo tenga permisos de escritura en todos los archivos/dirs que PHP cree
umask(0002);

use versaWYS\kernel\Router;

require_once __DIR__ . '/versaWYS/kernel/config/config.php';

// Inicializar el manejo mejorado de errores
require_once __DIR__ . '/versaWYS/kernel/init_error_handling.php';

// Cabeceras de seguridad adicionales
require_once __DIR__ . '/versaWYS/kernel/init_headers.php';

// Inicio de la aplicación
(new Router())->resolve();
