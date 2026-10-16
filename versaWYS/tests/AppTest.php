<?php

declare(strict_types=1);

use app\services\MailService;
use versaWYS\kernel\ContentType;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Testing\TestClient;
use versaWYS\kernel\Testing\TestDatabase;

/*
 * Pruebas de la aplicación con TestClient: la petición recorre el kernel completo (cabeceras, rutas,
 * middlewares, controlador, sesión, CSRF y base de datos) en el mismo proceso, sin servidor. Así se
 * prueban los módulos de un proyecto. La base es una SQLite desechable, como en HttpTest.
 */

const APP_TEST_PASSWORD = TestDatabase::PASSWORD;

// Una base SQLite desechable para toda la suite (TestDatabase): la comparten las pruebas de cada módulo
beforeAll(function (): void {
    TestDatabase::boot();

    // Usuarios con correo fijo para estas pruebas (TestDatabase::user() crea otros con correo único)
    $insert = TestDatabase::pdo()->prepare(
        'INSERT INTO versa_users (id_empresa, tokenid, name, email, password, expiration_pass, role, status, id_perfil)
         VALUES (1, ?, ?, ?, ?, ?, ?, 1, 1)',
    );
    foreach ([['Admin', 'admin@app.test', 'superadmin'], ['Usuario', 'user@app.test', 'user']] as [$name, $email, $role]) {
        $exists = TestDatabase::pdo()->query("SELECT COUNT(*) FROM versa_users WHERE email = '{$email}'")->fetchColumn();
        if ((int) $exists === 0) {
            $insert->execute([bin2hex(random_bytes(16)), $name, $email, Functions::hash(APP_TEST_PASSWORD), date('Y-m-d', strtotime('+90 days')), $role]);
        }
    }
});

function app(?string $email = null): TestClient
{
    $client = TestClient::boot(dirname(__DIR__, 2));
    if ($email !== null) {
        $client->login($email, APP_TEST_PASSWORD);
    }

    return $client;
}

describe('TestClient', function (): void {
    it('responde la página de login con la CSP con nonce', function (): void {
        app()->get('/admin/login')
            ->assertOk()
            ->assertSee('id="csrf_token"')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        expect(app()->get('/admin/login')->header('Content-Security-Policy'))->toContain("'nonce-");
    });

    it('sin sesión el panel redirige al login', function (): void {
        app()->get('/admin/dashboard')->assertRedirect('/admin/login');
    });

    it('con sesión el panel responde y la API entrega JSON', function (): void {
        $admin = app('admin@app.test');

        $admin->get('/admin/dashboard')->assertOk();
        $admin->getJson('/admin/empresas/api/list')->assertOk()->assertJsonPath('success', 1);
    });

    it('un usuario sin rol de superadmin no entra a sus rutas', function (): void {
        $response = app('user@app.test')->getJson('/admin/empresas/api/list');

        expect($response->status)->toBeIn([302, 401, 403]);
    });

    it('exige el token CSRF en las peticiones que modifican datos', function (): void {
        $admin = app('admin@app.test');

        $admin->withoutCsrfToken()->postJson('/admin/empresas/api/saveEmpresa', ['nombre' => 'x'])->assertStatus(403);
    });

    it('valida los datos y responde 422 con los errores por campo', function (): void {
        $response = app('admin@app.test')->postJson('/admin/empresas/api/saveEmpresa', []);

        $response->assertStatus(422);
        expect($response->json('errors'))->toBeArray()->not->toBeEmpty();
    });

    it('una ruta que no existe responde 404', function (): void {
        app('admin@app.test')->getJson('/admin/no-existe')->assertStatus(404);
    });

    it('cada cliente es un navegador distinto: no comparten la sesión', function (): void {
        $admin = app('admin@app.test');
        $otro = app();

        $admin->get('/admin/dashboard')->assertOk();
        $otro->get('/admin/dashboard')->assertRedirect('/admin/login');
    });

    it('el correo de prueba usa el MailService del contenedor: en la prueba, uno que no envía', function (): void {
        $enviados = new ArrayObject();
        $admin = app('admin@app.test');
        $admin->container()->instance(MailService::class, new class ($enviados) extends MailService {
            public function __construct(private readonly ArrayObject $enviados) {}

            public function enviar(array $to, string $asunto, string $cuerpo, ContentType $tipo = ContentType::HTML, array $imagenesEmbed = []): bool
            {
                $this->enviados[] = [$to['email'], $asunto];

                return true;
            }
        });

        try {
            $admin->postJson('/admin/configuracion/testMail')->assertOk()->assertJsonPath('success', 1);
            expect($enviados->getArrayCopy())->toBe([['admin@app.test', 'Correo de prueba']]);
        } finally {
            $admin->container()->forget(MailService::class);
        }
    });

    it('después del logout la sesión ya no sirve', function (): void {
        $admin = app('admin@app.test');
        $admin->post('/admin/logout');

        $admin->get('/admin/dashboard')->assertRedirect('/admin/login');
    });
});

