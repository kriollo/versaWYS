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

/** Invoca un método privado estático del kernel. */
function kernelStatic(string $class, string $method, mixed ...$args): mixed
{
    return (new ReflectionMethod($class, $method))->invoke(null, ...$args);
}

describe('filtros de listados', function (): void {
    $parse = static fn (string $raw, array $allowed): array => kernelStatic(versaWYS\kernel\GlobalControllers::class, 'parseExternalFilters', $raw, $allowed);
    $order = static fn (string $raw): string => kernelStatic(versaWYS\kernel\GlobalControllers::class, 'sanitizeOrder', $raw, 'id,asc');

    it('convierte las condiciones permitidas en marcadores y valores', function () use ($parse): void {
        expect($parse('status = true AND id_menu=5', ['status' => 'u.status', 'id_menu']))
            ->toBe([['u.status = ?', 'id_menu = ?'], [true, 5]]);
    });

    it('descarta columnas no permitidas y nunca deja SQL del cliente en la condición', function () use ($parse): void {
        expect($parse("password>'a'", ['status']))->toBe([[], []])
            ->and($parse('1=1; DROP TABLE x', ['status']))->toBe([[], []])
            ->and($parse('status=false OR 1=1', ['status']))->toBe([['status = ?'], ['false OR 1=1']]);
    });

    it('solo acepta orden por un identificador y una dirección', function () use ($order): void {
        expect($order('name,desc'))->toBe('name DESC')
            ->and($order('versa_users.email'))->toBe('versa_users.email ASC')
            ->and($order('id;DROP TABLE versa_users'))->toBe('id ASC')
            ->and($order('(select 1)'))->toBe('id ASC')
            ->and($order('password,asc'))->toBe('id ASC');
    });
});

describe('archivos subidos', function (): void {
    it('no acepta destinos que el servidor web pueda ejecutar u ocultar', function (string $destination): void {
        $file = new versaWYS\kernel\File(['name' => 'a.png', 'type' => 'image/png', 'tmp_name' => '', 'error' => 0, 'size' => 1]);

        expect(static fn () => (new ReflectionMethod($file, 'assertSafeDestination'))->invoke($file, $destination))
            ->toThrow(RuntimeException::class);
    })->with(['public/x/shell.php', 'public/x/shell.PHP5', 'public/x/a.phtml', 'public/x/a.php.jpg', 'public/x/.htaccess', 'public/x/.env']);
});

describe('documentación de rutas', function (): void {
    $source = <<<'PHP'
        <?php

        namespace app\routes;

        use app\controllers\UsersController;
        use app\middleware\AuthMiddleware;
        use versaWYS\kernel\Router;

        // description: Grupo de usuarios
        Router::group(
            '/admin/users',
            function (): void {
                // description: Lista usuarios
                Router::get('', [UsersController::class, 'getUsersPaginated']);

                // description: Edita un usuario
                // path-param name:id type:int description:ID del usuario
                Router::put('/edit/{id}', [
                    UsersController::class,
                    'editUser',
                ])->middleware([
                    [AuthMiddleware::class, 'onlyAdmin'],
                ]);

                Router::group('/api', function (): void {
                    Router::post('/hook/{token}', [UsersController::class, 'noExiste'])->withoutCsrf();
                });
            },
            [[AuthMiddleware::class, 'checkSession']],
        );

        Router::delete('/x', [\app\controllers\Fantasma::class, 'borrar'])->middleware([[AuthMiddleware::class, 'checkSession']])->withoutCsrf();
        PHP;

    $routes = versaWYS\kernel\cli\RouteManager::parseRouteSource($source, 'panel');
    $messages = static fn (array $route): string => implode(' | ', array_column($route['issues'], 'message'));

    it('reconoce grupos anidados, prefijos y la URL real del panel', function () use ($routes): void {
        expect(array_column($routes, 'route'))->toBe(['/admin/users', '/admin/users/edit/{id}', '/admin/users/api/hook/{token}', '/x'])
            ->and(array_column($routes, 'url'))->toBe(['/panel/users', '/panel/users/edit/{id}', '/panel/users/api/hook/{token}', '/x'])
            ->and(array_column($routes, 'method'))->toBe(['GET', 'PUT', 'POST', 'DELETE']);
    });

    it('lee el controlador aunque esté en varias líneas y hereda middlewares y descripción del grupo', function () use ($routes): void {
        expect($routes[1]['class_method'])->toBe('editUser')
            ->and(array_column($routes[1]['middleware'], 'class_method'))->toBe(['checkSession', 'onlyAdmin'])
            ->and($routes[1]['pathParams'][0]['name'])->toBe('id')
            ->and($routes[2]['description'])->toBe('Grupo de usuarios');
    });

    it('indica la protección CSRF de cada ruta', function () use ($routes): void {
        expect(array_column($routes, 'csrf'))->toBe(['none', 'protected', 'exempt', 'exempt']);
    });

    it('detecta controladores inexistentes y documentación faltante', function () use ($routes, $messages): void {
        expect($routes[0]['issues'])->toBe([])
            ->and($routes[1]['issues'])->toBe([])
            ->and($messages($routes[2]))->toContain('noExiste() no existe')->toContain('path-param name:token')
            ->and($messages($routes[3]))->toContain('la clase app\controllers\Fantasma no existe')->toContain('Falta `// description:`');
    });

    it('el starter no tiene rutas rotas ni sin documentar', function (): void {
        $pending = [];
        foreach (versaWYS\kernel\cli\RouteManager::extractAllRouteData() as $file => $fileRoutes) {
            foreach ($fileRoutes as $route) {
                foreach ($route['issues'] as $issue) {
                    $pending[] = "{$file} {$route['method']} {$route['route']}: {$issue['message']}";
                }
            }
        }

        expect($pending)->toBe([]);
    });

    it('rechaza nombres de archivo fuera de app/routes', function (): void {
        versaWYS\kernel\cli\RouteManager::extractRouteData('../../versaWYS/kernel/config/config.php');
    })->throws(InvalidArgumentException::class);
});

