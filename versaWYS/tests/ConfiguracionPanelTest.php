<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestDatabase;

/*
 * Pantalla de Configuración (solo superadministrador): ajustes guardados en config.json, tareas
 * programadas, consola SQL y respaldos. Lo que se guarda se escribe en el config.json de la prueba,
 * que se restaura al terminar cada caso.
 */

beforeAll(fn() => TestDatabase::boot());

/** @return array<string, mixed> lo mismo que envía la pantalla */
function config_datos(array $cambios = []): array
{
    return array_replace_recursive([
        'mail' => ['host' => '', 'port' => 587, 'transport' => 'smtp', 'secure' => 'tls', 'name_from' => 'App', 'username' => '', 'password' => ''],
        'login_attempt' => ['max' => 5, 'time' => 120],
        'auth' => [
            'expiration_days_password' => 90,
            'inactive_account_days' => 30,
            'password_policy' => ['large' => 8, 'uppercase' => true, 'lowercase' => true, 'number' => true, 'special_chars' => true],
        ],
        'build' => ['name' => 'Panel de prueba', 'version' => '1.0.0', 'timezone' => 'America/Santiago', 'charset' => 'UTF-8', 'debug' => false],
        'admin' => ['custom_url' => 'admin', 'hide_default' => false, 'redirect_404' => false],
    ], $cambios);
}

/** Corre la prueba y deja config.json como estaba */
function config_restaurando(Closure $prueba): void
{
    $archivo = TestDatabase::configPath();
    $original = (string) file_get_contents($archivo);
    try {
        $prueba($archivo);
    } finally {
        file_put_contents($archivo, $original);
    }
}

describe('ajustes', function (): void {
    it('solo el superadministrador los ve y los cambia', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->getJson('/admin/configuracion/get')->assertStatus(403);
        $admin->postJson('/admin/configuracion/save', config_datos())->assertStatus(403);
        $admin->postJson('/admin/configuracion/database/query', ['query' => 'SELECT 1'])->assertStatus(403);
    });

    it('nunca entrega la contraseña SMTP, la clave de sesión ni la base de datos', function (): void {
        config_restaurando(function (string $archivo): void {
            $config = json_decode((string) file_get_contents($archivo), true);
            // @mago-expect lint:no-literal-password
            $config['mail']['password'] = 'smtp-secreta';
            file_put_contents($archivo, json_encode($config));

            $body = TestDatabase::login(TestDatabase::user('superadmin'))->getJson('/admin/configuracion/get')->assertOk()->body;

            expect($body)->not->toContain('smtp-secreta')->not->toContain((string) $config['session']['key'])->not->toContain('"database"');
        });
    });

    it('guarda los cambios; una contraseña SMTP en blanco conserva la actual', function (): void {
        config_restaurando(function (string $archivo): void {
            $super = TestDatabase::login(TestDatabase::user('superadmin'));

            $super->postJson('/admin/configuracion/save', config_datos(['mail' => ['host' => 'smtp.prueba.test', 'password' => 'clave-smtp'], 'login_attempt' => ['max' => 7]]))
                ->assertOk();
            $super->postJson('/admin/configuracion/save', config_datos(['mail' => ['host' => 'smtp.prueba.test', 'password' => '']]))->assertOk();

            $guardado = json_decode((string) file_get_contents($archivo), true);
            expect($guardado['mail']['host'])->toBe('smtp.prueba.test')
                ->and($guardado['mail']['password'])->toBe('clave-smtp')
                ->and($guardado['login_attempt']['max'])->toBe(5);
        });
    });

    it('rechaza con 422 valores que romperían la aplicación o que no son del tipo esperado', function (): void {
        config_restaurando(function (string $archivo): void {
            $super = TestDatabase::login(TestDatabase::user('superadmin'));
            $antes = (string) file_get_contents($archivo);

            foreach ([
                ['build' => ['timezone' => 'Marte/Olympus']],
                ['build' => ['charset' => 'NO-EXISTE']],
                ['admin' => ['custom_url' => '../otra']],
                ['admin' => ['custom_url' => '']],
                ['login_attempt' => ['max' => 0]],
                ['mail' => ['port' => 70000]],
                ['build' => ['name' => ['Array']]],
                ['mail' => ['host' => ['smtp']]],
            ] as $malo) {
                $super->postJson('/admin/configuracion/save', config_datos($malo))->assertStatus(422);
            }

            $super->postJson('/admin/configuracion/save', ['mail' => 'texto', 'build' => 5])->assertStatus(422);
            expect((string) file_get_contents($archivo))->toBe($antes, 'nada rechazado quedó guardado');
        });
    });

    it('activar o desactivar el modo debug queda en el log de seguridad, con quién lo hizo', function (): void {
        config_restaurando(function (): void {
            $email = TestDatabase::user('superadmin');
            $super = TestDatabase::login($email);
            global $config;
            $logs = (string) $config['logging']['dir'];

            $super->postJson('/admin/configuracion/save', config_datos(['build' => ['debug' => true]]))->assertOk();

            $seguridad = implode('', array_map(file_get_contents(...), glob($logs . 'security_*.log') ?: []));
            expect($seguridad)->toContain('Modo debug activado desde el panel')->toContain($email);
        });
    });
});

