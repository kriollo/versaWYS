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
    $config['session']['user_cookie']['key_encript'] = bin2hex(random_bytes(32));
    $config['session']['user_cookie']['secure'] = false; // el servidor de prueba es HTTP
    $config['twig']['cache'] = false;
    $config['logging']['dir'] = $dir . '/logs/';
    $config['rate_limiting']['cache_dir'] = $dir . '/rate_limits/';
    // Toda la suite sale de una misma IP: solo el límite de login se deja al alcance de un test
    foreach (['global', 'api', 'upload', 'sensitive'] as $rule) {
        $config['rate_limiting']['rules'][$rule]['requests'] = 100000;
    }
    $config['rate_limiting']['rules']['login']['requests'] = 30;
    file_put_contents($dir . '/config.json', json_encode($config));

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
        ['php', '-S', '127.0.0.1:' . $port, '-t', '.', dirname((string) (new ReflectionClass(versaWYS\kernel\cli\ServerManager::class))->getFileName()) . '/routerServe.php'],
        [['file', '/dev/null', 'r'], ['file', $dir . '/server.log', 'a'], ['file', $dir . '/server.log', 'a']],
        $pipes,
    );
    for ($i = 0; $i < 50 && @fsockopen('127.0.0.1', $port) === false; $i++) {
        usleep(100_000);
    }

    httpEnv(['dir' => $dir, 'url' => 'http://127.0.0.1:' . $port, 'server' => $server]);
});

afterAll(function (): void {
    $env = httpEnv();
    proc_terminate($env['server']);
    proc_close($env['server']);
    putenv('VERSAWYS_CONFIG');

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
            ->not->toContain('X-Powered-By');
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
