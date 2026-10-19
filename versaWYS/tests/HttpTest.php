<?php

declare(strict_types=1);

use versaWYS\kernel\helpers\Functions;

/*
 * Pruebas de extremo a extremo: levantan la aplicación real con `php -S` contra una base SQLite
 * desechable y le hacen peticiones HTTP. Cubren lo que un test unitario no ve: cabeceras,
 * cookies, sesión, tokens y la forma de los errores. No tocan config.json: la configuración
 * de prueba se pasa con la variable de entorno VERSAWYS_CONFIG.
 */

const HTTP_TEST_PASSWORD = 'Clave#Segura2026';

/** Estado compartido de la suite (directorio temporal, servidor, URL base). */
function httpEnv(?array $set = null): array
{
    static $env = [];
    if ($set !== null) {
        $env = $set;
    }

    return $env;
}

function httpDb(): PDO
{
    return new PDO('sqlite:' . httpEnv()['dir'] . '/test.sqlite', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
}

/**
 * Petición HTTP. $jar es el "navegador" (archivo de cookies); null = sin cookies.
 *
 * @return array{status: int, headers: string, body: string, json: mixed}
 */
function http(string $method, string $path, ?string $jar = null, array $headers = [], array|string|null $body = null): array
{
    $ch = curl_init(httpEnv()['url'] . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($jar !== null) {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
    }
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw = (string) curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    unset($ch); // al destruirse escribe el archivo de cookies

    $content = substr($raw, $headerSize);

    return ['status' => $status, 'headers' => substr($raw, 0, $headerSize), 'body' => $content, 'json' => json_decode($content, true)];
}

/** Petición como la hace el frontend (fetch): espera JSON. */
function api(string $method, string $path, ?string $jar = null, array|string|null $body = null, array $headers = []): array
{
    return http($method, $path, $jar, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json', ...$headers], $body);
}

function newJar(): string
{
    return httpEnv()['dir'] . '/jar_' . bin2hex(random_bytes(4));
}

/** Token CSRF del formulario que trae la página. */
function csrfFrom(string $page, string $jar): string
{
    preg_match('/id="csrf_token" value="([^"]+)"/', http('GET', $page, $jar)['body'], $m);

    return $m[1] ?? '';
}

function cookieValue(string $jar, string $name): string
{
    preg_match('/\t' . preg_quote($name, '/') . '\t(\S+)/', (string) file_get_contents($jar), $m);

    return $m[1] ?? '';
}

/** Inicia sesión y devuelve [archivo de cookies, token CSRF de la sesión]. */
function loginAs(string $email): array
{
    $jar = newJar();
    $login = api('POST', '/admin/login/autentication', $jar, [
        '_csrf_token' => csrfFrom('/admin/login', $jar),
        'email' => $email,
        'password' => HTTP_TEST_PASSWORD,
    ]);
    expect($login['json']['success'] ?? 0)->toBe(1);

    return [$jar, csrfFrom('/admin/dashboard', $jar)];
}

beforeAll(function (): void {
    $dir = sys_get_temp_dir() . '/versawys_http_' . getmypid();
    @mkdir($dir, 0o700, true);

    $config = versaWYS\kernel\cli\ConfigManager::defaults();
    $config['database'] = ['driver' => 'sqlite', 'database' => $dir . '/test.sqlite', 'log_path' => $dir . '/orm/'] + $config['database'];
    $config['session']['key'] = bin2hex(random_bytes(32));
    $config['session']['user_cookie']['secure'] = false; // el servidor de prueba es HTTP
    // Caché de Twig como en producción, en una carpeta propia de esta ejecución: nunca plantillas viejas
    $config['twig']['compiled_dir'] = $dir . '/twig';
    $config['csp']['nonce'] = true;
    $config['csp']['unsafe_eval'] = false; // como una instalación nueva (config:init)
    $config['websocket_service']['ws_url'] = 'ws://localhost:8083/ws';
    $config['logging']['dir'] = $dir . '/logs/';
    $config['rate_limiting']['cache_dir'] = $dir . '/rate_limits/';
    // Toda la suite sale de una misma IP: solo el límite de login se deja al alcance de un test
    foreach (['global', 'api', 'upload', 'sensitive'] as $rule) {
        $config['rate_limiting']['rules'][$rule]['requests'] = 100000;
    }
    $config['rate_limiting']['rules']['login']['requests'] = 30;
    file_put_contents($dir . '/config.json', json_encode($config));

    // El de las demás suites (TestDatabase), que corren en este mismo proceso: se restaura al final
    $previousConfig = getenv('VERSAWYS_CONFIG');
    putenv('VERSAWYS_CONFIG=' . $dir . '/config.json');
    exec('php versaCLI migrate:up 2>&1 < /dev/null && php versaCLI seeder:runAll 2>&1 < /dev/null', $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException("No se pudo preparar la base de prueba:\n" . implode("\n", $output));
    }

    httpEnv(['dir' => $dir]);
    $insert = httpDb()->prepare(
        'INSERT INTO versa_users (id_empresa, tokenid, name, email, password, expiration_pass, role, status, id_perfil)
         VALUES (1, ?, ?, ?, ?, ?, ?, 1, 1)',
    );
    foreach ([['Admin', 'admin@test.cl', 'superadmin'], ['Usuario', 'user@test.cl', 'user']] as [$name, $email, $role]) {
        $insert->execute([bin2hex(random_bytes(16)), $name, $email, Functions::hash(HTTP_TEST_PASSWORD), date('Y-m-d', strtotime('+90 days')), $role]);
    }

    // Puerto libre: se pide uno al sistema y se libera para el servidor
    $probe = stream_socket_server('tcp://127.0.0.1:0');
    $port = (int) substr(strrchr((string) stream_socket_get_name($probe, false), ':'), 1);
    fclose($probe);

    $server = proc_open(
        // Sin Xdebug (si está instalado): producción no lo tiene y deforma los tiempos
        ['php', '-d', 'xdebug.mode=off', '-S', '127.0.0.1:' . $port, '-t', '.', dirname((string) (new ReflectionClass(versaWYS\kernel\cli\ServerManager::class))->getFileName()) . '/routerServe.php'],
        [['file', '/dev/null', 'r'], ['file', $dir . '/server.log', 'a'], ['file', $dir . '/server.log', 'a']],
        $pipes,
    );
    for ($i = 0; $i < 50 && @fsockopen('127.0.0.1', $port) === false; $i++) {
        usleep(100_000);
    }

    httpEnv(['dir' => $dir, 'url' => 'http://127.0.0.1:' . $port, 'server' => $server, 'previous_config' => $previousConfig]);
});

afterAll(function (): void {
    $env = httpEnv();
    proc_terminate($env['server']);
    proc_close($env['server']);
    putenv($env['previous_config'] === false ? 'VERSAWYS_CONFIG' : 'VERSAWYS_CONFIG=' . $env['previous_config']);

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($env['dir'], FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($env['dir']);
});

describe('respuestas y cabeceras', function (): void {
    it('envía las cabeceras de seguridad y no revela la tecnología', function (): void {
        $res = http('GET', '/admin/login');

        expect($res['status'])->toBe(200)
            ->and($res['headers'])->toContain('X-Frame-Options: SAMEORIGIN')
            ->toContain('X-Content-Type-Options: nosniff')
            ->toContain('Referrer-Policy:')
            ->toContain("Content-Security-Policy: default-src 'self'")
            ->toContain('X-XSS-Protection: 0')
            ->toContain('Permissions-Policy: camera=(self), microphone=(self), geolocation=(self), payment=()')
            ->not->toContain('X-Powered-By');
    });

    it('con nonce, script-src no permite scripts inline sin nonce', function (): void {
        preg_match('/^Content-Security-Policy:.*$/mi', http('GET', '/admin/login')['headers'], $m);
        preg_match("/script-src [^;]*/", $m[0] ?? '', $scriptSrc);

        expect($scriptSrc[0] ?? '')->toContain("'nonce-")->not->toContain("'unsafe-inline'");
    });

    it('con csp.unsafe_eval en false la CSP no permite eval', function (): void {
        preg_match('/^Content-Security-Policy:.*$/mi', http('GET', '/admin/login')['headers'], $m);

        expect($m[0] ?? '')->toContain('script-src')->not->toContain('unsafe-eval');
    });

    it('la CSP permite conectarse al websocket configurado', function (): void {
        preg_match('/^Content-Security-Policy:.*$/mi', http('GET', '/admin/login')['headers'], $m);

        expect($m[0] ?? '')->toMatch("~connect-src 'self' blob: ws://localhost:8083[ ;]~");
    });

    it('la cookie de sesión es HttpOnly y SameSite', function (): void {
        preg_match('/^Set-Cookie: PHPSESSID=.*$/mi', http('GET', '/admin/login')['headers'], $m);

        expect($m[0] ?? '')->toContain('HttpOnly')->toContain('SameSite=');
    });

    it('no sirve archivos internos', function (string $path): void {
        expect(http('GET', $path)['status'])->toBeIn([403, 404]);
    })->with([
        '/versaWYS/kernel/config/config.json',
        '/versaWYS/config/config.json',
        '/versaWYS/kernel/config/config.default.php',
        '/versaWYS/composer.json',
        '/app/routes/UsersRoutes.php',
        '/.env',
        '/.git/config',
        '/versaCLI',
    ]);

    it('una página inexistente responde 404 en HTML', function (): void {
        $res = http('GET', '/no-existe');

        expect($res['status'])->toBe(404)->and($res['body'])->toContain('<html');
    });

    it('un endpoint inexistente responde 404 en JSON, sin detalles internos', function (): void {
        $res = api('GET', '/admin/api/no-existe');

        expect($res['status'])->toBe(404)
            ->and($res['json'])->toBe(['success' => 0, 'message' => 'Recurso no encontrado']);
    });

    it('un método no permitido responde 405', function (): void {
        expect(http('GET', '/admin/logout')['status'])->toBe(405);
    });
});

describe('sesión y tokens', function (): void {
    it('sin sesión, una página redirige al login y un endpoint responde 401', function (): void {
        $page = http('GET', '/admin/dashboard');
        $endpoint = api('GET', '/admin/users/getUsersPaginated');

        expect($page['status'])->toBe(302)
            ->and($page['headers'])->toContain('Location: /admin/login')
            ->and($endpoint['status'])->toBe(401)
            ->and($endpoint['json']['success'])->toBe(0);
    });

    it('rechaza el login sin token CSRF', function (): void {
        $res = api('POST', '/admin/login/autentication', newJar(), ['email' => 'admin@test.cl', 'password' => HTTP_TEST_PASSWORD]);

        expect($res['status'])->toBe(403);
    });

    it('responde igual si falla la clave o si el correo no existe', function (): void {
        $jar = newJar();
        $csrf = csrfFrom('/admin/login', $jar);
        $attempt = static fn (string $email): array => api('POST', '/admin/login/autentication', $jar, [
            '_csrf_token' => $csrf, 'email' => $email, 'password' => 'Incorrecta#2026',
        ]);
        $wrongPassword = $attempt('admin@test.cl');
        $unknownEmail = $attempt('nadie@test.cl');

        expect($wrongPassword['status'])->toBe(401)
            ->and($unknownEmail['status'])->toBe(401)
            ->and($unknownEmail['body'])->toBe($wrongPassword['body']);
    });

    it('al iniciar sesión regenera el id de sesión, rota el CSRF y protege la cookie del token', function (): void {
        $jar = newJar();
        $csrfBefore = csrfFrom('/admin/login', $jar);
        $sessionBefore = cookieValue($jar, 'PHPSESSID');

        $login = api('POST', '/admin/login/autentication', $jar, [
            '_csrf_token' => $csrfBefore, 'email' => 'admin@test.cl', 'password' => HTTP_TEST_PASSWORD,
        ]);
        preg_match('/^Set-Cookie: tknHash=.*$/mi', $login['headers'], $tokenCookie);

        expect($login['json']['success'])->toBe(1)
            ->and($tokenCookie[0] ?? '')->toContain('HttpOnly')->toContain('SameSite=Strict')
            ->and(cookieValue($jar, 'PHPSESSID'))->not->toBe($sessionBefore)
            ->and(csrfFrom('/admin/dashboard', $jar))->not->toBe($csrfBefore)->not->toBe('');
    });

    it('las páginas no exponen el hash de la contraseña', function (): void {
        [$jar] = loginAs('admin@test.cl');
        $hash = (string) httpDb()->query("SELECT password FROM versa_users WHERE email = 'admin@test.cl'")->fetchColumn();

        expect(http('GET', '/admin/dashboard', $jar)['body'])->not->toContain($hash)->not->toContain('restore_token')
            ->and(api('GET', '/admin/users/getUsersPaginated?page=1&per_page=10', $jar)['body'])->not->toContain($hash);
    });

    it('rechaza un token JWT manipulado', function (): void {
        [$jar] = loginAs('admin@test.cl');
        // Se altera un carácter interior de la firma: los últimos no usan todos sus bits y
        // cambiarlos puede dejar la firma intacta
        $forged = cookieValue($jar, 'tknHash');
        $forged[-10] = $forged[-10] === 'A' ? 'B' : 'A';

        $res = api('GET', '/admin/users/getUsersPaginated', null, null, ['Cookie: tknHash=' . $forged]);

        expect($res['status'])->toBe(401);
    });

    it('el logout invalida el token: una copia de las cookies deja de servir', function (): void {
        [$jar, $csrf] = loginAs('admin@test.cl');
        $stolen = newJar();
        copy($jar, $stolen);

        expect(api('GET', '/admin/users/getUsersPaginated?page=1', $stolen)['status'])->toBe(200);

        http('POST', '/admin/logout', $jar, [], ['_csrf_token' => $csrf]);

        expect(api('GET', '/admin/users/getUsersPaginated?page=1', $stolen)['status'])->toBe(401);
    });
});

describe('peticiones y errores controlados', function (): void {
    it('una petición que modifica datos exige el token CSRF', function (): void {
        [$jar] = loginAs('admin@test.cl');

        $res = api('POST', '/admin/users/addUser', $jar, ['name' => 'Sin Token']);

        expect($res['status'])->toBe(403)->and($res['json']['success'])->toBe(0);
    });

    it('los datos inválidos responden 422, nunca 500', function (array|string $body, array $headers): void {
        [$jar, $csrf] = loginAs('admin@test.cl');

        $res = api('POST', '/admin/users/addUser', $jar, $body, ['X-CSRF-Token: ' . $csrf, ...$headers]);

        expect($res['status'])->toBe(422)
            ->and($res['json']['success'])->toBe(0)
            ->and($res['json']['message'])->toBeString();
    })->with([
        'campos faltantes' => [['name' => 'A'], []],
        'arreglos donde se espera texto' => [['name[]' => 'A', 'email[]' => 'x', 'password[]' => 'x', 'empresa' => '1', 'id_perfil' => '1'], []],
        'JSON malformado' => ['{mal', ['Content-Type: application/json']],
        'correo inválido' => [['name' => 'Ana', 'email' => 'no-es-correo', 'password' => HTTP_TEST_PASSWORD, 'empresa' => '1', 'id_perfil' => '1', 'rol' => 'user'], []],
    ]);

    it('acepta el token CSRF por cabecera y crea el registro', function (): void {
        [$jar, $csrf] = loginAs('admin@test.cl');

        $res = api('POST', '/admin/users/addUser', $jar, [
            'name' => 'Nueva Persona', 'email' => 'nueva@test.cl', 'password' => HTTP_TEST_PASSWORD,
            'empresa' => '1', 'id_perfil' => '1', 'rol' => 'user',
        ], ['X-CSRF-Token: ' . $csrf]);

        expect($res['status'])->toBe(200)
            ->and($res['json']['success'])->toBe(1)
            ->and((int) httpDb()->query("SELECT COUNT(*) FROM versa_users WHERE email = 'nueva@test.cl'")->fetchColumn())->toBe(1);
    });

    it('los filtros y el orden de un listado no se ejecutan como SQL', function (): void {
        [$jar] = loginAs('admin@test.cl');
        $before = (int) httpDb()->query('SELECT COUNT(*) FROM versa_users')->fetchColumn();

        $res = api('GET', '/admin/users/getUsersPaginated?page=1&per_page=5&' . http_build_query([
            'order' => 'id;DROP TABLE versa_users--,asc',
            'filter' => "' OR 1=1--",
            'externalFilters' => '1=1;DELETE FROM versa_users',
        ]), $jar);

        expect($res['status'])->toBe(200)
            ->and($res['json']['data'])->toBe([])
            ->and((int) httpDb()->query('SELECT COUNT(*) FROM versa_users')->fetchColumn())->toBe($before);
    });

    it('un usuario sin rol de administrador no puede crear usuarios', function (): void {
        [$jar, $csrf] = loginAs('user@test.cl');

        $res = api('POST', '/admin/users/addUser', $jar, [
            'name' => 'Escalada', 'email' => 'escalada@test.cl', 'password' => HTTP_TEST_PASSWORD,
            'empresa' => '1', 'id_perfil' => '1', 'rol' => 'superadmin',
        ], ['X-CSRF-Token: ' . $csrf]);

        expect($res['status'])->toBeIn([401, 403])
            ->and((int) httpDb()->query("SELECT COUNT(*) FROM versa_users WHERE email = 'escalada@test.cl'")->fetchColumn())->toBe(0);
    });

    it('un fallo de base de datos responde 500 sin filtrar SQL ni rutas', function (): void {
        [$jar] = loginAs('admin@test.cl');
        $empresa = httpDb()->query('SELECT * FROM empresas LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $token = $empresa['token_empresa'] ?? $empresa['token'] ?? '';

        httpDb()->exec('ALTER TABLE media_store RENAME TO media_store_off');
        try {
            $res = api('GET', '/admin/mediaStore/api/list/' . $token, $jar);
        } finally {
            httpDb()->exec('ALTER TABLE media_store_off RENAME TO media_store');
        }

        expect($res['status'])->toBe(500)
            ->and($res['json']['success'])->toBe(0)
            ->and($res['body'])->not->toContain('SQLSTATE')->not->toContain('media_store')->not->toContain(getcwd());
    });

    it('rechaza un archivo PHP disfrazado de imagen', function (): void {
        [$jar, $csrf] = loginAs('admin@test.cl');
        $fake = httpEnv()['dir'] . '/avatar.png';
        file_put_contents($fake, '<?php echo 1;');

        $res = api('POST', '/admin/usuarios/updateAvatar', $jar, [
            '_csrf_token' => $csrf, 'fileAvatar' => new CURLFile($fake, 'image/png', 'avatar.png'),
        ]);

        expect($res['status'])->toBe(422);
    });
});

describe('seguridad del kernel', function (): void {
    it('un fallo interno en una página HTML responde 500, no 404, y sin filtrar SQL', function (): void {
        [$jar] = loginAs('admin@test.cl');

        httpDb()->exec('ALTER TABLE versa_perfil RENAME TO versa_perfil_off');
        try {
            $res = http('GET', '/admin/usuarios/addUser', $jar);
        } finally {
            httpDb()->exec('ALTER TABLE versa_perfil_off RENAME TO versa_perfil');
        }

        expect($res['status'])->toBe(500)
            ->and($res['body'])->not->toContain('SQLSTATE')->not->toContain('versa_perfil')->not->toContain(getcwd());
    });

    it('aísla la ventana de otros orígenes sin romper los popups de OAuth', function (): void {
        expect(http('GET', '/admin/login')['headers'])->toContain('Cross-Origin-Opener-Policy: same-origin-allow-popups');
    });

    it('al iniciar sesión renueva un hash de contraseña antiguo', function (): void {
        $old = password_hash(HTTP_TEST_PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]);
        httpDb()->prepare(
            'INSERT INTO versa_users (id_empresa, tokenid, name, email, password, expiration_pass, role, status, id_perfil)
             VALUES (1, ?, ?, ?, ?, ?, ?, 1, 1)',
        )->execute([bin2hex(random_bytes(16)), 'Hash antiguo', 'hashantiguo@test.cl', $old, date('Y-m-d', strtotime('+90 days')), 'user']);

        loginAs('hashantiguo@test.cl');
        $new = (string) httpDb()->query("SELECT password FROM versa_users WHERE email = 'hashantiguo@test.cl'")->fetchColumn();

        expect($new)->not->toBe($old)
            ->and(password_verify(HTTP_TEST_PASSWORD, $new))->toBeTrue()
            ->and(password_needs_rehash($new, PASSWORD_DEFAULT))->toBeFalse();
    });
});

describe('entrada sin reescribir', function (): void {
    it('guarda el texto tal como se envió: apóstrofos, & y palabras como select', function (): void {
        [$jar, $csrf] = loginAs('admin@test.cl');
        $nombre = "O'Brien & Cía <select from> 2";

        $res = api('POST', '/admin/perfiles/save', $jar, ['_csrf_token' => $csrf, 'nombre' => $nombre, 'estado' => 1]);
        $guardado = httpDb()->query('SELECT nombre FROM versa_perfil ORDER BY id DESC LIMIT 1')->fetchColumn();

        expect($res['json']['success'] ?? 0)->toBe(1)->and($guardado)->toBe($nombre);
    });

    it('y lo muestra escapado: el HTML enviado no se ejecuta en la página', function (): void {
        [$jar] = loginAs('admin@test.cl');

        expect(http('GET', '/admin/usuarios/addUser', $jar)['body'])
            ->toContain('O&#039;Brien &amp; Cía &lt;select from&gt; 2')
            ->not->toContain('<select from>');
    });
});

describe('método QUERY', function (): void {
    // php -S responde 501 a QUERY: se envía como POST con X-HTTP-Method-Override, como en desarrollo
    it('responde con los filtros en el cuerpo JSON y sin token CSRF (es de solo lectura)', function (): void {
        [$jar] = loginAs('admin@test.cl');
        $expected = api('GET', '/admin/users/getUsersPaginated?page=1&per_page=1', $jar)['json'];

        $res = api(
            'POST',
            '/admin/users/getUsersPaginated',
            $jar,
            json_encode(['page' => 1, 'per_page' => 1]),
            ['X-HTTP-Method-Override: QUERY', 'Content-Type: application/json; charset=utf-8'],
        );

        expect($res['status'])->toBe(200)
            ->and($res['json'])->toBe($expected)
            ->and($res['headers'])->toContain('Accept-Query: application/json');
    });

    it('un POST sin override a una ruta solo GET/QUERY responde 405 y lo anuncia en Allow', function (): void {
        [$jar] = loginAs('admin@test.cl');
        $res = api('POST', '/admin/users/getUsersPaginated', $jar, json_encode(['page' => 1]), ['Content-Type: application/json']);

        expect($res['status'])->toBe(405)->and($res['headers'])->toMatch('/^Allow: .*GET.*QUERY/mi');
    });

    it('lee el cuerpo JSON de PUT/PATCH aunque el Content-Type traiga charset', function (): void {
        [$jar, $csrf] = loginAs('admin@test.cl');

        $res = api(
            'PATCH',
            '/admin/perfiles/changeState',
            $jar,
            json_encode(['id' => 1, 'estado' => 1]),
            ['Content-Type: application/json; charset=utf-8', 'X-CSRF-Token: ' . $csrf],
        );

        expect($res['status'])->not->toBe(422)->and($res['json']['success'] ?? 0)->toBe(1);
    });
});

describe('CSP con nonce', function (): void {
    it('cada <script> de la página lleva el nonce de la cabecera, y no hay manejadores inline', function (string $page): void {
        // Con sesión, /admin/login redirige al panel: esa página se pide sin sesión
        $jar = $page === '/admin/login' ? null : loginAs('admin@test.cl')[0];
        $res = http('GET', $page, $jar);
        preg_match("/Content-Security-Policy:.*'nonce-([^']+)'/i", $res['headers'], $header);
        $nonce = $header[1] ?? '';

        preg_match_all('/<script\\b[^>]*>/i', $res['body'], $scripts);
        $sinNonce = array_filter($scripts[0], static fn (string $tag): bool => !str_contains($tag, 'nonce="' . $nonce . '"'));

        expect($nonce)->not->toBe('')
            ->and($scripts[0])->not->toBe([])
            ->and($sinNonce)->toBe([])
            ->and($res['body'])->not->toMatch('/<[^>]+\\son(click|change|load|submit|input)=/i');
    })->with(['/admin/login', '/admin/dashboard', '/admin/usuarios/addUser', '/admin/perfiluser', '/admin/no-existe']);

    it('el nonce cambia en cada petición', function (): void {
        $nonce = static fn (): string => preg_match("/'nonce-([^']+)'/", http('GET', '/admin/login')['headers'], $m) === 1 ? $m[1] : '';

        expect($nonce())->not->toBe($nonce());
    });
});

describe('/up', function (): void {
    it('responde 200 sin sesión y solo el estado, aunque la petición venga del propio servidor', function (): void {
        // Detrás de un proxy en el mismo servidor todas las visitas llegan desde 127.0.0.1
        $res = api('GET', '/up');

        expect($res['status'])->toBe(200)->and($res['json'])->toBe(['status' => 'ok']);
    });

    it('con la firma de session.key entrega cómo corre PHP en el servidor web', function (): void {
        $key = json_decode((string) file_get_contents(httpEnv()['dir'] . '/config.json'), true)['session']['key'];
        $res = api('GET', '/up', headers: ['X-Versawys-Health: ' . hash_hmac('sha256', 'versawys:/up', $key)]);

        expect($res['json'])->toHaveKeys(['kernel', 'php', 'opcache', 'xdebug'])
            ->and($res['json']['xdebug'])->toBeFalse();
    });
});

describe('app:check:deploy --url', function (): void {
    it('no encuentra archivos internos expuestos en el sitio y lee /up', function (): void {
        exec('php versaCLI app:check:deploy --url=' . escapeshellarg(httpEnv()['url']) . ' 2>&1 < /dev/null', $output);
        $output = implode("\n", $output);

        expect($output)->toContain('Archivos expuestos en la web')->not->toContain('responde 200')
            ->not->toContain('Xdebug está activo')->not->toContain('No se pudo leer la configuración de PHP');
    });

    it('detecta un archivo interno que el servidor entrega', function (): void {
        // Un servidor sin reglas: entrega cualquier archivo que exista
        $dir = httpEnv()['dir'] . '/sitio_sin_reglas';
        @mkdir($dir);
        file_put_contents($dir . '/.env', 'SECRETO=1');
        $probe = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr((string) stream_socket_get_name($probe, false), ':'), 1);
        fclose($probe);
        $server = proc_open(['php', '-S', '127.0.0.1:' . $port, '-t', $dir], [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        // Mientras arranca, cada intento de conexión fallido emite un warning: no es parte de la prueba
        set_error_handler(static fn (): bool => true);
        for ($i = 0; $i < 50 && fsockopen('127.0.0.1', $port) === false; $i++) {
            usleep(100_000);
        }
        restore_error_handler();

        try {
            exec('php versaCLI app:check:deploy --url=http://127.0.0.1:' . $port . ' 2>&1 < /dev/null', $output, $exitCode);
        } finally {
            proc_terminate($server);
            proc_close($server);
        }

        expect($exitCode)->toBe(1)->and(implode("\n", $output))->toContain('/.env responde 200');
    });
});

describe('cuentas inactivas', function (): void {
    it('desactiva al ingresar las cuentas sin uso en auth.inactive_account_days, salvo el superadministrador', function (): void {
        $insert = httpDb()->prepare(
            'INSERT INTO versa_users (id_empresa, tokenid, name, email, password, expiration_pass, role, status, id_perfil, last_login)
             VALUES (1, ?, ?, ?, ?, ?, ?, 1, 1, ?)',
        );
        $longAgo = date('Y-m-d H:i:s', strtotime('-400 days'));
        foreach ([['Inactivo', 'inactivo@test.cl', 'user'], ['Super inactivo', 'superinactivo@test.cl', 'superadmin']] as [$name, $email, $role]) {
            $insert->execute([bin2hex(random_bytes(16)), $name, $email, Functions::hash(HTTP_TEST_PASSWORD), date('Y-m-d', strtotime('+90 days')), $role, $longAgo]);
        }

        $jar = newJar();
        $login = api('POST', '/admin/login/autentication', $jar, [
            '_csrf_token' => csrfFrom('/admin/login', $jar),
            'email' => 'inactivo@test.cl',
            'password' => HTTP_TEST_PASSWORD,
        ]);

        expect($login['json']['success'] ?? 1)->toBe(0)
            ->and((int) httpDb()->query("SELECT status FROM versa_users WHERE email = 'inactivo@test.cl'")->fetchColumn())->toBe(0);
        loginAs('superinactivo@test.cl');
    });
});

// Medición, no prueba: no corre con la suite. `vendor/bin/pest --group=bench`
describe('benchmark', function (): void {
    it('mide la latencia de una página Twig, una API con sesión y un 404', function (): void {
        [$jar] = loginAs('admin@test.cl');
        $routes = [
            'página Twig (login)' => fn (): array => http('GET', '/admin/login'),
            'API JSON con sesión' => fn (): array => api('GET', '/admin/users/getUsersPaginated?page=1&per_page=10', $jar),
            'ruta inexistente (404)' => fn (): array => http('GET', '/no-existe/' . random_int(1, 9)),
        ];
        $rounds = (int) (getenv('BENCH_ROUNDS') ?: 300);

        $report = sprintf("\n  %-26s %8s %8s %8s\n", 'Ruta', 'p50 ms', 'p95 ms', 'req/s');
        foreach ($routes as $name => $request) {
            for ($i = 0; $i < 20; $i++) {
                $request(); // calentar OPcache y cachés
            }
            $times = [];
            for ($i = 0; $i < $rounds; $i++) {
                $start = hrtime(true);
                $request();
                $times[] = (hrtime(true) - $start) / 1e6;
            }
            sort($times);
            $report .= sprintf(
                "  %-26s %8.2f %8.2f %8.0f\n",
                $name,
                $times[intdiv($rounds, 2)],
                $times[(int) floor($rounds * 0.95)],
                $rounds / (array_sum($times) / 1000),
            );
        }
        fwrite(STDERR, $report);

        expect($times)->toHaveCount($rounds);
    })->group('bench');
});

describe('app:deploy:finish', function (): void {
    it('corre los pasos en orden y se detiene en el primero que falla', function (): void {
        // En este entorno la revisión final falla (cookie sin secure): debe detenerse ahí con código 1
        exec('php versaCLI app:deploy:finish 2>&1 < /dev/null', $output, $exitCode);
        $output = implode("\n", $output);

        expect($exitCode)->toBe(1)
            ->and($output)->toContain('No hay versaWYS/config.compartida.json: se omite')
            ->and(strpos($output, 'Migraciones pendientes'))->toBeLessThan(strpos($output, 'Registro de comandos'))
            ->and($output)->toContain('Falló «php versaCLI app:check:deploy --production»')
            ->not->toContain('Despliegue terminado');
    });
});

describe('límite de intentos', function (): void {
    // Último a propósito: deja bloqueada la IP de la suite para el login
    it('bloquea el login tras demasiados intentos', function (): void {
        $jar = newJar();
        $csrf = csrfFrom('/admin/login', $jar);
        $statuses = [];
        for ($i = 0; $i < 40 && !in_array(429, $statuses, true); $i++) {
            $statuses[] = api('POST', '/admin/login/autentication', $jar, [
                '_csrf_token' => $csrf, 'email' => "bot{$i}@test.cl", 'password' => 'Incorrecta#2026',
            ])['status'];
        }

        expect($statuses)->toContain(429);
    });
});
