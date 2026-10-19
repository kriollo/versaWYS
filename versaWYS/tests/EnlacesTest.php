<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestDatabase;

/*
 * Ningún enlace interno del panel lleva a una página que no existe (menú, cabecera y pie).
 */

beforeAll(fn() => TestDatabase::boot());

it('cada enlace del panel del superadministrador abre una página', function (): void {
    $super = TestDatabase::login(TestDatabase::user('superadmin'));
    $html = $super->get('/admin/dashboard')->assertOk()->body;

    preg_match_all('/href="(\/admin\/[^"#?]*)"/', $html, $m);
    $enlaces = array_diff(array_unique($m[1]), ['/admin/logout']);
    expect($enlaces)->not->toBeEmpty();

    $rotos = array_values(array_filter($enlaces, static fn(string $url): bool => $super->get($url)->status === 404));
    expect($rotos)->toBe([]);
});
