<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestClient;
use versaWYS\kernel\Testing\TestDatabase;

/*
 * Módulo de usuarios del panel: pantallas, listado, alta, edición, activar/desactivar, cambio de
 * contraseña por el admin y avatar. Cada acción se prueba también con quien no debería poder hacerla.
 */

const USUARIOS_CLAVE_NUEVA = 'Otra#Clave2027';

beforeAll(fn() => TestDatabase::boot());

function usuarios_token(string $email): string
{
    $stmt = TestDatabase::pdo()->prepare('SELECT tokenid FROM versa_users WHERE email = ?');
    $stmt->execute([$email]);

    return (string) $stmt->fetchColumn();
}

/** @return array<string, mixed> */
function usuarios_fila(string $email): array
{
    $stmt = TestDatabase::pdo()->prepare('SELECT * FROM versa_users WHERE email = ?');
    $stmt->execute([$email]);

    return (array) $stmt->fetch(PDO::FETCH_ASSOC);
}

/** @return array<string, mixed> los datos del formulario de alta */
function usuarios_alta(array $cambios = []): array
{
    return $cambios + [
        'empresa' => 1,
        'name' => 'Usuaria Nueva',
        'email' => 'nueva-' . bin2hex(random_bytes(4)) . '@prueba.test',
        'password' => TestDatabase::PASSWORD,
        'id_perfil' => 2,
        'rol' => 'user',
    ];
}

/** Un PNG de 1×1 en un archivo temporal */
function usuarios_png(): string
{
    $file = sys_get_temp_dir() . '/versa_avatar_' . bin2hex(random_bytes(4)) . '.png';
    file_put_contents($file, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==', true));

    return $file;
}

describe('pantallas', function (): void {
    it('el admin ve la lista y los formularios; un usuario común no', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));
        $admin->get('/admin/usuarios')->assertOk();
        $admin->get('/admin/usuarios/addUser')->assertOk();
        $admin->get('/admin/usuarios/editUser/' . usuarios_token(TestDatabase::user('user')))->assertOk()->assertDontSee('$2y$');

        // El formulario de un usuario de otra empresa, o de uno que no existe: 404
        $admin->get('/admin/usuarios/editUser/' . usuarios_token(TestDatabase::user('user', TestDatabase::empresa('Ajena edición'))))
            ->assertStatus(404);
        $admin->get('/admin/usuarios/editUser/no-existe')->assertStatus(404);

        TestDatabase::login(TestDatabase::user('user'))->get('/admin/usuarios')->assertRedirect();
    });
});

describe('listado', function (): void {
    it('cada empresa ve solo sus usuarios, por GET y por QUERY', function (): void {
        $empresa = TestDatabase::empresa('Con usuarios propios');
        $propio = TestDatabase::user('user', $empresa);
        $ajeno = TestDatabase::user('user');
        $admin = TestDatabase::login(TestDatabase::user('admin', $empresa));

        $lista = $admin->getJson('/admin/users/getUsersPaginated', ['per_page' => 100])->assertOk();
        $correos = array_column($lista->json('data'), 'email');
        expect($correos)->toContain($propio)->not->toContain($ajeno);

        $porQuery = $admin->json('QUERY', '/admin/users/getUsersPaginated', ['filter' => $propio])->assertOk();
        expect(array_column($porQuery->json('data'), 'email'))->toBe([$propio]);
    });

    it('filtra por estado', function (): void {
        $empresa = TestDatabase::empresa('Con un usuario inactivo');
        $inactivo = TestDatabase::user('user', $empresa);
        TestDatabase::pdo()->prepare('UPDATE versa_users SET status = 0 WHERE email = ?')->execute([$inactivo]);
        $admin = TestDatabase::login(TestDatabase::user('admin', $empresa));

        $lista = $admin->getJson('/admin/users/getUsersPaginated', ['externalFilters' => 'status=false'])->assertOk();

        expect(array_column($lista->json('data'), 'email'))->toBe([$inactivo]);
    });
});