describe('usuarios por consola', function (): void {
    $cli = static function (string ...$args): array {
        $env = 'VERSAWYS_CONFIG=' . escapeshellarg(TestDatabase::configPath());
        exec("{$env} php versaCLI " . implode(' ', array_map(escapeshellarg(...), $args)) . ' 2>&1 < /dev/null', $output, $code);

        return [$code, implode("\n", $output)];
    };

    it('crea un usuario que puede entrar, lo lista sin la clave y le cambia la clave', function () use ($cli): void {
        $cli('command:refresh');
        [$code] = $cli('app:user:create', '--name=Consola', '--email=consola@app.test', '--role=admin', '--empresa=1', '--perfil=1', '--password=' . APP_TEST_PASSWORD);
        expect($code)->toBe(0);
        app('consola@app.test'); // login() falla si la clave no sirve

        [, $lista] = $cli('app:user:list');
        expect($lista)->toContain('consola@app.test')->not->toContain('$2y$')->not->toContain('$argon');

        [$code] = $cli('app:user:password', 'consola@app.test', '--password=Nueva#Clave2027');
        expect($code)->toBe(0);
        TestClient::boot(dirname(__DIR__, 2))->login('consola@app.test', 'Nueva#Clave2027');
        expect(fn() => app('consola@app.test'))->toThrow(RuntimeException::class);
    });

    it('rechaza un correo repetido y una clave fuera de la política', function () use ($cli): void {
        [$code, $out] = $cli('app:user:create', '--name=X', '--email=admin@app.test', '--role=user', '--empresa=1', '--password=' . APP_TEST_PASSWORD);
        expect($code)->toBe(1)->and($out)->toContain('Ya existe un usuario con ese correo');

        [$code] = $cli('app:user:create', '--name=X', '--email=nuevo@app.test', '--role=user', '--empresa=1', '--password=123');
        expect($code)->toBe(1);
    });
});

describe('modo mantenimiento', function (): void {
    $cli = static function (string ...$args): string {
        $env = 'VERSAWYS_CONFIG=' . escapeshellarg(TestDatabase::configPath());
        exec("{$env} php versaCLI " . implode(' ', array_map(escapeshellarg(...), $args)) . ' 2>&1 < /dev/null', $output);

        return implode("\n", $output);
    };

    it('app:down responde 503 a todos menos a quien entra con la clave, y app:up lo termina', function () use ($cli): void {
        $cli('command:refresh');
        expect($cli('app:down', '--secret=equipo-ok', '--message=Volvemos pronto'))->toContain('/equipo-ok');

        try {
            $visitante = TestClient::boot(dirname(__DIR__, 2));
            $visitante->get('/admin/login')->assertStatus(503)->assertSee('Volvemos pronto');
            $visitante->getJson('/up')->assertStatus(503);

            $equipo = TestClient::boot(dirname(__DIR__, 2));
            $equipo->get('/equipo-ok')->assertRedirect();
            $equipo->get('/admin/login')->assertOk();
        } finally {
            $cli('app:up');
        }

        TestClient::boot(dirname(__DIR__, 2))->get('/admin/login')->assertOk();
    });
});
