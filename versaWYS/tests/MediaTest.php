<?php

declare(strict_types=1);

use versaWYS\kernel\Testing\TestClient;
use versaWYS\kernel\Testing\TestDatabase;

/*
 * mediaStore: los archivos quedan en una carpeta pública del sitio, así que lo que se acepta y cómo
 * se guarda es una cuestión de seguridad (ejecutables, XSS en SVG), además del aislamiento por empresa.
 */

const MEDIA_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

beforeAll(fn() => TestDatabase::boot());

/** Un archivo temporal con ese contenido y ese nombre */
function media_archivo(string $nombre, string $contenido): string
{
    $dir = sys_get_temp_dir() . '/versa_media_' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir . '/' . $nombre, $contenido);

    return $dir . '/' . $nombre;
}

/** La carpeta pública de prueba (relativa a la raíz, como en config.json) y la empresa con su sesión */
function media_contexto(): array
{
    $empresa = TestDatabase::empresa('Con media');
    $token = (string) TestDatabase::pdo()->query("SELECT token_empresa FROM empresas WHERE id = {$empresa}")->fetchColumn();
    $client = TestDatabase::login(TestDatabase::user('admin', $empresa));
    // Estas rutas piden el token CSRF también en el cuerpo (csrf_token), como lo manda el panel
    preg_match('/id="csrf_token" value="([^"]+)"/', $client->get('/admin/dashboard')->body, $csrf);

    return [$client, $token, $csrf[1] ?? '', $empresa];
}

/** @param array<string, mixed> $datos */
function media_subir(TestClient $client, string $token, string $csrf, string $archivo, string $tipo = 'image', array $datos = []): array
{
    return (array) $client->postFile("/admin/mediaStore/api/upload/{$token}", ['media_type' => $tipo, 'csrf_token' => $csrf] + $datos, [
        'media' => $archivo,
    ])->json();
}

function media_con_carpeta(Closure $prueba): void
{
    $dist = 'versaWYS/cache/test-media-' . bin2hex(random_bytes(4));
    try {
        TestClient::withConfig(['assets' => ['dashboard' => ['store' => ['dist' => $dist]]]], $prueba);
    } finally {
        exec('rm -rf ' . escapeshellarg(dirname(__DIR__, 2) . '/' . $dist));
    }
}

