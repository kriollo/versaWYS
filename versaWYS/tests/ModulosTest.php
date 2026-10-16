<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestDatabase;

/*
 * Administración del menú (módulos y submódulos), solo para el superadministrador. Lo que cambia
 * aquí debe verse en el menú de quien ya tiene la sesión abierta, no recién al volver a entrar.
 */

beforeAll(fn() => TestDatabase::boot());

/** @return array<string, mixed> */
function modulos_datos(array $cambios = []): array
{
    return $cambios + [
        'action' => 'create',
        'seccion' => 'PRUEBAS ' . bin2hex(random_bytes(3)),
        'nombre' => 'Módulo ' . bin2hex(random_bytes(3)),
        'descripcion' => 'Un módulo de prueba',
        'icono' => '<svg viewBox="0 0 16 16"><path d="M0 0h16v16H0z"/></svg>',
        'fill' => false,
        'estado' => true,
        'url' => '/reportes',
    ];
}

/** @return array<string, mixed> */
function modulos_fila(string $tabla, string $nombre): array
{
    $stmt = TestDatabase::pdo()->prepare("SELECT * FROM {$tabla} WHERE nombre = ?");
    $stmt->execute([$nombre]);

    return (array) $stmt->fetch(PDO::FETCH_ASSOC);
}

describe('módulos', function (): void {
    it('solo el superadministrador los administra', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->get('/admin/modulesRoutes')->assertRedirect();
        $admin->getJson('/admin/modules/getModulesPaginated')->assertStatus(403);
        $admin->postJson('/admin/modules/saveModule', modulos_datos())->assertStatus(403);
    });

    it('crea un módulo activo al final de su sección, y aparece en el listado', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $primero = modulos_datos();
        $segundo = modulos_datos(['seccion' => $primero['seccion']]);

        $super->postJson('/admin/modules/saveModule', $primero)->assertOk();
        $super->postJson('/admin/modules/saveModule', $segundo)->assertOk();

        $fila = modulos_fila('versa_menu', $segundo['nombre']);
        expect((int) $fila['estado'])->toBe(1, 'estado: true enviado como JSON')
            ->and((int) $fila['posicion'])->toBe((int) modulos_fila('versa_menu', $primero['nombre'])['posicion'] + 1);

        $lista = $super->getJson('/admin/modules/getModulesPaginated', ['filter' => $segundo['nombre']])->assertOk();
        expect(array_column($lista->json('data'), 'nombre'))->toBe([$segundo['nombre']]);
    });

    it('editar no lo mueve de posición ni cambia su fecha de creación', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $datos = modulos_datos();
        $super->postJson('/admin/modules/saveModule', $datos)->assertOk();
        $super->postJson('/admin/modules/saveModule', modulos_datos(['seccion' => $datos['seccion']]))->assertOk();
        $antes = modulos_fila('versa_menu', $datos['nombre']);

        $super->postJson('/admin/modules/saveModule', ['action' => 'edit', 'id' => $antes['id'], 'descripcion' => 'Editado'] + $datos)
            ->assertOk();

        $despues = modulos_fila('versa_menu', $datos['nombre']);
        expect($despues['descripcion'])->toBe('Editado')
            ->and($despues['posicion'])->toBe($antes['posicion'])
            ->and($despues['created_at'])->toBe($antes['created_at']);
    });

    it('limpia el SVG del ícono', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $datos = modulos_datos(['icono' => '<svg onload="alert(1)"><script>alert(1)</script><path d="M0 0"/></svg>']);

        $super->postJson('/admin/modules/saveModule', $datos)->assertOk();

        expect(modulos_fila('versa_menu', $datos['nombre'])['icono'])->not->toContain('script')->not->toContain('onload');
    });

    it('rechaza datos incompletos con 422', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));

        $super->postJson('/admin/modules/saveModule', modulos_datos(['nombre' => '']))->assertStatus(422);
        $super->patchJson('/admin/modules/changeStatus', [])->assertStatus(422);
        $super->patchJson('/admin/modules/movePosition', ['id' => 1])->assertStatus(422);
        $super->patchJson('/admin/submodules/changeStatus', [])->assertStatus(422);
    });

    it('un id inexistente responde 404, no 500', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));

        $super->patchJson('/admin/modules/changeStatus', ['id' => 999999, 'estado' => true])->assertStatus(404);
        $super->patchJson('/admin/submodules/changeStatus', ['id' => 999999, 'id_menu' => 1, 'estado' => true])->assertStatus(404);
    });

    it('sube y baja un módulo dentro de su sección', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $a = modulos_datos();
        $b = modulos_datos(['seccion' => $a['seccion']]);
        $super->postJson('/admin/modules/saveModule', $a)->assertOk();
        $super->postJson('/admin/modules/saveModule', $b)->assertOk();
        $idB = modulos_fila('versa_menu', $b['nombre'])['id'];

        $super->patchJson('/admin/modules/movePosition', ['id' => $idB, 'direction' => 'up'])->assertOk();

        expect((int) modulos_fila('versa_menu', $b['nombre'])['posicion'])
            ->toBeLessThan((int) modulos_fila('versa_menu', $a['nombre'])['posicion']);
    });
});

describe('el menú de quien ya tiene la sesión abierta', function (): void {
    it('desactivar un módulo lo quita del menú en la siguiente página', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $datos = modulos_datos(['nombre' => 'Visible ' . bin2hex(random_bytes(3))]);
        $super->postJson('/admin/modules/saveModule', $datos)->assertOk();

        $otro = TestDatabase::login(TestDatabase::user('superadmin'));
        $otro->get('/admin/dashboard')->assertSee($datos['nombre']);

        $super->patchJson('/admin/modules/changeStatus', ['id' => modulos_fila('versa_menu', $datos['nombre'])['id'], 'estado' => false])
            ->assertOk();

        $otro->get('/admin/dashboard')->assertDontSee($datos['nombre']);
    });
});

describe('submódulos', function (): void {
    it('desactivar uno no esconde los demás submódulos del menú', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));
        $menu = modulos_datos(['url' => '#']);
        $super->postJson('/admin/modules/saveModule', $menu)->assertOk();
        $idMenu = (int) modulos_fila('versa_menu', $menu['nombre'])['id'];

        foreach (['Sub uno', 'Sub dos'] as $nombre) {
            $super->postJson('/admin/submodules/saveModule', [
                'action' => 'create', 'id_menu' => $idMenu, 'nombre' => "{$nombre} {$idMenu}",
                'descripcion' => 'x', 'url' => '/sub-' . bin2hex(random_bytes(3)), 'estado' => 'true',
            ])->assertOk();
        }

        $lista = $super->getJson('/admin/modules/getSubModules', ['externalFilters' => "id_menu={$idMenu}"])->assertOk();
        expect($lista->json('data'))->toHaveCount(2);

        $super->patchJson('/admin/submodules/changeStatus', [
            'id' => modulos_fila('versa_submenu', "Sub uno {$idMenu}")['id'], 'id_menu' => $idMenu, 'estado' => false,
        ])->assertOk();

        $pagina = TestDatabase::login(TestDatabase::user('superadmin'))->get('/admin/dashboard');
        $pagina->assertSee("Sub dos {$idMenu}")->assertDontSee("Sub uno {$idMenu}");
    });

    it('un submódulo sin url se rechaza', function (): void {
        TestDatabase::login(TestDatabase::user('superadmin'))->postJson('/admin/submodules/saveModule', [
            'action' => 'create', 'id_menu' => 1, 'nombre' => 'Sin url', 'descripcion' => 'x', 'url' => '', 'estado' => 'true',
        ])->assertStatus(422);
    });
});
