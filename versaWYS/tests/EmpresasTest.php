<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\Factory;
use versaWYS\kernel\Testing\TestClient;
use versaWYS\kernel\Testing\TestDatabase;

/*
 * Un ejemplo de Factory: crear muchos registros en una línea para probar lo que depende de la
 * cantidad (búsqueda, paginación, filtros). La definición está en versaWYS/tests/factories/Empresas.php.
 */

beforeAll(fn() => TestDatabase::boot());

it('el listado de empresas busca y pagina', function (): void {
    $marca = 'Fabrica' . bin2hex(random_bytes(3));
    Factory::createMany('empresas', 12, ['nombre' => "{$marca} activa"]);
    Factory::createMany('empresas', 3, ['nombre' => "{$marca} inactiva", 'estado' => 0]);

    $superadmin = TestDatabase::login(TestDatabase::user('superadmin'));
    $buscar = static fn(array $query) => $superadmin->getJson('/admin/empresas/api/list?' . http_build_query($query))
        ->assertOk();

    $todas = $buscar(['filter' => $marca, 'per_page' => 5]);
    expect($todas->json('meta.total'))->toBe(15)
        ->and($todas->json('meta.total_pages'))->toBe(3)
        ->and($todas->json('data'))->toHaveCount(5);

    $ultima = $buscar(['filter' => $marca, 'per_page' => 5, 'page' => 3]);
    expect($ultima->json('data'))->toHaveCount(5);

    $inactivas = $buscar(['filter' => $marca, 'externalFilters' => 'estado=false']);
    expect($inactivas->json('meta.total'))->toBe(3);
});

it('un administrador que no es superadmin no ve el listado: la API responde 403 y la página redirige', function (): void {
    $admin = TestDatabase::login(TestDatabase::user('admin'));

    $admin->getJson('/admin/empresas/api/list')->assertStatus(403);
    $admin->get('/admin/empresas')->assertRedirect();
});

/** Un RUT chileno válido y distinto en cada llamada */
function empresas_rut(): string
{
    $cuerpo = random_int(10_000_000, 99_999_999);
    $suma = 0;
    $factor = 2;
    for ($d = $cuerpo; $d > 0; $d = intdiv($d, 10)) {
        $suma += ($d % 10) * $factor;
        $factor = $factor === 7 ? 2 : $factor + 1;
    }

    $dv = 11 - ($suma % 11);

    return $cuerpo . '-' . match ($dv) {
        11 => '0',
        10 => 'K',
        default => (string) $dv,
    };
}

/** @return array<string, mixed> */
function empresas_datos(array $cambios = []): array
{
    return $cambios + [
        'action' => 'create',
        'nombre' => 'Empresa ' . bin2hex(random_bytes(3)),
        'rut' => empresas_rut(),
        'email' => 'empresa-' . bin2hex(random_bytes(4)) . '@prueba.test',
        'telefono' => '+56 9 1234 5678',
    ];
}

/** @return array<string, mixed> */
function empresas_fila(string $rut): array
{
    $stmt = TestDatabase::pdo()->prepare('SELECT * FROM empresas WHERE rut = ?');
    $stmt->execute([$rut]);

    return (array) $stmt->fetch(PDO::FETCH_ASSOC);
}

describe('alta de empresas', function (): void {
    it('crea la empresa, su perfil Admin y un administrador que se activa con el enlace', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $datos = empresas_datos();

        $respuesta = $super->postJson('/admin/empresas/api/saveEmpresa', $datos)->assertOk();
        $empresa = empresas_fila($datos['rut']);
        expect($empresa['nombre'])->toBe($datos['nombre'])->and((int) $empresa['estado'])->toBe(1);

        // El administrador no conoce su contraseña: la define con el enlace de activación
        $enlace = (string) $respuesta->json('activation_url');
        expect($enlace)->toContain('/reset-password?token=');
        $token = urldecode((string) parse_url($enlace, PHP_URL_QUERY));
        $token = substr($token, strlen('token='));

        $visitante = TestDatabase::boot();
        $visitante->get('/admin/reset-password?token=' . rawurlencode($token))->assertOk();
        $visitante->postJson('/admin/login/apply-reset-password', [
            'tokenReset' => $token,
            'email' => $datos['email'],
            'new_password' => 'Activada#2027',
            'comfirm_new_password' => 'Activada#2027',
        ])->assertOk();

        TestDatabase::boot()->login($datos['email'], 'Activada#2027');
    });

    it('rechaza con 422 un RUT inválido, un RUT repetido y un correo de otro usuario', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $existente = empresas_datos();
        $super->postJson('/admin/empresas/api/saveEmpresa', $existente)->assertOk();

        $super->postJson('/admin/empresas/api/saveEmpresa', empresas_datos(['rut' => '12345678-0']))->assertStatus(422);
        $super->postJson('/admin/empresas/api/saveEmpresa', empresas_datos(['rut' => $existente['rut']]))->assertStatus(422);
        $super->postJson('/admin/empresas/api/saveEmpresa', empresas_datos(['email' => TestDatabase::user()]))->assertStatus(422);
        $super->postJson('/admin/empresas/api/saveEmpresa', empresas_datos(['nombre' => '']))->assertStatus(422);
        $super->postJson('/admin/empresas/api/saveEmpresa', empresas_datos(['email' => ['a@b.cl']]))->assertStatus(422);
    });
});