describe('archivos', function (): void {
    it('sube, lista, renombra, mueve a una carpeta y elimina', function (): void {
        media_con_carpeta(function (): void {
            [$client, $token, $csrf] = media_contexto();

            $subida = media_subir($client, $token, $csrf, media_archivo('foto.png', base64_decode(MEDIA_PNG, true)));
            expect($subida['success'])->toBe(1);
            $archivo = $subida['data'][0];
            // El id es el del registro, no 1 (save() devuelve un arreglo y (int) de un arreglo es 1)
            expect((int) TestDatabase::pdo()->query("SELECT id FROM media_store WHERE stored_name = '" . basename($archivo['url']) . "'")->fetchColumn())
                ->toBe((int) $archivo['id']);
            $segundo = media_subir($client, $token, $csrf, media_archivo('otra.png', base64_decode(MEDIA_PNG, true)))['data'][0];
            expect($segundo['id'])->not->toBe($archivo['id']);
            expect(dirname(__DIR__, 2) . $archivo['url'])->toBeFile()
                ->and($archivo['url'])->toEndWith('.png')->and($archivo['mime'])->toBe('image/png');

            $lista = $client->getJson("/admin/mediaStore/api/list/{$token}")->assertOk();
            expect(array_column($lista->json('data'), 'original_name'))->toContain('foto.png');

            $client->postJson("/admin/mediaStore/api/rename/{$token}", ['id' => $archivo['id'], 'new_name' => 'portada.png', 'csrf_token' => $csrf])
                ->assertOk();

            $carpeta = $client->postJson("/admin/mediaStore/api/folders/{$token}", ['name' => 'Fotos', 'csrf_token' => $csrf])
                ->assertOk()->json('data.id');
            $client->postJson("/admin/mediaStore/api/move/{$token}", ['id' => $archivo['id'], 'folder_id' => $carpeta, 'csrf_token' => $csrf])
                ->assertOk();
            $enCarpeta = $client->getJson("/admin/mediaStore/api/list/{$token}", ['folder_id' => $carpeta])->assertOk();
            expect(array_column($enCarpeta->json('data'), 'original_name'))->toBe(['portada.png']);

            $client->postJson("/admin/mediaStore/api/delete/{$token}", ['id' => $archivo['id'], 'csrf_token' => $csrf])->assertOk();
            expect(array_column($client->getJson("/admin/mediaStore/api/list/{$token}")->json('data'), 'original_name'))
                ->toBe(['otra.png']);
        });
    });

    it('rechaza ejecutables, contenido que no corresponde y extensiones de otro tipo', function (): void {
        media_con_carpeta(function (): void {
            [$client, $token, $csrf] = media_contexto();
            $png = base64_decode(MEDIA_PNG, true);

            foreach ([
                ['shell.php', $png, 'image'],
                ['script.js', 'alert(1)', 'file'],
                ['texto.png', 'solo texto', 'image'],
                ['pagina.txt', '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>', 'file'],
                ['foto.png', $png, 'video'],
            ] as [$nombre, $contenido, $tipo]) {
                $r = media_subir($client, $token, $csrf, media_archivo($nombre, $contenido), $tipo);
                expect($r['success'])->toBe(0, "{$nombre} como {$tipo}");
            }

            global $config;
            $dist = dirname(__DIR__, 2) . '/' . $config['assets']['dashboard']['store']['dist'];
            expect(glob($dist . '/media/*/*/*/*') ?: [])->toBe([], 'nada rechazado quedó en disco');
        });
    });

    it('un SVG se guarda sin nada ejecutable', function (): void {
        media_con_carpeta(function (): void {
            [$client, $token, $csrf] = media_contexto();
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script><path d="M0 0h1v1z"/></svg>';

            $r = media_subir($client, $token, $csrf, media_archivo('logo.svg', $svg));

            expect($r['success'])->toBe(1);
            $guardado = (string) file_get_contents(dirname(__DIR__, 2) . $r['data'][0]['url']);
            expect($guardado)->toContain('<path d="M0 0h1v1z"/>')->not->toContain('script')->not->toContain('onload');
        });
    });

    it('de un ZIP extrae lo permitido y descarta el resto', function (): void {
        media_con_carpeta(function (): void {
            [$client, $token, $csrf] = media_contexto();
            $zip = sys_get_temp_dir() . '/versa_' . bin2hex(random_bytes(4)) . '.zip';
            $z = new ZipArchive();
            $z->open($zip, ZipArchive::CREATE);
            $z->addFromString('buena.png', base64_decode(MEDIA_PNG, true));
            $z->addFromString('mala.php', '<?php system($_GET["c"]);');
            $z->addFromString('../../escape.png', base64_decode(MEDIA_PNG, true));
            $z->addFromString('dibujo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1"/></svg>');
            $z->close();

            $r = media_subir($client, $token, $csrf, $zip);

            $ok = array_values(array_filter($r['data'], static fn(array $f): bool => $f['success'] === true));
            expect(array_column($ok, 'name'))->toEqualCanonicalizing(['buena.png', 'dibujo.svg']);
            foreach ($ok as $f) {
                expect((string) file_get_contents(dirname(__DIR__, 2) . $f['url']))->not->toContain('script');
            }
        });
    });

    it('sin el csrf_token del cuerpo no sube nada', function (): void {
        media_con_carpeta(function (): void {
            [$client, $token] = media_contexto();

            $client->postFile("/admin/mediaStore/api/upload/{$token}", ['media_type' => 'image'], ['media' => media_archivo('a.png', 'x')])
                ->assertStatus(422);
        });
    });
});

