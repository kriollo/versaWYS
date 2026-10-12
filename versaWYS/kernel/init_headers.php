<?php

declare(strict_types=1);

global $config;

// CORS: solo se responde a los orígenes listados en config `cors.allowed_origins`.
// Las peticiones same-origin no envían cabecera Origin y no necesitan CORS.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && in_array($origin, $config['cors']['allowed_origins'] ?? [], strict: true)) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF-Token, X-SPA');
    header('Access-Control-Max-Age: 86400');
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$host = explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0];
$isLocalhost = in_array($host, ['localhost', '127.0.0.1'], strict: true);

// HSTS solo fuera de localhost para no forzar HTTPS en desarrollo
if (!$isLocalhost) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// CSP: cada directiva acepta orígenes extra desde config `csp.<directiva>` (array de strings).
// Solo en producción: en debug el HMR y la barra de depuración necesitan orígenes locales.
if (!($config['build']['debug'] ?? false) && ($config['csp']['enabled'] ?? true)) {
    $csp = $config['csp'] ?? [];
    $extra = static fn (string $directive): string => implode(' ', (array) ($csp[$directive] ?? []));

    // ponytail: 'unsafe-inline' y 'unsafe-eval' siguen porque los templates usan scripts inline
    // y el runtime de Vue compila plantillas; quitar cuando se migre a nonces y build precompilado.
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$extra('script-src')}; "
        . "style-src 'self' 'unsafe-inline' fonts.googleapis.com {$extra('style-src')}; "
        . "font-src 'self' fonts.gstatic.com {$extra('font-src')}; "
        . "img-src 'self' data: blob: https: {$extra('img-src')}; "
        . "media-src 'self' blob: data: {$extra('media-src')}; "
        . "connect-src 'self' blob: {$extra('connect-src')}; "
        . "frame-src 'self' {$extra('frame-src')}; "
        . "frame-ancestors 'self'; "
        . "form-action 'self' {$extra('form-action')}; "
        . "base-uri 'self'",
    );
}

header_remove('Server');
header_remove('X-Powered-By');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit();
}
