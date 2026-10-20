<?php

declare(strict_types=1);

use versaWYS\kernel\cli\ConfigManager;

/*
 * Los comandos de versaCLI que se usan en el día a día, cada uno en un proceso propio como desde la
 * terminal, contra una base SQLite y una configuración de esta prueba (VERSAWYS_CONFIG): migraciones,
 * seeders, caché, logs, rutas, tareas cron, ajustes de config.json y los generadores con su borrado.
 */

/** @return array{0: int, 1: string} código de salida y salida sin colores */
function consola(string $args): array
{
    exec('VERSAWYS_CONFIG=' . escapeshellarg(consolaDir() . '/config.json') . ' php versaCLI ' . $args . ' -n 2>&1 < /dev/null', $output, $code);

    return [$code, (string) preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $output))];
}

function consolaDir(): string
{
    static $dir = null;
    if ($dir === null) {
        $dir = sys_get_temp_dir() . '/versawys_consola_' . getmypid();
        @mkdir($dir . '/logs', 0o700, true);
        $config = ConfigManager::defaults();
        $config['database'] = ['driver' => 'sqlite', 'database' => $dir . '/test.sqlite', 'log_path' => $dir . '/orm/'] + $config['database'];
        $config['session']['key'] = bin2hex(random_bytes(32));
        $config['twig']['compiled_dir'] = $dir . '/twig';
        $config['logging']['dir'] = $dir . '/logs/';
        file_put_contents($dir . '/config.json', json_encode($config));
    }

    return $dir;
}

function consolaTablas(): array
{
    $pdo = new PDO('sqlite:' . consolaDir() . '/test.sqlite');

    return $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN);
}

afterAll(fn() => exec('rm -rf ' . escapeshellarg(consolaDir())));

it('migraciones: up, status, rollback, down y refresh con seeders', function (): void {
    [$code, $out] = consola('migrate:up');
    expect($code)->toBe(0, $out)->and(consolaTablas())->toContain('versa_users')->toContain('versa_jobs');

    [, $estado] = consola('migrate:status');
    expect($estado)->toContain('ejecutada m20261015120000_versa_jobs')->not->toContain('• pendiente');

    // rollback revierte solo la última
    expect(consola('migrate:rollback')[0])->toBe(0);
    expect(consolaTablas())->not->toContain('versa_jobs')->toContain('versa_users');
    expect(consola('migrate:status')[1])->toContain('pendiente m20261015120000_versa_jobs');

    expect(consola('migrate:up')[0])->toBe(0);
    expect(consola('migrate:down:m20261015120000_versa_jobs')[0])->toBe(0);
    expect(consolaTablas())->not->toContain('versa_jobs');

    // Lo destructivo no corre sin confirmar (-n responde «no»)…
    [$code, $out] = consola('migrate:refresh:seeder');
    expect($code)->toBe(1)->and($out)->toContain('--force');
    // …y con --force deja todo migrado y sembrado
    [$code, $out] = consola('migrate:refresh:seeder --force');
    expect($code)->toBe(0, $out)->and(consolaTablas())->toContain('versa_jobs');
    $pdo = new PDO('sqlite:' . consolaDir() . '/test.sqlite');
    expect((int) $pdo->query('SELECT COUNT(*) FROM empresas')->fetchColumn())->toBeGreaterThan(0);
});

it('seeder:run ejecuta uno solo, y uno inexistente es un error', function (): void {
    consola('migrate:up');
    [$code, $out] = consola('seeder:run:S202502_PerfilDefaultSeeder');
    expect($code)->toBe(0, $out)->and($out)->toContain('S202502_PerfilDefaultSeeder');

    expect(consola('seeder:run:NoExiste')[0])->not->toBe(0);
});

it('caché, logs y ajustes de config.json', function (): void {
    expect(consola('cache:status')[0])->toBe(0);
    expect(consola('cache:twig')[0])->toBe(0);
    expect(consola('cache:clear')[0])->toBe(0);

    file_put_contents(consolaDir() . '/logs/prueba_2026.log', "uno\ndos\ntres\n");
    expect(consola('logs:list')[1])->toContain('prueba_2026.log');
    expect(consola('logs:tail:prueba_2026 --lines=1')[1])->toContain('tres');
    expect(consola('logs:clear')[0])->toBe(0);
    expect(glob(consolaDir() . '/logs/*.log'))->toBe([]);

    expect(consola('config:debug:true')[0])->toBe(0);
    expect(json_decode((string) file_get_contents(consolaDir() . '/config.json'), true)['build']['debug'])->toBeTrue();
    expect(consola('config:debug:false')[0])->toBe(0);
    expect(json_decode((string) file_get_contents(consolaDir() . '/config.json'), true)['build']['debug'])->toBeFalse();
    expect(consola('config:templateCache:true')[0])->toBe(0);
    expect(consola('config:ClearCache')[0])->toBe(0);
});

it('rutas, comandos y tareas cron se listan', function (): void {
    [$code, $out] = consola('route:list --all');
    expect($code)->toBe(0)->and($out)->toContain('/admin/login');

    [$code, $out] = consola('command:refresh');
    expect($code)->toBe(0)->and($out)->toContain('app:install');

    [$code, $out] = consola('cron:list');
    expect($code)->toBe(0)->and($out)->toContain('LimpiarTemporales');
    consola('migrate:up');
    expect(consola('cron:run:LimpiarTemporales')[0])->toBe(0);
});

it('lo que crean los generadores se borra con su comando', function (): void {
    $nombre = 'ZzConsola' . bin2hex(random_bytes(3));
    $archivos = [
        "app/controllers/{$nombre}Controller.php",
        "app/middleware/{$nombre}Middleware.php",
        "app/routes/{$nombre}Routes.php",
    ];

    try {
        expect(consola("controller:make:{$nombre}")[0])->toBe(0);
        expect(consola("middleware:make:{$nombre}")[0])->toBe(0);
        expect(consola("route:make:{$nombre}")[0])->toBe(0);
        foreach ($archivos as $archivo) {
            expect($archivo)->toBeFile();
        }

        // Borrar pide confirmación: con -n no borra, con --force sí
        consola("controller:delete:{$nombre}");
        expect($archivos[0])->toBeFile();
        expect(consola("controller:delete:{$nombre} --force")[0])->toBe(0);
        expect(consola("middleware:delete:{$nombre} --force")[0])->toBe(0);
        expect(consola("route:delete:{$nombre} --force")[0])->toBe(0);
        foreach ($archivos as $archivo) {
            expect($archivo)->not->toBeFile();
        }
    } finally {
        array_map(static fn(string $f): bool => !is_file($f) || unlink($f), $archivos);
    }
});
