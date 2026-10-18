<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestClient;
use versaWYS\kernel\Testing\TestDatabase;

/*
 * La sesión en el tiempo: el heartbeat del panel mantiene viva la sesión y renueva el token antes de
 * que venza, sin alargar la de una cuenta desactivada, y respetando el «recordarme».
 */

beforeAll(fn() => TestDatabase::boot());

function sesion_heartbeat(TestClient $client): versaWYS\kernel\Testing\TestResponse
{
    return $client->getJson('/admin/api/session/heartbeat');
}

it('sin sesión el heartbeat responde 401', function (): void {
    sesion_heartbeat(TestDatabase::boot())->assertStatus(401)->assertJsonPath('valid', false);
});

it('con la sesión recién iniciada no renueva el token', function (): void {
    $client = TestDatabase::login(TestDatabase::user());
    $token = $client->cookies()['tknHash'] ?? null;

    $respuesta = sesion_heartbeat($client)->assertOk();

    expect($respuesta->json('valid'))->toBeTrue()
        ->and($respuesta->json('sessionRenewed'))->toBeFalse()
        ->and($respuesta->json('timeRemaining'))->toBeGreaterThan(0)
        ->and($client->cookies()['tknHash'] ?? null)->toBe($token);
});

it('cerca del vencimiento renueva el token, y el nuevo sirve', function (): void {
    $client = TestDatabase::login(TestDatabase::user());
    $token = $client->cookies()['tknHash'] ?? null;

    // Un lifetime mayor que lo que le queda al token: para el heartbeat ya está «cerca de vencer»
    TestClient::withConfig(['session' => ['user_cookie' => ['lifetime' => 100_000]]], function () use ($client): void {
        sesion_heartbeat($client)->assertOk()->assertJsonPath('sessionRenewed', true);
    });

    expect($client->cookies()['tknHash'] ?? null)->not->toBe($token);
    $client->getJson('/admin/perfiles/all')->assertOk();
});

it('una cuenta desactivada no obtiene un token nuevo', function (): void {
    $email = TestDatabase::user();
    $client = TestDatabase::login($email);
    TestDatabase::pdo()->prepare('UPDATE versa_users SET status = 0, updated_at = ? WHERE email = ?')
        ->execute([date('Y-m-d H:i:s', time() + 5), $email]);

    TestClient::withConfig(['session' => ['user_cookie' => ['lifetime' => 100_000]]], function () use ($client): void {
        sesion_heartbeat($client)->assertStatus(401);
    });
});

it('con «recordarme» la sesión dura lo de lifetime_remember y no lo de una sesión normal', function (): void {
    $email = TestDatabase::user();
    $client = TestDatabase::boot();
    $client->get('/admin/login');
    $client->postJson('/admin/login/autentication', ['email' => $email, 'password' => TestDatabase::PASSWORD, 'remember' => 'on'])->assertOk();

    global $config;
    $normal = (int) $config['session']['user_cookie']['lifetime'];
    $recordar = (int) $config['session']['user_cookie']['lifetime_remember'];

    expect($recordar)->toBeGreaterThan($normal)
        ->and(sesion_heartbeat($client)->assertOk()->json('timeRemaining'))->toBeGreaterThan($normal);
});
