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
    $defaults = versaWYS\kernel\cli\ConfigManager::defaults();

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
        // stdin vacío equivale a responder "no"; código 1 porque lo pedido no se hizo
        [$exitCode, $output] = versaCli($command);

        expect($output)->toContain('Operación cancelada')->and($exitCode)->toBe(1);
    })->with(['migrate:dropAll', 'migrate:refresh']);
});

describe('Functions::validateParams', function (): void {
    it('aplica required y min', function (): void {
        expect(Functions::validateParams(['name' => 'Z'], ['name' => 'required|min:2']))->toHaveKey('name')
            ->and(Functions::validateParams(['name' => ''], ['name' => 'required|min:2']))->toHaveKey('name')
            ->and(Functions::validateParams(['name' => 'Ana'], ['name' => 'required|min:2']))->toBe([]);
    });

    it('min y max aceptan números enviados por JSON', function (): void {
        expect(Functions::validateParams(['id' => 7], ['id' => 'required|numeric|min:1|max:3']))->toBe([]);
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
        // Tailwind 4: las paletas están en el @theme de dashboard.css
        $config = (string) file_get_contents('src/dashboard/css/dashboard.css');
        $sources = '';
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/templates', FilesystemIterator::SKIP_DOTS)) as $file) {
            $sources .= file_get_contents($file->getPathname());
        }

        // Sin la paleta en la configuración, clases como bg-brand no generan ningún estilo
        foreach (['brand', 'corporate-orange'] as $palette) {
            if (preg_match('/\b(?:bg|text|border|ring)-' . $palette . '\b/', $sources) === 1 || $palette === 'corporate-orange') {
                expect($config)->toMatch('/--color-' . preg_quote($palette, '/') . '(-500)?\s*:/');
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

describe('revisión previa al despliegue', function (): void {
    it('el proyecto no tiene nada que impida desplegar', function (): void {
        [$exitCode, $output] = versaCli('app:check:deploy');

        expect($exitCode)->toBe(0, $output);
    });

    it('localiza rutas distinguiendo mayúsculas, como Linux', function (): void {
        $locate = versaWYS\kernel\cli\commands\CheckPsr4Command::locatePath(...);
        $root = (string) getcwd();

        [$found, $diff] = $locate($root . '/app/models/Users.php');
        expect($found)->toBe($root . '/app/models/Users.php')->and($diff)->toBeNull();

        [$found, $diff] = $locate($root . '/app/Models/users.php');
        expect($found)->toBe($root . '/app/models/Users.php')->and($diff)->toBe(['used' => 'Models', 'expected' => 'models']);

        expect($locate($root . '/app/models/NoExiste.php'))->toBe([null, null]);
    });

    it('distingue un secreto de un texto corriente', function (string $value, bool $expected): void {
        $command = (new ReflectionClass(versaWYS\kernel\cli\commands\CheckDeployCommand::class))->newInstanceWithoutConstructor();

        expect((new ReflectionMethod($command, 'looksLikeSecret'))->invoke($command, $value))->toBe($expected);
    })->with([
        ['SuperSecreta123', true],
        ['abcdef1234567890abcdef', true],
        ['required|min:8', false],
        ['La contraseña no es válida', false],
        ['password', false],
        ['corta1A', false],
    ]);
});

describe('documentación de rutas: comentarios', function (): void {
    it('no cuenta como activo un middleware comentado dentro del arreglo', function (): void {
        $routes = versaWYS\kernel\cli\RouteManager::parseRouteSource(<<<'PHP'
            <?php

            namespace app\routes;

            use app\controllers\UsersController;
            use app\middleware\AuthMiddleware;
            use versaWYS\kernel\Router;

            // description: Formulario de alta
            Router::get('/admin/usuarios/addUser', [UsersController::class, 'addUserTemplate'])->middleware([
                // [NoExisteMiddleware::class, 'validar'],
                [AuthMiddleware::class, 'checkSession'],
            ]);
            PHP, 'admin');

        expect(array_column($routes[0]['middleware'], 'class_method'))->toBe(['checkSession'])
            ->and($routes[0]['issues'])->toBe([]);
    });
});

describe('kernel empaquetable', function (): void {
    it('no asume vivir en versaWYS/kernel: así funciona instalado en vendor', function (): void {
        // Permitido: leer proyectos anteriores al paquete y saltar frames del kernel en las trazas
        $allowed = ['cli/ConfigManager.php', 'cli/commands/CheckDeployCommand.php', 'cli/commands/CheckPsr4Command.php', 'model/VersaORMModel.php'];
        $kernel = dirname((string) (new ReflectionClass(versaWYS\kernel\App::class))->getFileName());
        $offending = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($kernel, FilesystemIterator::SKIP_DOTS)) as $file) {
            $relative = substr($file->getPathname(), strlen($kernel) + 1);
            if ($file->getExtension() !== 'php' || str_starts_with($relative, 'vendor/') || in_array($relative, $allowed, true)) {
                continue;
            }
            if (preg_match("#versaWYS/kernel/|__DIR__ \\. '/\\.\\./\\.\\./#", (string) file_get_contents($file->getPathname())) === 1) {
                $offending[] = $relative;
            }
        }

        expect($offending)->toBe([]);
    });
});

describe('config:merge', function (): void {
    it('aplica un JSON versionado sobre config.json: las listas se reemplazan enteras y repetirlo no cambia nada', function (): void {
        $dir = sys_get_temp_dir() . '/versawys_merge_' . getmypid();
        @mkdir($dir, 0o700, true);
        file_put_contents($dir . '/config.json', json_encode([
            'session' => ['key' => 'secreta'],
            'csp' => ['script-src' => ['a.example', 'b.example', 'c.example']],
        ]));
        file_put_contents($dir . '/extra.json', json_encode(['csp' => ['script-src' => ['nuevo.example'], 'form-action' => ['pago.example']]]));
        $env = 'VERSAWYS_CONFIG=' . escapeshellarg($dir . '/config.json') . ' ';

        exec($env . 'php versaCLI config:merge ' . escapeshellarg($dir . '/extra.json') . ' 2>&1 < /dev/null', $output, $exitCode);
        $first = file_get_contents($dir . '/config.json');
        exec($env . 'php versaCLI config:merge ' . escapeshellarg($dir . '/extra.json') . ' 2>&1 < /dev/null');
        $config = json_decode((string) file_get_contents($dir . '/config.json'), true);

        expect($exitCode)->toBe(0)
            ->and($config['session']['key'])->toBe('secreta')
            ->and($config['csp']['script-src'])->toBe(['nuevo.example'])
            ->and($config['csp']['form-action'])->toBe(['pago.example'])
            ->and(file_get_contents($dir . '/config.json'))->toBe($first);
    });

    it('falla con código 1 si el archivo no existe', function (): void {
        [$exitCode, $output] = versaCli('config:merge /no/existe.json');

        expect($exitCode)->toBe(1)->and($output)->toContain('No se pudo leer');
    });
});

describe('Request detrás de proxies', function (): void {
    beforeEach(function (): void {
        $this->server = $_SERVER;
        $this->config = $GLOBALS['config'] ?? null;
    });

    afterEach(function (): void {
        $_SERVER = $this->server;
        $GLOBALS['config'] = $this->config;
    });

    $forged = [
        'REQUEST_METHOD' => 'GET',
        'HTTP_HOST' => 'app.example.cl',
        'HTTP_X_FORWARDED_HOST' => 'atacante.example',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_FOR' => '198.51.100.7, 10.0.0.5',
    ];

    it('no cree las cabeceras X-Forwarded-* de un cliente cualquiera', function () use ($forged): void {
        $GLOBALS['config'] = ['trusted_proxies' => [], 'build' => []];
        $_SERVER = ['REMOTE_ADDR' => '203.0.113.9'] + $forged;
        $request = new versaWYS\kernel\Request();

        expect($request->getBaseUrl())->toBe('http://app.example.cl')->and($request->getIp())->toBe('203.0.113.9');
    });

    it('sí las cree si la petición llega desde trusted_proxies', function () use ($forged): void {
        $GLOBALS['config'] = ['trusted_proxies' => ['10.0.0.0/8'], 'build' => []];
        $_SERVER = ['REMOTE_ADDR' => '10.1.2.3'] + $forged;
        $request = new versaWYS\kernel\Request();

        expect($request->getBaseUrl())->toBe('https://atacante.example')->and($request->getIp())->toBe('198.51.100.7');
    });

    it('build.base_url manda sobre la petición', function () use ($forged): void {
        $GLOBALS['config'] = ['trusted_proxies' => ['10.0.0.0/8'], 'build' => ['base_url' => 'https://app.example.cl/']];
        $_SERVER = ['REMOTE_ADDR' => '10.1.2.3'] + $forged;

        expect((new versaWYS\kernel\Request())->getBaseUrl())->toBe('https://app.example.cl');
    });

    it('reconoce IPs sueltas y rangos CIDR IPv4 e IPv6', function (string $ip, bool $trusted): void {
        $GLOBALS['config'] = ['trusted_proxies' => ['192.168.1.0/24', '2001:db8::/32', '127.0.0.1', '172.16.0.0/12']];

        expect(versaWYS\kernel\Request::isTrustedProxy($ip))->toBe($trusted);
    })->with([
        ['192.168.1.200', true],
        ['192.168.2.1', false],
        ['172.31.255.1', true],
        ['172.32.0.1', false],
        ['2001:db8::1', true],
        ['2001:db9::1', false],
        ['127.0.0.1', true],
        ['::ffff:127.0.0.1', false],
        ['no-es-ip', false],
    ]);
});

describe('contraseñas', function (): void {
    it('hash usa el algoritmo por defecto de PHP y needsRehash detecta los hashes antiguos', function (): void {
        $hash = Functions::hash('Clave#Segura2026');

        expect(password_get_info($hash)['algo'])->toBe(PASSWORD_DEFAULT)
            ->and(Functions::needsRehash($hash))->toBeFalse()
            ->and(Functions::needsRehash(password_hash('x', PASSWORD_BCRYPT, ['cost' => 4])))->toBeTrue();
    });
});

describe('entrada de la petición', function (): void {
    beforeEach(function (): void {
        $this->saved = [$_SERVER, $_POST, $GLOBALS['config'] ?? null];
        $_SERVER = ['REQUEST_METHOD' => 'POST', 'REMOTE_ADDR' => '127.0.0.1'];
        $_POST = ['nombre' => "O'Brien & Cía", 'nota' => 'select from tabla'];
    });

    afterEach(function (): void {
        [$_SERVER, $_POST, $GLOBALS['config']] = $this->saved;
    });

    it('getAllParams entrega lo que envió el cliente, aunque config.json traiga la clave de 2.x', function (): void {
        // security.sanitize_input se eliminó en 3.0: un proyecto que actualiza puede tenerla todavía en true
        $GLOBALS['config'] = ['security' => ['sanitize_input' => true]];

        expect((new versaWYS\kernel\Request())->getAllParams())->toBe(['nombre' => "O'Brien & Cía", 'nota' => 'select from tabla']);
    });

    it('config:init crea los proyectos nuevos con nonce de CSP, sin eval y sin la clave security', function (): void {
        $dir = sys_get_temp_dir() . '/versawys_init_' . getmypid();
        @mkdir($dir, 0o700, true);
        exec('VERSAWYS_CONFIG=' . escapeshellarg($dir . '/config.json') . ' php versaCLI config:init 2>&1 < /dev/null');
        $config = json_decode((string) file_get_contents($dir . '/config.json'), true);
        array_map(unlink(...), glob($dir . '/*') ?: []);
        rmdir($dir);

        expect($config)->not->toHaveKey('security')
            ->and($config['csp']['nonce'])->toBeTrue()
            ->and($config['csp']['unsafe_eval'])->toBeFalse();
    });
});

describe('app:kernel:publish', function (): void {
    it('el esqueleto tiene los archivos base del kernel al día', function (): void {
        [$exitCode, $output] = versaCli('app:kernel:publish');

        expect($exitCode)->toBe(0)
            ->and($output)->toContain('index.php')->toContain('.htaccess')
            ->not->toContain('difiere')->not->toContain('(creado)');
    });
});

describe('Functions::validateParams', function (): void {
    it('valida enteros con int e integer, con o sin parámetro', function (mixed $value, string $rule, bool $valid): void {
        expect(Functions::validateParams(['a' => $value], ['a' => $rule]) === [])->toBe($valid);
    })->with([
        ['5', 'int', true],
        [5, 'integer', true],
        ['5', 'required|integer', true],
        ['x', 'integer', false],
        ['1.5', 'int', false],
        ['0', 'int:positive', false],
        ['7', 'int:1-10', true],
        ['11', 'int:1-10', false],
    ]);

    it('string acepta texto y números (inputs numéricos en JSON), no arreglos ni booleanos', function (): void {
        expect(Functions::validateParams(['a' => 'hola'], ['a' => 'string']))->toBe([])
            ->and(Functions::validateParams(['a' => 2024], ['a' => 'string']))->toBe([])
            ->and(Functions::validateParams(['a' => ['x']], ['a' => 'string']))->toHaveKey('a')
            ->and(Functions::validateParams(['a' => true], ['a' => 'string']))->toHaveKey('a');
    });

    it('una regla desconocida es un error, no un dato que pasa sin validar', function (): void {
        expect(Functions::validateParams(['a' => 'lo que sea'], ['a' => 'required|entero']))
            ->toBe(['a' => 'Regla de validación desconocida para a: entero']);
    });
});

describe('app:install', function (): void {
    it('rechaza datos inválidos con código 1 y lista los errores', function (): void {
        [$exitCode, $output] = versaCli('app:install --db-driver=oracle --db-host=x --db-port=5432 --db-name=a --db-user=b --db-pass=c'
            . ' --empresa-nombre=E --empresa-rut=11.111.111-2 --admin-name=A --admin-email=no-es-correo --admin-password=corta');

        expect($exitCode)->toBe(1)
            ->and($output)->toContain('db_driver')->toContain('empresa_rut')->toContain('admin_email')->toContain('admin_password');
    })->skip(fn (): bool => file_exists(app\middleware\InstallMiddleware::lockFile()), 'el proyecto local ya está instalado');
});
