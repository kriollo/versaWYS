<?php

declare(strict_types=1);

use versaWYS\kernel\cli\ConfigManager;
use versaWYS\kernel\cli\ServerManager;

/*
 * El asistente web /install de punta a punta, como lo usa quien instala: `config:init` crea config.json y
 * el token, el servidor sirve /install y el formulario instala contra una base PostgreSQL real. La base
 * se vacía: tiene que ser desechable (VERSA_TEST_PG, ver RespaldoPostgresTest). Sin la variable se salta.
 */

/** @return array{status: int, headers: string, body: string, json: mixed} */
function instalador_http(string $url, string $jar, ?array $form = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HTTPHEADER => ['Accept: application/json, text/html'],
    ]);
    if ($form !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $form);
    }

    $raw = (string) curl_exec($ch);
    $size = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($raw, $size);

    return [
        'status' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
        'headers' => substr($raw, 0, $size),
        'body' => $body,
        'json' => json_decode($body, true),
    ];
}

it('instala con el token de config:init y después ya no se puede volver a instalar', function (): void {
    $valor = getenv('VERSA_TEST_PG');
    if (!is_string($valor) || substr_count($valor, ':') !== 4) {
        $this->markTestSkipped('Sin VERSA_TEST_PG (PostgreSQL desechable)');
    }

    [$host, $port, $database, $username, $password] = explode(':', $valor);
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$database}", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public');

    // Un proyecto recién creado: config:init en una carpeta propia (la de la copia local ya está instalada)
    $dir = sys_get_temp_dir() . '/versawys_instalador_' . getmypid();
    @mkdir($dir, 0o700, true);
    $configFile = $dir . '/config.json';
    exec('VERSAWYS_CONFIG=' . escapeshellarg($configFile) . ' php versaCLI config:init 2>&1 < /dev/null', $output, $code);
    expect($code)->toBe(0, implode("\n", $output));
    $config = json_decode((string) file_get_contents($configFile), true);
    $config['session']['user_cookie']['secure'] = false; // el servidor de prueba es HTTP
    $config['twig']['compiled_dir'] = $dir . '/twig';
    $config['logging']['dir'] = $dir . '/logs/';
    $config['rate_limiting']['cache_dir'] = $dir . '/rate_limits/';
    file_put_contents($configFile, json_encode($config));
    $token = trim((string) file_get_contents($dir . '/install.token'));

    // El frontend compilado es un requisito de la instalación (en CI no se compila)
    $marcas = array_keys(array_filter([
        'public/dashboard/css/dashboard.css' => !is_file('public/dashboard/css/dashboard.css'),
        'public/dashboard/js/versa-install-test.js' => glob('public/dashboard/js/*.js') === [],
    ]));
    foreach ($marcas as $archivo) {
        is_dir(dirname($archivo)) || mkdir(dirname($archivo), 0o775, true);
        touch($archivo);
    }

    $probe = stream_socket_server('tcp://127.0.0.1:0');
    $puerto = (int) substr(strrchr((string) stream_socket_get_name($probe, false), ':'), 1);
    fclose($probe);
    $servidor = proc_open(
        ['php', '-d', 'xdebug.mode=off', '-S', '127.0.0.1:' . $puerto, '-t', '.', dirname((string) (new ReflectionClass(ServerManager::class))->getFileName()) . '/routerServe.php'],
        [['file', '/dev/null', 'r'], ['file', $dir . '/server.log', 'a'], ['file', $dir . '/server.log', 'a']],
        $pipes,
        null,
        ['VERSAWYS_CONFIG' => $configFile] + getenv(),
    );
    $url = 'http://127.0.0.1:' . $puerto;
    $jar = $dir . '/cookies.txt';
    // Esperar al servidor con curl: fsockopen deja una advertencia por cada intento rechazado
    for ($i = 0; $i < 50 && instalador_http($url . '/up', $jar)['status'] === 0; $i++) {
        usleep(100_000);
    }

    try {
        $pagina = instalador_http($url . '/install', $jar);
        expect($pagina['status'])->toBe(200);
        preg_match('/name="_csrf_token" id="csrf_token" value="([^"]+)"/', $pagina['body'], $csrf);
        $datos = [
            '_csrf_token' => $csrf[1] ?? '',
            'db_driver' => 'postgresql', 'db_host' => $host, 'db_port' => $port, 'db_name' => $database,
            'db_user' => $username, 'db_pass' => $password,
            'empresa_nombre' => 'Empresa Instalada', 'empresa_rut' => '11.111.111-1',
            'admin_name' => 'Primer Admin', 'admin_email' => 'admin@instalada.cl',
            'admin_password' => 'Clave#Segura2026', 'admin_password_confirm' => 'Clave#Segura2026',
        ];

        // Sin el token, o con otro, nadie puede apuntar el sitio a su base y crear su superadministrador
        expect(instalador_http($url . '/install/run', $jar, $datos)['status'])->toBe(403);
        expect(instalador_http($url . '/install/run', $jar, $datos + ['install_token' => str_repeat('a', strlen($token))])['status'])
            ->toBe(403);
        expect(is_file($dir . '/installed.lock'))->toBeFalse();

        $instalacion = instalador_http($url . '/install/run', $jar, $datos + ['install_token' => $token]);
        expect($instalacion['json']['success'] ?? null)->toBe(1, $instalacion['body']);

        expect(is_file($dir . '/installed.lock'))->toBeTrue()
            ->and(is_file($dir . '/install.token'))->toBeFalse('el token se borra al instalar');
        $guardada = json_decode((string) file_get_contents($configFile), true)['database'];
        expect($guardada['driver'])->toBe('postgresql')->and($guardada['database'])->toBe($database);
        expect($pdo->query("SELECT role FROM versa_users WHERE email = 'admin@instalada.cl'")->fetchColumn())->toBe('superadmin');
        expect((int) $pdo->query('SELECT COUNT(*) FROM versa_users')->fetchColumn())->toBe(1);

        // Instalado: /install lleva al login y una segunda instalación no corre
        expect(instalador_http($url . '/install', $jar)['status'])->toBe(302);
        expect(instalador_http($url . '/install/run', $jar, $datos + ['install_token' => $token])['status'])->toBe(302);
        expect((int) $pdo->query('SELECT COUNT(*) FROM versa_users')->fetchColumn())->toBe(1);
    } finally {
        proc_terminate($servidor);
        proc_close($servidor);
        array_map(unlink(...), $marcas);
        exec('rm -rf ' . escapeshellarg($dir));
        $pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public');
    }
});
