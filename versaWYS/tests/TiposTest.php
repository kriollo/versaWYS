<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestDatabase;

/*
 * Type juggling: con JSON, un atacante manda true, 0, null o un arreglo donde el código espera un
 * texto. PHP convierte tipos al comparar (0 == "a", "1e3" == "1000", in_array sin strict), así que
 * cada punto sensible debe rechazarlo: nunca aceptar la petición, nunca responder 500.
 */

beforeAll(fn() => TestDatabase::boot());

const TIPOS_RAROS = ['true' => true, 'false' => false, 'cero' => 0, 'uno' => 1, 'null' => null, 'arreglo' => ['x'], 'objeto' => ['a' => 'b']];

describe('login', function (): void {
    it('rechaza una contraseña o un correo que no son texto', function (): void {
        $email = TestDatabase::user();

        foreach (TIPOS_RAROS as $nombre => $valor) {
            foreach ([['email' => $email, 'password' => $valor], ['email' => $valor, 'password' => TestDatabase::PASSWORD]] as $datos) {
                $client = TestDatabase::boot();
                $client->get('/admin/login');
                $respuesta = $client->postJson('/admin/login/autentication', $datos);

                expect($respuesta->status)->toBeGreaterThanOrEqual(400, "login con {$nombre}")->toBeLessThan(500, "login con {$nombre}")
                    ->and($respuesta->json('success'))->toBe(0);
            }
        }
    });
});

describe('token CSRF', function (): void {
    it('un token que no es texto nunca pasa ni rompe la petición', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        foreach (TIPOS_RAROS as $nombre => $valor) {
            foreach (['_csrf_token', 'csrf_token', '_csrf_token_form'] as $campo) {
                $admin->withoutCsrfToken()
                    ->postJson('/admin/perfiles/save', ['nombre' => 'X', $campo => $valor])
                    ->assertStatus(403);
            }
        }
    });

    it('un campo con otro tipo no tapa un token válido en la cabecera', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->postJson('/admin/perfiles/save', ['nombre' => 'Con cabecera', '_csrf_token' => true])->assertOk();
    });
});

describe('contraseñas', function (): void {
    it('cambiar la propia con tipos raros se rechaza con 422', function (): void {
        $client = TestDatabase::login(TestDatabase::user('user'));

        foreach (TIPOS_RAROS as $nombre => $valor) {
            $client->postJson('/admin/login/resetPassByUser', ['password' => $valor, 'password_confirmation' => $valor])
                ->assertStatus(422);
        }
    });

    it('el admin no cambia una contraseña con tipos raros', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));
        $email = TestDatabase::user('user');
        $token = (string) TestDatabase::pdo()->query("SELECT tokenid FROM versa_users WHERE email = '{$email}'")->fetchColumn();

        foreach (TIPOS_RAROS as $nombre => $valor) {
            $status = $admin->patchJson('/admin/users/changePassword', ['tokenid' => $token, 'new_password' => $valor])->status;
            expect($status)->toBe(422, "new_password {$nombre}");
        }

        TestDatabase::login($email); // la contraseña sigue siendo la original
    });
});

describe('identificadores', function (): void {
    it('un id de ruta int solo acepta dígitos: 1e3, 0x1A, 1.5 o " 1" son 404', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        foreach (['1e3', '0x1A', '1.5', ' 1', '1abc', 'true'] as $id) {
            $admin->getJson('/admin/perfiles/getPerfil/' . rawurlencode($id))->assertStatus(404);
        }

        $admin->getJson('/admin/perfiles/getPerfil/1')->assertOk();
    });

    it('un tokenid que no es texto no encuentra a nadie', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        foreach (TIPOS_RAROS as $nombre => $valor) {
            $status = $admin->deleteJson('/admin/users/deleteUser', ['tokenid' => $valor])->status;
            expect($status)->toBe(403, "tokenid {$nombre}");
        }
    });
});

describe('listados', function (): void {
    it('la paginación ignora arreglos en vez de responder 500', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->getJson('/admin/users/getUsersPaginated?page[]=1&per_page[]=5&filter[]=x&order[]=id&externalFilters[]=a')
            ->assertOk();
        $admin->json('QUERY', '/admin/users/getUsersPaginated', ['page' => ['x'], 'filter' => ['a' => 'b'], 'order' => true])
            ->assertOk();
    });

    it('un per_page enorme o negativo queda dentro del límite', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        foreach (['1e9', '-5', '0', 'abc'] as $perPage) {
            $admin->getJson('/admin/users/getUsersPaginated', ['per_page' => $perPage])->assertOk();
        }
    });
});

describe('roles', function (): void {
    it('el superadministrador no asigna una empresa escrita como 1e3 o 0x1', function (): void {
        $super = TestDatabase::login(TestDatabase::user('superadmin'));

        foreach (['1e3', '0x1', '1.0', ' 1', true, ['1']] as $empresa) {
            $super->postJson('/admin/users/addUser', [
                'empresa' => $empresa, 'name' => 'Tipos', 'email' => 'emp-' . bin2hex(random_bytes(4)) . '@x.cl',
                'password' => TestDatabase::PASSWORD, 'id_perfil' => 2, 'rol' => 'user',
            ])->assertStatus(403);
        }
    });

    it('un rol que no es texto no da permisos de más', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        foreach (TIPOS_RAROS as $nombre => $valor) {
            $email = "tipos-{$nombre}-" . bin2hex(random_bytes(3)) . '@x.cl';
            $status = $admin->postJson('/admin/users/addUser', [
                'empresa' => 1, 'name' => 'Tipos', 'email' => $email,
                'password' => TestDatabase::PASSWORD, 'id_perfil' => 2, 'rol' => $valor,
            ])->status;

            // null es no enviarlo: un usuario común. Cualquier otro tipo se rechaza
            if ($valor === null) {
                expect(TestDatabase::pdo()->query("SELECT role FROM versa_users WHERE email = '{$email}'")->fetchColumn())->toBe('user');

                continue;
            }

            expect($status)->toBe(403, "rol {$nombre}");
        }
    });
});