describe('respuesta SPA', function (): void {
    $twig = (new ReflectionClass(versaWYS\kernel\versaTwig::class))->newInstanceWithoutConstructor();
    $call = static fn (string $method, string $html): mixed => (new ReflectionMethod($twig, $method))->invoke($twig, $html);
    $html = '<div id="a">hola</div>'
        . '<script type="application/json" id="datos">{"a":1}</script>'
        . "<script>if (1 < 2) { window.x = 1; }\n</script>"
        . '<script type="module">import { a } from "/a.js";</script>'
        . '<script src="/public/pagina.js"></script>';

    it('saca del contenido los scripts ejecutables y conserva los bloques de datos', function () use ($call, $html): void {
        $content = $call('extractBodyContent', $html);

        expect($content)->toContain('<div id="a">hola</div>')->toContain('type="application/json"')
            ->not->toContain('window.x')->not->toContain('pagina.js')->not->toContain('import {');
    });

    it('entrega cada script ejecutable una sola vez, incluidos los que contienen "<"', function () use ($call, $html): void {
        $scripts = $call('extractPageSpecificScripts', $html);

        expect(array_column($scripts, 'src'))->toBe(['/public/pagina.js'])
            ->and(array_column($scripts, 'content'))->toBe(['if (1 < 2) { window.x = 1; }', 'import { a } from "/a.js";']);
    });
});

describe('apariencia del panel', function (): void {
    it('define en Tailwind las paletas propias que usan las plantillas y componentes', function (): void {
        $config = (string) file_get_contents('tailwind.config.js');
        $sources = '';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/templates', FilesystemIterator::SKIP_DOTS)) as $file) {
            $sources .= file_get_contents($file->getPathname());
        }

        // Sin la paleta en la configuración, clases como bg-brand no generan ningún estilo
        foreach (['brand', 'corporate-orange'] as $palette) {
            if (preg_match('/\b(?:bg|text|border|ring)-' . $palette . '\b/', $sources) === 1 || $palette === 'corporate-orange') {
                expect($config)->toMatch('/[\'"]?' . preg_quote($palette, '/') . '[\'"]?\s*:\s*\{/');
            }
        }
    });
});

describe('plantillas', function (): void {
    it('ningún script en línea contiene etiquetas de documento como texto', function (): void {
        // El proxy de desarrollo inserta su script en el primer cierre de body de la respuesta y la
        // navegación SPA busca el body con expresiones regulares: si esas etiquetas aparecen dentro de
        // un texto de JavaScript, el script se corta y su código queda visible en la página.
        $offenders = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/templates', FilesystemIterator::SKIP_DOTS)) as $file) {
            preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', (string) file_get_contents($file->getPathname()), $scripts);
            foreach ($scripts[1] as $script) {
                if (preg_match('/<\/?(body|html|head)\b/i', $script) === 1) {
                    $offenders[] = $file->getPathname();
                }
            }
        }

        expect($offenders)->toBe([]);
    });
});