describe('edición y estado', function (): void {
    it('edita los datos; un id inexistente responde 404', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $datos = empresas_datos();
        $super->postJson('/admin/empresas/api/saveEmpresa', $datos)->assertOk();
        $id = (int) empresas_fila($datos['rut'])['id'];

        $super->putJson('/admin/empresas/api/editEmpresa', ['action' => 'edit', 'id' => $id, 'nombre' => 'Renombrada'] + $datos)
            ->assertOk();
        expect(empresas_fila($datos['rut'])['nombre'])->toBe('Renombrada');

        $super->putJson('/admin/empresas/api/editEmpresa', ['action' => 'edit', 'id' => 999999] + empresas_datos())->assertStatus(404);
    });

    it('no cambia el RUT por el de otra empresa', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $a = empresas_datos();
        $b = empresas_datos();
        $super->postJson('/admin/empresas/api/saveEmpresa', $a)->assertOk();
        $super->postJson('/admin/empresas/api/saveEmpresa', $b)->assertOk();

        $super->putJson('/admin/empresas/api/editEmpresa', [
            'action' => 'edit', 'id' => (int) empresas_fila($b['rut'])['id'], 'rut' => $a['rut'],
        ] + $b)->assertStatus(422);
    });

    it('suspende y reactiva; un id inexistente responde 404', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $datos = empresas_datos();
        $super->postJson('/admin/empresas/api/saveEmpresa', $datos)->assertOk();
        $id = (int) empresas_fila($datos['rut'])['id'];

        $super->deleteJson('/admin/empresas/api/changeStatus', ['id' => $id, 'estado' => 0])->assertOk();
        expect((int) empresas_fila($datos['rut'])['estado'])->toBe(0);
        $super->deleteJson('/admin/empresas/api/changeStatus', ['id' => $id, 'estado' => 1])->assertOk();
        expect((int) empresas_fila($datos['rut'])['estado'])->toBe(1);

        $super->deleteJson('/admin/empresas/api/changeStatus', ['id' => 999999, 'estado' => 0])->assertStatus(404);
        $super->deleteJson('/admin/empresas/api/changeStatus', ['id' => $id, 'estado' => 5])->assertStatus(422);
    });
});

describe('logo', function (): void {
    it('guarda la imagen con la extensión de su contenido, nunca la del nombre enviado', function (): void {
        $dir = sys_get_temp_dir() . '/versa_logos_' . bin2hex(random_bytes(4));
        mkdir($dir . '/logos', 0o775, true);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true);

        TestClient::withConfig(['assets' => ['dashboard' => ['store' => ['dist' => $dir]]]], function () use ($dir, $png): void {
            $super = TestDatabase::login(TestDatabase::user('superadmin'));
            $id = TestDatabase::empresa('Con logo');

            foreach (['logo.png' => 'ok', 'logo.html' => 'ok', 'logo.svg' => 'ok', 'logo.php' => 'ok'] as $nombre => $_) {
                $archivo = sys_get_temp_dir() . '/' . bin2hex(random_bytes(3)) . '-' . $nombre;
                file_put_contents($archivo, $png);
                $super->postFile('/admin/empresas/api/uploadLogo', ['id_empresa' => $id], ['logo' => $archivo])->assertOk();
            }

            $guardados = array_map(basename(...), glob($dir . '/logos/*') ?: []);
            expect($guardados)->toBe(["{$id}.png"], 'un PNG llamado .html o .php se guarda como .png');

            $texto = sys_get_temp_dir() . '/' . bin2hex(random_bytes(3)) . '.png';
            file_put_contents($texto, '<script>alert(1)</script>');
            $super->postFile('/admin/empresas/api/uploadLogo', ['id_empresa' => $id], ['logo' => $texto])->assertStatus(422);

            $otro = sys_get_temp_dir() . '/' . bin2hex(random_bytes(3)) . '.png';
            file_put_contents($otro, $png);
            $super->postFile('/admin/empresas/api/uploadLogo', ['id_empresa' => 999999], ['logo' => $otro])->assertStatus(404);
        });

        exec('rm -rf ' . escapeshellarg($dir));
    });
});
