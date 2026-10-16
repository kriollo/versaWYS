<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestClient;
use versaWYS\kernel\Testing\TestDatabase;

/*
 * El ciclo de vida de una cuenta: iniciar sesión, contraseña vencida, cuenta desactivada y empresa
 * suspendida. Cada caso se prueba en el login y también con la sesión ya abierta, que es donde se
 * escapa un bloqueo mal hecho.
 */

beforeAll(fn() => TestDatabase::boot());

/** Inicia sesión por el formulario real y devuelve la respuesta (sin lanzar si falla). */
function cuentas_login(TestClient $client, string $email, string $password = TestDatabase::PASSWORD): array
{
    $client->get('/admin/login');

    return $client->postJson('/admin/login/autentication', ['email' => $email, 'password' => $password])->json();
}

/** Marca la fila como modificada ahora: así la caché de la sesión se entera del cambio */
function cuentas_actualizar(string $tabla, int|string $id, array $cambios, string $campo = 'id'): void
{
    $sets = implode(', ', array_map(static fn(string $c): string => "{$c} = ?", array_keys($cambios)));
    $stmt = TestDatabase::pdo()->prepare("UPDATE {$tabla} SET {$sets} WHERE {$campo} = ?");
    $stmt->execute([...array_values($cambios), $id]);
}

describe('inicio de sesión', function (): void {
    it('entra con la clave correcta y no con una incorrecta', function (): void {
        $email = TestDatabase::user();

        expect(cuentas_login(TestDatabase::boot(), $email, 'otra-clave')['success'])->toBe(0);
        expect(cuentas_login(TestDatabase::boot(), $email)['success'])->toBe(1);
    });

    it('cerrar sesión revoca el acceso aunque el navegador conserve la cookie', function (): void {
        $admin = TestDatabase::login(TestDatabase::user());
        $admin->getJson('/admin/perfiles/all')->assertOk();

        $admin->post('/admin/logout');

        $admin->getJson('/admin/perfiles/all')->assertStatus(401);
    });
});

describe('cuenta desactivada', function (): void {
    it('no inicia sesión', function (): void {
        $email = TestDatabase::user();
        cuentas_actualizar('versa_users', $email, ['status' => 0], 'email');

        $respuesta = cuentas_login(TestDatabase::boot(), $email);

        expect($respuesta['success'])->toBe(0);
    });

    it('desactivarla corta la sesión que ya estaba abierta', function (): void {
        $email = TestDatabase::user();
        $client = TestDatabase::login($email);
        $client->getJson('/admin/perfiles/all')->assertOk();

        cuentas_actualizar('versa_users', $email, ['status' => 0, 'updated_at' => date('Y-m-d H:i:s', time() + 5)], 'email');

        $client->getJson('/admin/perfiles/all')->assertStatus(403);
        $client->get('/admin/dashboard')->assertRedirect();
    });
});

describe('contraseña vencida', function (): void {
    it('puede entrar, pero cada página lleva al perfil hasta que la cambie', function (): void {
        $email = TestDatabase::user();
        cuentas_actualizar('versa_users', $email, ['expiration_pass' => date('Y-m-d', strtotime('-1 day'))], 'email');
        $client = TestDatabase::login($email);

        expect($client->get('/admin/dashboard')->header('location'))->toEndWith('/perfiluser');
        $client->get('/admin/perfiluser')->assertOk();
    });

    it('la API responde 403 mientras esté vencida: el bloqueo no se salta llamando a la API', function (): void {
        $email = TestDatabase::user();
        cuentas_actualizar('versa_users', $email, ['expiration_pass' => date('Y-m-d', strtotime('-1 day'))], 'email');
        $client = TestDatabase::login($email);

        $client->getJson('/admin/perfiles/all')->assertStatus(403)->assertJsonPath('password_expired', true);
    });

    it('al cambiarla recupera el acceso', function (): void {
        $email = TestDatabase::user();
        cuentas_actualizar('versa_users', $email, ['expiration_pass' => date('Y-m-d', strtotime('-1 day'))], 'email');
        $client = TestDatabase::login($email);

        $client->postJson('/admin/login/resetPassByUser', [
            'password' => 'Nueva#Clave2027',
            'password_confirmation' => 'Nueva#Clave2027',
        ])->assertOk();

        $client->get('/admin/dashboard')->assertOk();
        $client->getJson('/admin/perfiles/all')->assertOk();
    });
});

describe('empresa suspendida', function (): void {
    it('sus usuarios no inician sesión', function (): void {
        $empresa = TestDatabase::empresa('Suspendida');
        $email = TestDatabase::user('admin', $empresa);
        cuentas_actualizar('empresas', $empresa, ['estado' => 0]);

        $respuesta = cuentas_login(TestDatabase::boot(), $email);

        expect($respuesta['success'])->toBe(0)->and($respuesta['message'])->toContain('suspendida');
    });

    it('suspenderla corta las sesiones abiertas de sus usuarios', function (): void {
        $empresa = TestDatabase::empresa('Activa por ahora');
        $client = TestDatabase::login(TestDatabase::user('admin', $empresa));
        $client->getJson('/admin/perfiles/all')->assertOk();

        cuentas_actualizar('empresas', $empresa, ['estado' => 0]);

        $client->getJson('/admin/perfiles/all')->assertStatus(403)->assertJsonPath('message', 'La empresa está suspendida');
    });

    it('una página vuelve al login, que explica el motivo', function (): void {
        $empresa = TestDatabase::empresa('Suspendida con aviso');
        $client = TestDatabase::login(TestDatabase::user('admin', $empresa));
        cuentas_actualizar('empresas', $empresa, ['estado' => 0]);

        $destino = (string) $client->get('/admin/dashboard')->header('location');

        expect($destino)->toEndWith('/login?motivo=empresa_suspendida');
        $client->get($destino)->assertOk()->assertSee('La empresa está suspendida');
    });

    it('el superadministrador sigue entrando: es quien administra las empresas', function (): void {
        $empresa = TestDatabase::empresa('Del superadmin');
        $client = TestDatabase::login(TestDatabase::user('superadmin', $empresa));
        cuentas_actualizar('empresas', $empresa, ['estado' => 0]);

        $client->getJson('/admin/perfiles/all')->assertOk();
    });

    it('el login no acepta un motivo inventado', function (): void {
        TestDatabase::boot()->get('/admin/login?motivo=<script>')->assertOk()->assertDontSee('<script>');
    });

    it('las otras empresas siguen funcionando', function (): void {
        $suspendida = TestDatabase::empresa('Otra suspendida');
        cuentas_actualizar('empresas', $suspendida, ['estado' => 0]);

        TestDatabase::login(TestDatabase::user())->getJson('/admin/perfiles/all')->assertOk();
    });
});
