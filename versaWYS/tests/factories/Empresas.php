<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\Factory;

// Los datos por defecto de una empresa de prueba. $n no se repite: sirve para lo que debe ser único.
// En una prueba: Factory::create('empresas') o Factory::createMany('empresas', 20, ['estado' => 0]).
Factory::define('empresas', static fn(int $n): array => [
    'token_empresa' => bin2hex(random_bytes(16)),
    'rut' => sprintf('7%07d-%d', $n, $n % 10),
    'nombre' => "Empresa de prueba {$n}",
    'email' => "empresa{$n}-" . bin2hex(random_bytes(3)) . '@prueba.test',
    'estado' => 1,
]);