describe('carpetas', function (): void {
    it('crea, renombra y solo elimina una carpeta vacía', function (): void {
        media_con_carpeta(function (): void {
            [$client, $token, $csrf] = media_contexto();

            $padre = $client->postJson("/admin/mediaStore/api/folders/{$token}", ['name' => 'Padre', 'csrf_token' => $csrf])->json('data.id');
            $hija = $client->postJson("/admin/mediaStore/api/folders/{$token}", ['name' => 'Hija', 'parent_folder_id' => $padre, 'csrf_token' => $csrf])
                ->assertOk()->json('data.id');

            $client->postJson("/admin/mediaStore/api/folders/rename/{$token}", ['id' => $hija, 'new_name' => 'Renombrada', 'csrf_token' => $csrf])
                ->assertOk();
            $hijas = $client->getJson("/admin/mediaStore/api/folders/{$token}", ['parent_folder_id' => $padre])->json('data');
            expect(array_column($hijas, 'name'))->toBe(['Renombrada']);

            expect($client->postJson("/admin/mediaStore/api/folders/delete/{$token}", ['id' => $padre, 'csrf_token' => $csrf])->json('success'))
                ->toBe(0, 'tiene una subcarpeta');
            $client->postJson("/admin/mediaStore/api/folders/delete/{$token}", ['id' => $hija, 'csrf_token' => $csrf])->assertOk();
            $client->postJson("/admin/mediaStore/api/folders/delete/{$token}", ['id' => $padre, 'csrf_token' => $csrf])->assertOk();
        });
    });
});

describe('aislamiento por empresa', function (): void {
    it('otra empresa no ve, renombra, mueve ni elimina los archivos y carpetas, aunque use el token en la URL', function (): void {
        media_con_carpeta(function (): void {
            [$duena, $token, $csrf] = media_contexto();
            $archivo = media_subir($duena, $token, $csrf, media_archivo('privada.png', base64_decode(MEDIA_PNG, true)))['data'][0];
            $carpeta = $duena->postJson("/admin/mediaStore/api/folders/{$token}", ['name' => 'Privada', 'csrf_token' => $csrf])->json('data.id');

            [$otra, $tokenOtra, $csrfOtra] = media_contexto();
            foreach ([$token, $tokenOtra] as $t) {
                expect(array_column($otra->getJson("/admin/mediaStore/api/list/{$t}")->json('data'), 'original_name'))
                    ->not->toContain('privada.png');
                $otra->postJson("/admin/mediaStore/api/rename/{$t}", ['id' => $archivo['id'], 'new_name' => 'x.png', 'csrf_token' => $csrfOtra])
                    ->assertStatus(404);
                $otra->postJson("/admin/mediaStore/api/delete/{$t}", ['id' => $archivo['id'], 'csrf_token' => $csrfOtra])->assertStatus(404);
                $otra->postJson("/admin/mediaStore/api/move/{$t}", ['id' => $archivo['id'], 'folder_id' => '', 'csrf_token' => $csrfOtra])
                    ->assertStatus(404);
                expect($otra->postJson("/admin/mediaStore/api/folders/rename/{$t}", ['id' => $carpeta, 'new_name' => 'x', 'csrf_token' => $csrfOtra])->json('success'))
                    ->toBe(0);
            }

            // Ni puede colgar sus archivos o carpetas de una carpeta ajena
            $propio = media_subir($otra, $tokenOtra, $csrfOtra, media_archivo('mia.png', base64_decode(MEDIA_PNG, true)))['data'][0];
            $otra->postJson("/admin/mediaStore/api/move/{$tokenOtra}", ['id' => $propio['id'], 'folder_id' => $carpeta, 'csrf_token' => $csrfOtra])
                ->assertStatus(404);
            $otra->postJson("/admin/mediaStore/api/folders/{$tokenOtra}", ['name' => 'Intrusa', 'parent_folder_id' => $carpeta, 'csrf_token' => $csrfOtra])
                ->assertStatus(404);
            expect(media_subir($otra, $tokenOtra, $csrfOtra, media_archivo('b.png', base64_decode(MEDIA_PNG, true)), 'image', ['folder_id' => $carpeta])['success'])
                ->toBe(0);
        });
    });

    it('un token de empresa inexistente responde 404', function (): void {
        [$client] = media_contexto();

        $client->getJson('/admin/mediaStore/api/list/no-existe')->assertStatus(404);
    });
});
