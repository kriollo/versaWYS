<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\Factory;
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
