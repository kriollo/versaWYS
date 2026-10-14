<?php

declare(strict_types=1);

/**
 * Router Script para el servidor de desarrollo PHP integrado (php -S)
 * Este script maneja las reescrituras de URL y bloquea acceso a carpetas sensibles
 */

// Obtener la URI solicitada
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Directorio raíz del proyecto: el que sirve `php -S -t` (el kernel puede vivir en vendor)
$projectRoot = $_SERVER['DOCUMENT_ROOT'] . '/';

// Lista de carpetas sensibles que NO deben ser accesibles directamente
$blockedDirectories = ['/app/', '/versaWYS/', '/vendor/', '/logs/', '/src/', '/configs/', '/scripts/', '/documentacion/'];

// El frontend compilado importa Vue y otras librerías desde /node_modules/<paquete>/dist/.
// De node_modules solo se sirven esos módulos JS y CSS; nada más (package.json, fuentes, binarios).
if (
    str_starts_with(strtolower($uri), '/node_modules/')
    && preg_match('#^/node_modules/[\w@.\-/]+\.(js|mjs|css)$#i', $uri) !== 1
) {
    require_once $projectRoot . 'index.php';
    return true;
}

// Verificar si la URI comienza con una carpeta bloqueada
foreach ($blockedDirectories as $blockedDir) {
    if (!str_starts_with(strtolower($uri), strtolower($blockedDir))) {
        continue;
    }

    require_once $projectRoot . 'index.php';
    return true;
}

// Archivos y patrones que deben ser bloqueados
$blockedPatterns = [
    '/\.env',
    '/\.git',
    '/\.htaccess',
    '/composer\.json',
    '/composer\.lock',
    '/package\.json',
    '/package-lock\.json',
    '/pnpm-lock\.yaml',
    '/\.gitignore',
    '/\.editorconfig',
    '/README\.md',
];

foreach ($blockedPatterns as $pattern) {
    if (!preg_match('#' . $pattern . '#', $uri)) {
        continue;
    }

    http_response_code(404);
    echo '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1></body></html>';
    return true;
}

// Si el archivo existe y es un archivo estático permitido, servirlo directamente
if ($uri !== '/' && file_exists($projectRoot . $uri)) {
    // Extensiones permitidas para servir directamente
    $allowedExtensions = [
        'css',
        'js',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'svg',
        'ico',
        'webp',
        'woff',
        'woff2',
        'ttf',
        'otf',
        'eot',
        'pdf',
        'mp4',
        'webm',
        'mp3',
        'wav',
        'json',
        'xml',
        'txt',
    ];

    $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));

    if (in_array($extension, $allowedExtensions, true)) {
        // Dejar que PHP sirva el archivo
        return false;
    }
}

// Para todo lo demás (rutas de la aplicación, 404, etc.), pasar por el router del framework
require_once $projectRoot . 'index.php';
return true;
