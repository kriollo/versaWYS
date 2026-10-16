<?php

declare(strict_types=1);

use versaWYS\kernel\Maintenance;
use versaWYS\kernel\Router;
use versaWYS\kernel\SignedUrl;
use versaWYS\kernel\Testing\TestClient;
use versaWYS\kernel\Testing\TestDatabase;

/*
 * Combinaciones de config.json que cambian lo que ve el usuario. Cada prueba cambia solo las claves
 * que le importan con TestClient::withConfig y la configuración vuelve a quedar como estaba.
 */

/** Un controlador mínimo para probar el middleware de enlaces firmados en una ruta real */
final class ConfiguracionTestDescargas
{
    public function factura(int $id): array
    {
        return ['success' => 1, 'factura' => $id];
    }
}

beforeAll(function (): void {
    TestDatabase::boot();
    Router::get('/prueba/facturas/{id}/pdf', [ConfiguracionTestDescargas::class, 'factura'])->middleware([
        [SignedUrl::class, 'check'],
    ]);
});

describe('panel en otra URL (admin.custom_url)', function (): void {
    it('se sirve bajo el prefijo propio y, con hide_default, /admin deja de existir', function (): void {
        TestClient::withConfig(['admin' => ['custom_url' => 'gestion', 'hide_default' => true]], function (): void {
            $client = TestDatabase::boot();

            $client->get('/gestion/login')->assertOk();
            $client->get('/admin/login')->assertStatus(404);
        });
    });

    it('sin hide_default, /admin sigue respondiendo', function (): void {
        TestClient::withConfig(['admin' => ['custom_url' => 'gestion', 'hide_default' => false]], function (): void {
            TestDatabase::boot()->get('/gestion/login')->assertOk();
        });
    });
});

describe('CORS (cors.allowed_origins)', function (): void {
    it('solo responde con credenciales al origen autorizado', function (): void {
        TestClient::withConfig(['cors' => ['allowed_origins' => ['https://app.ejemplo.cl']]], function (): void {
            $client = TestDatabase::boot();

            $autorizado = $client->withHeaders(['Origin' => 'https://app.ejemplo.cl'])->getJson('/up');
            expect($autorizado->header('Access-Control-Allow-Origin'))->toBe('https://app.ejemplo.cl');

            $otro = TestDatabase::boot()->withHeaders(['Origin' => 'https://otro.ejemplo.cl'])->getJson('/up');
            expect($otro->header('Access-Control-Allow-Origin'))->toBeNull();
        });
    });

    it('por defecto no autoriza a ningún origen', function (): void {
        $respuesta = TestDatabase::boot()->withHeaders(['Origin' => 'https://app.ejemplo.cl'])->getJson('/up');

        expect($respuesta->header('Access-Control-Allow-Origin'))->toBeNull();
    });
});

describe('cabeceras configurables', function (): void {
    it('con csp.enabled en false no se envía la CSP', function (): void {
        TestClient::withConfig(['csp' => ['enabled' => false]], function (): void {
            expect(TestDatabase::boot()->get('/admin/login')->header('Content-Security-Policy'))->toBeNull();
        });
    });

    it('permissions_policy define la cabecera, y vacía no se envía', function (): void {
        TestClient::withConfig(['permissions_policy' => 'camera=()'], function (): void {
            expect(TestDatabase::boot()->get('/admin/login')->header('Permissions-Policy'))->toBe('camera=()');
        });

        TestClient::withConfig(['permissions_policy' => ''], function (): void {
            expect(TestDatabase::boot()->get('/admin/login')->header('Permissions-Policy'))->toBeNull();
        });
    });
});

describe('websocket (websocket_service.ws_url)', function (): void {
    it('el panel recibe la URL y la CSP permite conectarse a ella', function (): void {
        TestClient::withConfig(['websocket_service' => ['ws_url' => 'wss://ws.ejemplo.cl:8443/socket']], function (): void {
            $client = TestDatabase::login(TestDatabase::user());
            expect($client->get('/admin/dashboard')->header('Content-Security-Policy'))->toContain('wss://ws.ejemplo.cl:8443');

            // El contenido de la navegación SPA trae el campo que lee vue-loader.ts: debe ser la URL tal cual
            $contenido = (string) $client->withHeaders(['X-Spa' => '1'])->get('/admin/dashboard')->json('content');
            preg_match('/id="ws_url" value="([^"]*)"/', $contenido, $campo);
            expect(html_entity_decode($campo[1] ?? '', ENT_QUOTES | ENT_HTML5))->toBe('wss://ws.ejemplo.cl:8443/socket');
        });
    });

    it('sin ws_url, la CSP no agrega ningún origen de websocket', function (): void {
        TestClient::withConfig(['websocket_service' => ['ws_url' => '']], function (): void {
            expect(TestDatabase::boot()->get('/admin/login')->header('Content-Security-Policy'))->not->toContain('wss://');
        });
    });
});

describe('modo mantenimiento en el panel', function (): void {
    it('quien entra con la clave ve el aviso de que el sitio está cerrado para los demás', function (): void {
        $equipo = TestDatabase::login(TestDatabase::user());
        $equipo->get('/admin/dashboard')->assertOk()->assertDontSee('maintenance-banner');
        $otro = TestDatabase::login(TestDatabase::user());

        Maintenance::down(secret: 'equipo-panel');

        try {
            $equipo->get('/equipo-panel');
            $equipo->get('/admin/dashboard')->assertOk()->assertSee('maintenance-banner');

            // El resto ve la página de mantenimiento, también con la sesión ya iniciada
            $otro->get('/admin/dashboard')->assertStatus(503);
        } finally {
            Maintenance::up();
        }

        $equipo->get('/admin/dashboard')->assertOk()->assertDontSee('maintenance-banner');
    });
});

describe('enlaces firmados (SignedUrl)', function (): void {
    it('el enlace vale sin sesión hasta que vence y no se puede alterar', function (): void {
        $client = TestDatabase::boot();
        $url = SignedUrl::make('/prueba/facturas/7/pdf', 60);

        $client->getJson($url)->assertOk()->assertJsonPath('factura', 7);

        // Otra factura con la misma firma, o el enlace sin firma
        $client->getJson(str_replace('/7/', '/8/', $url))->assertStatus(403);
        $client->getJson('/prueba/facturas/7/pdf')->assertStatus(403);

        $client->getJson(SignedUrl::make('/prueba/facturas/7/pdf', -1))->assertStatus(403);
    });

    it('un enlace firmado con otra clave no sirve', function (): void {
        $url = TestClient::withConfig(['crypt' => ['key' => base64_encode(random_bytes(32))]], static fn(): string =>
            SignedUrl::make('/prueba/facturas/7/pdf', 60));

        TestDatabase::boot()->getJson($url)->assertStatus(403);
    });
});