describe('tareas programadas', function (): void {
    it('lista las tareas de app/schedule.php y una inexistente responde 404', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));

        $tareas = $super->getJson('/admin/configuracion/tareas')->assertOk()->json('tasks');
        expect(array_column($tareas, 'task'))->toContain('app\cron\LimpiarTemporales');

        $super->postJson('/admin/configuracion/tareas/ejecutar', ['id' => 'no-existe'])->assertStatus(404);
    });
});

describe('consola SQL', function (): void {
    it('devuelve filas, confirma lo que no las devuelve y explica un error', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));

        $filas = $super->postJson('/admin/configuracion/database/query', ['query' => "SELECT 'hola' AS saludo"])->assertOk();
        expect($filas->json('data.rows'))->toBe([['saludo' => 'hola']]);

        $super->postJson('/admin/configuracion/database/query', ['query' => 'CREATE TABLE consola_prueba (id INTEGER)'])
            ->assertOk()->assertJsonPath('data.type', 'ack');
        $super->postJson('/admin/configuracion/database/query', ['query' => 'DROP TABLE consola_prueba'])->assertOk();

        $error = $super->postJson('/admin/configuracion/database/query', ['query' => 'SELECT * FROM no_existe'])->assertOk();
        expect($error->json('data.type'))->toBe('error');

        expect($super->postJson('/admin/configuracion/database/query', ['query' => ''])->json('data.type'))->toBe('error');
        expect($super->postJson('/admin/configuracion/database/query', ['query' => ['SELECT 1']])->json('data.type'))->toBe('error');
    });

    it('cada consulta queda registrada con quién la ejecutó, aunque falle', function (): void {
        $email = TestDatabase::user('superadmin');
        $marca = 'auditoria_' . bin2hex(random_bytes(4));
        TestDatabase::login($email)->postJson('/admin/configuracion/database/query', ['query' => "SELECT * FROM {$marca}"]);

        global $config;
        $log = implode('', array_map(file_get_contents(...), glob($config['logging']['dir'] . 'database_*.log') ?: []));
        expect($log)->toContain($marca)->toContain($email);
    });

    it('exige el token CSRF', function (): void {
        TestDatabase::login(TestDatabase::user('superadmin'))->withoutCsrfToken()
            ->postJson('/admin/configuracion/database/query', ['query' => 'SELECT 1'])->assertStatus(403);
    });
});

describe('respaldos', function (): void {
    it('con SQLite lo dice en vez de intentar pg_dump', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));

        $super->postJson('/admin/configuracion/database/export/prepare')->assertStatus(422)
            ->assertJsonPath('message', 'Los respaldos desde el panel funcionan con MySQL/MariaDB y PostgreSQL.');
    });

    it('un token de descarga inválido o inexistente responde 404', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));

        foreach (['no-es-token', str_repeat('a', 32), '..%2F..%2Fconfig'] as $token) {
            $super->get("/admin/configuracion/database/export/{$token}/download")->assertStatus(404);
        }
    });

    it('importar solo acepta archivos .sql', function (): void {
        $archivo = sys_get_temp_dir() . '/versa_' . bin2hex(random_bytes(3)) . '.txt';
        file_put_contents($archivo, 'SELECT 1;');

        TestDatabase::login(TestDatabase::user('superadmin'))
            ->postFile('/admin/configuracion/database/import', [], ['dump' => $archivo])
            ->assertStatus(422)->assertJsonPath('message', 'Solo se aceptan archivos .sql');
    });
});
