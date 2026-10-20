<?php

declare(strict_types=1);

use app\services\DatabaseBackupService;
use versaWYS\kernel\File;

/*
 * Ciclo real de respaldo con pg_dump/psql: exportar, perder datos, importar y recuperarlos. Necesita un
 * PostgreSQL desechable (la prueba borra y crea tablas), indicado en VERSA_TEST_PG:
 *
 *   podman run -d --rm --name versa-pg-prueba -e POSTGRES_PASSWORD=prueba-local -e POSTGRES_DB=respaldo \
 *       -p 127.0.0.1:55432:5432 docker.io/library/postgres:18-alpine
 *   VERSA_TEST_PG=127.0.0.1:55432:respaldo:postgres:prueba-local composer test
 *
 * Sin la variable se salta: las demás pruebas usan SQLite, que no tiene cliente de respaldo.
 */

/** @return array{host: string, port: string, database: string, username: string, password: string}|null */
function respaldo_pg(): ?array
{
    $valor = getenv('VERSA_TEST_PG');
    if (!is_string($valor) || substr_count($valor, ':') !== 4) {
        return null;
    }

    [$host, $port, $database, $username, $password] = explode(':', $valor);

    return compact('host', 'port', 'database', 'username', 'password');
}

/** Corre la prueba con config.json apuntando al PostgreSQL desechable */
function respaldo_con_pg(array $pg, Closure $prueba): void
{
    $config = tempnam(sys_get_temp_dir(), 'versa_pg_') . '.json';
    file_put_contents($config, json_encode(['database' => ['driver' => 'pgsql'] + $pg]));
    $anterior = getenv('VERSAWYS_CONFIG');
    putenv('VERSAWYS_CONFIG=' . $config);
    try {
        $prueba(new PDO("pgsql:host={$pg['host']};port={$pg['port']};dbname={$pg['database']}", $pg['username'], $pg['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]));
    } finally {
        putenv($anterior === false ? 'VERSAWYS_CONFIG' : 'VERSAWYS_CONFIG=' . $anterior);
        unlink($config);
    }
}

it('exporta, y al importar el respaldo recupera los datos perdidos', function (): void {
    $pg = respaldo_pg();
    if ($pg === null) {
        $this->markTestSkipped('Sin VERSA_TEST_PG (PostgreSQL desechable)');
    }

    respaldo_con_pg($pg, function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS respaldo_prueba');
        $pdo->exec('CREATE TABLE respaldo_prueba (id SERIAL PRIMARY KEY, nombre TEXT NOT NULL)');
        $pdo->exec("INSERT INTO respaldo_prueba (nombre) VALUES ('uno'), ('dos con ''comillas'' y ñ')");

        $servicio = new DatabaseBackupService();
        $respaldo = $servicio->exportToFile();
        expect($respaldo['success'])->toBeTrue($respaldo['error'] ?? '');
        $archivo = $servicio->getDumpPath($respaldo['token']);
        expect($archivo)->toBeFile();

        try {
            // Se pierden datos y aparece una tabla que el respaldo no tiene
            $pdo->exec('DELETE FROM respaldo_prueba');
            $pdo->exec('CREATE TABLE respaldo_posterior (id INT)');

            $importado = $servicio->importFromFile(new File([
                'name' => 'respaldo.sql',
                'type' => 'application/sql',
                'tmp_name' => $archivo,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($archivo),
            ]));
            expect($importado['success'])->toBeTrue($importado['error'] ?? '');

            expect($pdo->query('SELECT nombre FROM respaldo_prueba ORDER BY id')->fetchAll(PDO::FETCH_COLUMN))
                ->toBe(['uno', "dos con 'comillas' y ñ"]);
            // La secuencia sigue después de los datos restaurados
            $pdo->exec("INSERT INTO respaldo_prueba (nombre) VALUES ('tres')");
            expect((int) $pdo->query("SELECT id FROM respaldo_prueba WHERE nombre = 'tres'")->fetchColumn())->toBe(3);
            // --clean solo reemplaza lo que el respaldo trae: lo creado después sigue ahí
            expect((int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'respaldo_posterior'")->fetchColumn())
                ->toBe(1);
        } finally {
            $servicio->deleteDump($respaldo['token']);
            $pdo->exec('DROP TABLE IF EXISTS respaldo_prueba, respaldo_posterior');
        }

        expect($servicio->getDumpPath($respaldo['token']))->toBeNull();
    });
});

it('un respaldo inválido no deja la base a medias: psql corre en una sola transacción', function (): void {
    $pg = respaldo_pg();
    if ($pg === null) {
        $this->markTestSkipped('Sin VERSA_TEST_PG (PostgreSQL desechable)');
    }

    respaldo_con_pg($pg, function (PDO $pdo): void {
        $pdo->exec('DROP TABLE IF EXISTS respaldo_prueba');
        $pdo->exec("CREATE TABLE respaldo_prueba (nombre TEXT); INSERT INTO respaldo_prueba VALUES ('intacto')");
        $malo = tempnam(sys_get_temp_dir(), 'versa_sql_');
        file_put_contents($malo, "DELETE FROM respaldo_prueba;\nESTO NO ES SQL;\n");

        try {
            $resultado = (new DatabaseBackupService())->importFromFile(new File([
                'name' => 'malo.sql', 'type' => 'application/sql', 'tmp_name' => $malo, 'error' => UPLOAD_ERR_OK, 'size' => filesize($malo),
            ]));

            expect($resultado['success'])->toBeFalse();
            expect($pdo->query('SELECT nombre FROM respaldo_prueba')->fetchAll(PDO::FETCH_COLUMN))->toBe(['intacto']);
        } finally {
            unlink($malo);
            $pdo->exec('DROP TABLE IF EXISTS respaldo_prueba');
        }
    });
});
