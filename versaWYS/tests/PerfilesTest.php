<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestDatabase;

/*
 * Perfiles y permisos del menú, de punta a punta: el superadministrador asocia un módulo a la empresa,
 * el admin le da el permiso a un perfil y el usuario con ese perfil ve la opción en su menú.
 */

beforeAll(fn() => TestDatabase::boot());

/** Un perfil nuevo de la empresa; devuelve su id */
function perfiles_crear(int $empresa, string $nombre): int
{
    // La misma conexión para el INSERT y lastInsertId(): cada TestDatabase::pdo() abre una nueva
    $pdo = TestDatabase::pdo();
    $pdo->prepare("INSERT INTO versa_perfil (id_empresa, nombre, pagina_inicio, estado) VALUES (?, ?, 'dashboard', 1)")
        ->execute([$empresa, $nombre]);

    return (int) $pdo->lastInsertId();
}

/** Un usuario del perfil indicado; devuelve el correo */
function perfiles_usuario(int $empresa, int $perfil): string
{
    $email = TestDatabase::user('user', $empresa);
    TestDatabase::pdo()->prepare('UPDATE versa_users SET id_perfil = ? WHERE email = ?')->execute([$perfil, $email]);

    return $email;
}

describe('perfiles', function (): void {
    it('cada empresa ve solo sus perfiles y puede crear uno', function (): void {
        $empresa = TestDatabase::empresa('Con perfiles');
        perfiles_crear($empresa, 'Bodega');
        $admin = TestDatabase::login(TestDatabase::user('admin', $empresa));

        $admin->postJson('/admin/perfiles/save', ['nombre' => 'Ventas'])->assertOk();

        $nombres = array_column($admin->getJson('/admin/perfiles/all')->assertOk()->json('data'), 'nombre');
        expect($nombres)->toContain('Bodega', 'Ventas')->not->toContain('Personalizado');
    });

    it('un perfil sin nombre se rechaza', function (): void {
        TestDatabase::login(TestDatabase::user('admin'))->postJson('/admin/perfiles/save', ['nombre' => ''])->assertStatus(422);
    });

    it('activa y desactiva un perfil propio, pero no uno de otra empresa', function (): void {
        $propio = perfiles_crear(1, 'Temporal');
        $ajeno = perfiles_crear(TestDatabase::empresa('Ajena de perfiles'), 'Ajeno');
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->patchJson('/admin/perfiles/changeState', ['id' => $propio])->assertOk();
        expect((int) TestDatabase::pdo()->query("SELECT estado FROM versa_perfil WHERE id = {$propio}")->fetchColumn())->toBe(0);

        $admin->patchJson('/admin/perfiles/changeState', ['id' => $ajeno])->assertStatus(404);
        expect((int) TestDatabase::pdo()->query("SELECT estado FROM versa_perfil WHERE id = {$ajeno}")->fetchColumn())->toBe(1);
    });

    it('no muestra ni modifica el perfil de otra empresa', function (): void {
        $ajeno = perfiles_crear(TestDatabase::empresa('Perfil secreto'), 'Secreto');
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->getJson("/admin/perfiles/getPerfil/{$ajeno}")->assertStatus(404);
        $admin->postJson('/admin/perfiles/savePerfilPermisos', [
            'id' => $ajeno, 'nombre' => 'Cambiado', 'pagina_inicio' => 'dashboard', 'data' => '[]',
        ])->assertStatus(404);
        expect(TestDatabase::pdo()->query("SELECT nombre FROM versa_perfil WHERE id = {$ajeno}")->fetchColumn())->toBe('Secreto');
    });

    it('un perfil inexistente responde 404', function (): void {
        TestDatabase::login(TestDatabase::user('admin'))->getJson('/admin/perfiles/getPerfil/999999')->assertStatus(404);
    });
});

describe('permisos del menú', function (): void {
    it('el usuario ve solo las opciones que su perfil tiene, y el cambio llega a los usuarios existentes', function (): void {
        $empresa = TestDatabase::empresa('Con Media Store');

        // 1. El superadministrador asocia el módulo «Media Store» (id 1, del seeder) a la empresa
        TestDatabase::login(TestDatabase::user('superadmin'))->postJson('/admin/empresas/api/saveModuloEmpresaAsociado', [
            'id' => $empresa,
            'modulos' => json_encode([['id' => 1, 'asociado' => true]]),
        ])->assertOk();

        $perfil = perfiles_crear($empresa, 'Archivo');
        $conPermiso = perfiles_usuario($empresa, $perfil);
        $sinPermiso = perfiles_usuario($empresa, perfiles_crear($empresa, 'Sin archivos'));
        $sesionAbierta = TestDatabase::login($conPermiso);
        expect($sesionAbierta->get('/admin/dashboard')->body)->not->toContain('Almacenamiento de Medios');

        // 2. El admin marca la opción en el perfil, con los mismos datos que muestra la pantalla
        $admin = TestDatabase::login(TestDatabase::user('admin', $empresa));
        $permisos = $admin->getJson("/admin/perfiles/getPerfil/{$perfil}")->assertOk()->json('data');
        expect($permisos['ARCHIVOS']['Media Store']['submenu'][0]['submenu'])->toBe('Almacenamiento de Medios');
        $permisos['ARCHIVOS']['Media Store']['submenu'][0]['checked'] = true;

        $admin->postJson('/admin/perfiles/savePerfilPermisos', [
            'id' => $perfil, 'nombre' => 'Archivo', 'pagina_inicio' => 'dashboard', 'data' => json_encode($permisos),
        ])->assertOk();

        // 3. El usuario la ve en su siguiente página, sin volver a entrar; el de otro perfil, no
        $sesionAbierta->get('/admin/dashboard')->assertOk()->assertSee('Almacenamiento de Medios');
        TestDatabase::login($sinPermiso)->get('/admin/dashboard')->assertOk()->assertDontSee('Almacenamiento de Medios');
    });

    it('sin el módulo asociado a la empresa, el perfil ni siquiera lo ofrece', function (): void {
        $empresa = TestDatabase::empresa('Sin módulos');
        $perfil = perfiles_crear($empresa, 'Vacío');

        $permisos = TestDatabase::login(TestDatabase::user('admin', $empresa))
            ->getJson("/admin/perfiles/getPerfil/{$perfil}")->assertOk()->json('data');

        expect($permisos)->toBe([]);
    });

    it('guardar sin data cambia el nombre y conserva los permisos', function (): void {
        $perfil = perfiles_crear(1, 'Conserva');
        TestDatabase::pdo()->prepare('INSERT INTO versa_perfildetalle (perfil_id, menu_id, submenu_id) VALUES (?, 1, 1)')->execute([$perfil]);

        TestDatabase::login(TestDatabase::user('admin'))->postJson('/admin/perfiles/savePerfilPermisos', [
            'id' => $perfil, 'nombre' => 'Conserva 2', 'pagina_inicio' => 'dashboard',
        ])->assertOk();

        expect((int) TestDatabase::pdo()->query("SELECT COUNT(*) FROM versa_perfildetalle WHERE perfil_id = {$perfil}")->fetchColumn())->toBe(1);
    });
});