describe('alta', function (): void {
    it('crea un usuario que puede iniciar sesión', function (): void {
        $datos = usuarios_alta();

        TestDatabase::login(TestDatabase::user('admin'))->postJson('/admin/users/addUser', $datos)->assertOk();

        expect(usuarios_fila($datos['email']))->toMatchArray(['role' => 'user', 'id_empresa' => 1, 'status' => 1]);
        TestDatabase::boot()->login($datos['email'], TestDatabase::PASSWORD);
    });

    it('rechaza un correo repetido, una clave débil y datos incompletos', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->postJson('/admin/users/addUser', usuarios_alta(['email' => TestDatabase::user()]))
            ->assertStatus(422)->assertJsonPath('errors.email', 'El correo ya existe');
        $admin->postJson('/admin/users/addUser', usuarios_alta(['password' => '123']))->assertStatus(422);
        $admin->postJson('/admin/users/addUser', usuarios_alta(['name' => '', 'email' => 'no-es-correo']))->assertStatus(422);
    });

    it('un admin no crea superadministradores ni usuarios de otra empresa', function (): void {
        $empresa = TestDatabase::empresa('Del admin');
        $admin = TestDatabase::login(TestDatabase::user('admin', $empresa));

        $admin->postJson('/admin/users/addUser', usuarios_alta(['rol' => 'superadmin']))->assertStatus(403);

        // Pide la empresa 1, pero el usuario queda en la suya
        $datos = usuarios_alta(['empresa' => 1]);
        $admin->postJson('/admin/users/addUser', $datos)->assertOk();
        expect((int) usuarios_fila($datos['email'])['id_empresa'])->toBe($empresa);
    });

    it('un superadministrador sí puede asignar otra empresa', function (): void {
        $empresa = TestDatabase::empresa('Destino');
        $datos = usuarios_alta(['empresa' => $empresa, 'rol' => 'admin']);

        TestDatabase::login(TestDatabase::user('superadmin'))->postJson('/admin/users/addUser', $datos)->assertOk();

        expect(usuarios_fila($datos['email']))->toMatchArray(['id_empresa' => $empresa, 'role' => 'admin']);
    });

    it('un usuario común no crea usuarios', function (): void {
        TestDatabase::login(TestDatabase::user('user'))->postJson('/admin/users/addUser', usuarios_alta())->assertStatus(403);
    });
});

describe('edición', function (): void {
    it('cambia el nombre y, si viene, la contraseña', function (): void {
        $email = TestDatabase::user('user');
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->putJson('/admin/users/editUser', [
            'tokenid' => usuarios_token($email),
            'empresa' => 1,
            'name' => 'Nombre Editado',
            'email' => $email,
            'id_perfil' => 2,
            'rol' => 'user',
            'password' => USUARIOS_CLAVE_NUEVA,
        ])->assertOk();

        expect(usuarios_fila($email)['name'])->toBe('Nombre Editado');
        TestDatabase::boot()->login($email, USUARIOS_CLAVE_NUEVA);
    });

    it('sin contraseña conserva la actual', function (): void {
        $email = TestDatabase::user('user');

        TestDatabase::login(TestDatabase::user('admin'))->putJson('/admin/users/editUser', [
            'tokenid' => usuarios_token($email), 'empresa' => 1, 'name' => 'Sin clave', 'email' => $email,
            'id_perfil' => 2, 'rol' => 'user', 'password' => '',
        ])->assertOk();

        TestDatabase::login($email);
    });

    it('no acepta el correo de otro usuario', function (): void {
        $email = TestDatabase::user('user');

        TestDatabase::login(TestDatabase::user('admin'))->putJson('/admin/users/editUser', [
            'tokenid' => usuarios_token($email), 'empresa' => 1, 'name' => 'X', 'email' => TestDatabase::user(),
            'id_perfil' => 2, 'rol' => 'user',
        ])->assertStatus(422);
    });

    it('un admin no edita usuarios de otra empresa ni superadministradores', function (): void {
        $admin = TestDatabase::login(TestDatabase::user('admin'));
        $otraEmpresa = TestDatabase::user('user', TestDatabase::empresa('Ajena'));
        $superadmin = TestDatabase::user('superadmin');

        foreach ([$otraEmpresa, $superadmin] as $email) {
            $admin->putJson('/admin/users/editUser', [
                'tokenid' => usuarios_token($email), 'empresa' => 1, 'name' => 'Intruso', 'email' => $email,
                'id_perfil' => 2, 'rol' => 'user',
            ])->assertStatus(403);
            expect(usuarios_fila($email)['name'])->not->toBe('Intruso');
        }
    });
});

