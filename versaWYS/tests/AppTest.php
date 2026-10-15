<?php

declare(strict_types=1);

use app\services\MailService;
use versaWYS\kernel\ContentType;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Testing\TestClient;

/*
 * Pruebas de la aplicación con TestClient: la petición recorre el kernel completo (cabeceras, rutas,
 * middlewares, controlador, sesión, CSRF y base de datos) en el mismo proceso, sin servidor. Así se
 * prueban los módulos de un proyecto. La base es una SQLite desechable, como en HttpTest.
 */

const APP_TEST_PASSWORD = 'Clave#Segura2026';

function appTestDir(): string
{
    return sys_get_temp_dir() . '/versawys_app_' . getmypid();
}

beforeAll(function (): void {
    $dir = appTestDir();
    @mkdir($dir, 0o700, true);

    $config = versaWYS\kernel\cli\ConfigManager::defaults();
    $config['database'] = ['driver' => 'sqlite', 'database' => $dir . '/test.sqlite', 'log_path' => $dir . '/orm/'] + $config['database'];
    $config['session']['key'] = bin2hex(random_bytes(32));
    $config['twig']['compiled_dir'] = $dir . '/twig';
    $config['csp']['nonce'] = true;
    $config['logging']['dir'] = $dir . '/logs/';
    $config['rate_limiting']['cache_dir'] = $dir . '/rate_limits/';
    foreach (['global', 'api', 'upload', 'sensitive', 'login'] as $rule) {
        $config['rate_limiting']['rules'][$rule]['requests'] = 100000;
    }
    file_put_contents($dir . '/config.json', json_encode($config));

    $env = 'VERSAWYS_CONFIG=' . escapeshellarg($dir . '/config.json');
    exec("{$env} php versaCLI migrate:up 2>&1 < /dev/null && {$env} php versaCLI seeder:runAll 2>&1 < /dev/null", $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException("No se pudo preparar la base de prueba:\n" . implode("\n", $output));
    }

    $db = new PDO('sqlite:' . $dir . '/test.sqlite', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $insert = $db->prepare(
        'INSERT INTO versa_users (id_empresa, tokenid, name, email, password, expiration_pass, role, status, id_perfil)
         VALUES (1, ?, ?, ?, ?, ?, ?, 1, 1)',
    );
    foreach ([['Admin', 'admin@app.test', 'superadmin'], ['Usuario', 'user@app.test', 'user']] as [$name, $email, $role]) {
        $insert->execute([bin2hex(random_bytes(16)), $name, $email, Functions::hash(APP_TEST_PASSWORD), date('Y-m-d', strtotime('+90 days')), $role]);
    }

    TestClient::boot(dirname(__DIR__, 2), $dir . '/config.json');
});

afterAll(function (): void {
    exec('rm -rf ' . escapeshellarg(appTestDir()));
});

/** Un navegador nuevo; con $email, ya con la sesión iniciada. */
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
