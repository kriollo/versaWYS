<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestDatabase;

/*
 * Recuperar la contraseña: el enlace sirve una vez, vence, y la pantalla no revela qué correos
 * están registrados.
 */

beforeAll(fn() => TestDatabase::boot());

/** Pide el enlace como lo hace la pantalla «¿Olvidaste tu contraseña?» */
function recuperacion_pedir(string $email): versaWYS\kernel\Testing\TestResponse
{
    $client = TestDatabase::boot();
    $client->get('/admin/lost-password');

    return $client->patchJson('/admin/lost-password/send', ['email' => $email]);
}

function recuperacion_token(string $email): string
{
    $stmt = TestDatabase::pdo()->prepare('SELECT restore_token FROM versa_users WHERE email = ?');
    $stmt->execute([$email]);

    return (string) $stmt->fetchColumn();
}

/** @return array<string, mixed> */
function recuperacion_datos(string $token, string $email, mixed $clave = 'Recuperada#2027'): array
{
    return ['tokenReset' => $token, 'email' => $email, 'new_password' => $clave, 'comfirm_new_password' => $clave];
}

it('responde lo mismo si el correo existe o no: no revela quién está registrado', function (): void {
    $existe = recuperacion_pedir(TestDatabase::user());
    $noExiste = recuperacion_pedir('nadie-' . bin2hex(random_bytes(4)) . '@prueba.test');

    expect($noExiste->status)->toBe($existe->status)
        ->and($noExiste->json('message'))->toBe($existe->json('message'))
        ->and($existe->status)->toBe(200);
});

it('el enlace cambia la contraseña una sola vez', function (): void {
    $email = TestDatabase::user();
    recuperacion_pedir($email)->assertOk();
    $token = recuperacion_token($email);
    expect($token)->not->toBe('');

    $client = TestDatabase::boot();
    $client->get('/admin/reset-password?token=' . rawurlencode($token))->assertOk();
    $client->postJson('/admin/login/apply-reset-password', recuperacion_datos($token, $email))->assertOk();
    TestDatabase::boot()->login($email, 'Recuperada#2027');

    // Usado, ya no sirve
    TestDatabase::boot()->get('/admin/reset-password?token=' . rawurlencode($token))->assertStatus(404);
    $otro = TestDatabase::boot();
    $otro->get('/admin/login');
    $otro->postJson('/admin/login/apply-reset-password', recuperacion_datos($token, $email, 'Otra#Clave2028'))->assertStatus(422);
});

it('un enlace vencido o inventado no sirve', function (): void {
    $email = TestDatabase::user();
    $vencido = bin2hex(random_bytes(32)) . '.' . (time() - 60);
    TestDatabase::pdo()->prepare('UPDATE versa_users SET restore_token = ? WHERE email = ?')->execute([$vencido, $email]);

    foreach ([$vencido, 'inventado', bin2hex(random_bytes(32)) . '.' . (time() + 3600)] as $token) {
        TestDatabase::boot()->get('/admin/reset-password?token=' . rawurlencode($token))->assertStatus(404);

        $client = TestDatabase::boot();
        $client->get('/admin/login');
        $client->postJson('/admin/login/apply-reset-password', recuperacion_datos($token, $email))->assertStatus(422);
    }
});

it('las contraseñas deben coincidir y cumplir la política', function (): void {
    $email = TestDatabase::user();
    recuperacion_pedir($email)->assertOk();
    $token = recuperacion_token($email);
    $client = TestDatabase::boot();
    $client->get('/admin/login');

    $client->postJson('/admin/login/apply-reset-password', ['comfirm_new_password' => 'Distinta#2027'] + recuperacion_datos($token, $email))
        ->assertStatus(422);
    $client->postJson('/admin/login/apply-reset-password', recuperacion_datos($token, $email, '123'))->assertStatus(422);
    $client->postJson('/admin/login/apply-reset-password', recuperacion_datos($token, $email, ['x']))->assertStatus(422);

    expect(recuperacion_token($email))->toBe($token, 'un intento fallido no gasta el enlace');
});

it('con sesión iniciada, las pantallas de recuperación llevan al panel', function (): void {
    TestDatabase::login(TestDatabase::user())->get('/admin/lost-password')->assertRedirect();
});

it('el correo con el enlace sale por la cola, con el enlace de esa cuenta', function (): void {
    $email = TestDatabase::user();
    recuperacion_pedir($email)->assertOk();

    $stmt = TestDatabase::pdo()->prepare("SELECT payload FROM versa_jobs WHERE job = ? AND payload LIKE ? ORDER BY id DESC");
    $stmt->execute([app\jobs\EnviarCorreo::class, '%' . $email . '%']);
    $datos = json_decode((string) $stmt->fetchColumn(), true);

    expect($datos['to']['email'])->toBe($email)
        ->and($datos['template'])->toBe('mail/layout')
        ->and($datos['data']['btnhref'])->toContain('reset-password?token=' . recuperacion_token($email));
});

it('si el servidor de correo falla, el trabajo lanza para que la cola lo reintente', function (): void {
    $mail = new class extends app\services\MailService {
        public array $enviados = [];

        public function __construct() {}

        public function enviar(array $to, string $asunto, string $cuerpo, versaWYS\kernel\ContentType $tipo = versaWYS\kernel\ContentType::HTML, array $imagenesEmbed = []): bool
        {
            $this->enviados[] = $to['email'];

            return $to['email'] !== 'falla@prueba.test';
        }
    };
    $job = new app\jobs\EnviarCorreo($mail);

    $job->handle(['to' => ['email' => 'ok@prueba.test'], 'subject' => 'Hola', 'type' => 'html', 'body' => '<p>x</p>']);
    expect($mail->enviados)->toBe(['ok@prueba.test']);

    expect(fn() => $job->handle(['to' => ['email' => 'falla@prueba.test'], 'subject' => 'Hola', 'type' => 'html', 'body' => '<p>x</p>']))
        ->toThrow(RuntimeException::class);
});