describe('activar y desactivar', function (): void {
    it('desactiva y vuelve a activar; desactivado no inicia sesión', function (): void {
        $email = TestDatabase::user('user');
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->deleteJson('/admin/users/deleteUser', ['tokenid' => usuarios_token($email)])->assertOk();
        expect((int) usuarios_fila($email)['status'])->toBe(0);
        expect(fn() => TestDatabase::login($email))->toThrow(RuntimeException::class);

        $admin->deleteJson('/admin/users/deleteUser', ['tokenid' => usuarios_token($email)])->assertOk();
        expect((int) usuarios_fila($email)['status'])->toBe(1);
        TestDatabase::login($email);
    });

    it('nadie se desactiva a sí mismo', function (): void {
        $email = TestDatabase::user('admin');

        TestDatabase::login($email)->deleteJson('/admin/users/deleteUser', ['tokenid' => usuarios_token($email)])->assertStatus(403);

        expect((int) usuarios_fila($email)['status'])->toBe(1);
    });

    it('un admin no desactiva usuarios de otra empresa', function (): void {
        $ajeno = TestDatabase::user('user', TestDatabase::empresa('Otra más'));

        TestDatabase::login(TestDatabase::user('admin'))
            ->deleteJson('/admin/users/deleteUser', ['tokenid' => usuarios_token($ajeno)])->assertStatus(403);
    });
});

describe('cambio de contraseña por el admin', function (): void {
    it('la nueva sirve para entrar; una débil se rechaza', function (): void {
        $email = TestDatabase::user('user');
        $admin = TestDatabase::login(TestDatabase::user('admin'));

        $admin->patchJson('/admin/users/changePassword', ['tokenid' => usuarios_token($email), 'new_password' => '123'])
            ->assertStatus(422);
        $admin->patchJson('/admin/users/changePassword', [
            'tokenid' => usuarios_token($email),
            'new_password' => USUARIOS_CLAVE_NUEVA,
        ])->assertOk();

        TestDatabase::boot()->login($email, USUARIOS_CLAVE_NUEVA);
    });

    it('no cambia la de un usuario de otra empresa', function (): void {
        $ajeno = TestDatabase::user('user', TestDatabase::empresa('Lejana'));

        TestDatabase::login(TestDatabase::user('admin'))->patchJson('/admin/users/changePassword', [
            'tokenid' => usuarios_token($ajeno),
            'new_password' => USUARIOS_CLAVE_NUEVA,
        ])->assertStatus(403);
    });
});

describe('avatar', function (): void {
    it('guarda una imagen real y rechaza otro contenido con extensión de imagen', function (): void {
        $dir = sys_get_temp_dir() . '/versa_avatars_' . bin2hex(random_bytes(4));
        mkdir($dir);

        TestClient::withConfig(['assets' => ['dashboard' => ['avatars' => ['dist' => $dir]]]], function () use ($dir): void {
            $email = TestDatabase::user('user');
            $client = TestDatabase::login($email);

            $client->postFile('/admin/usuarios/updateAvatar', [], ['fileAvatar' => usuarios_png()])->assertOk();
            $id = (int) usuarios_fila($email)['id'];
            expect($dir . "/{$id}.png")->toBeFile()->and(usuarios_fila($email)['avatar'])->toBe("{$id}.png");

            $falsa = sys_get_temp_dir() . '/versa_falsa_' . bin2hex(random_bytes(4)) . '.png';
            file_put_contents($falsa, '<?php echo "hola";');
            $client->postFile('/admin/usuarios/updateAvatar', [], ['fileAvatar' => $falsa])->assertStatus(422);

            $client->postJson('/admin/usuarios/updateAvatar', [])->assertStatus(422);
        });

        exec('rm -rf ' . escapeshellarg($dir));
    });
});
