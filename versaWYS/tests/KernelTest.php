<?php

declare(strict_types=1);

use app\models\Users;
use versaWYS\kernel\helpers\Functions;

/** Ejecuta versaCLI y devuelve [código de salida, salida]. */
function versaCli(string $args): array
{
    exec('php versaCLI ' . $args . ' 2>&1 < /dev/null', $output, $exitCode);

    return [$exitCode, implode("\n", $output)];
}

describe('config.default.php', function (): void {
    $defaults = require 'versaWYS/kernel/config/config.default.php';

    it('nace con valores seguros', function () use ($defaults): void {
        expect($defaults['build']['debug'])->toBeFalse()
            ->and($defaults['session']['user_cookie']['secure'])->toBeTrue()
            ->and($defaults['session']['user_cookie']['http_only'])->toBeTrue()
            ->and($defaults['cors']['allowed_origins'])->toBe([]);
    });

    it('no trae claves ni contraseñas fijas', function () use ($defaults): void {
        expect($defaults['session']['key'])->toBe('')
            ->and($defaults['session']['user_cookie']['key_encript'])->toBe('')
            ->and($defaults['database']['password'])->toBe('');
    });

    it('declara las secciones que el kernel lee sin valor por defecto', function () use ($defaults): void {
        expect($defaults)->toHaveKeys(['database', 'admin', 'twig', 'build', 'auth', 'session', 'mail', 'logging', 'rate_limiting', 'assets'])
            ->and($defaults['database'])->toHaveKeys(['engine', 'driver', 'host', 'port', 'charset', 'collation', 'database', 'username', 'password'])
            ->and($defaults['session']['user_cookie'])->toHaveKeys(['domain', 'secure', 'http_only', 'same_site', 'strict_mode']);
    });
});

describe('versaCLI', function (): void {
    it('rechaza rutas en los nombres de generadores y borradores', function (string $command): void {
        [$exitCode, $output] = versaCli($command);

        expect($exitCode)->toBe(1)->and($output)->toContain('Parámetro inválido');
    })->with([
        'model:delete:../../index',
        'controller:make:foo/bar',
        'logs:view:../kernel/config/config.json',
    ]);

    it('no ejecuta comandos destructivos sin confirmación', function (string $command): void {
        // stdin vacío equivale a responder "no"
        [, $output] = versaCli($command);

        expect($output)->toContain('Operación cancelada');
    })->with(['migrate:dropAll', 'migrate:refresh']);
});

describe('Functions::validateParams', function (): void {
    it('aplica required y min', function (): void {
        expect(Functions::validateParams(['name' => 'Z'], ['name' => 'required|min:2']))->toHaveKey('name')
            ->and(Functions::validateParams(['name' => ''], ['name' => 'required|min:2']))->toHaveKey('name')
            ->and(Functions::validateParams(['name' => 'Ana'], ['name' => 'required|min:2']))->toBe([]);
    });
});

describe('token de recuperación de contraseña', function (): void {
    it('lleva su expiración y no se repite', function (): void {
        $token = Users::newResetToken();
        $expires = (int) substr((string) strrchr($token, '.'), 1);

        expect($expires)->toBeGreaterThan(time())->toBeLessThanOrEqual(time() + 3600)
            ->and(Users::newResetToken())->not->toBe($token)
            ->and(strlen($token))->toBeLessThanOrEqual(255);
    });
});
