<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli\commands;

use versaWYS\kernel\cli\BaseCommand;
use versaWYS\kernel\cli\CLIErrorHandler;
use versaWYS\kernel\cli\ConfigManager;
use versaWYS\kernel\cli\RouteManager;
use versaWYS\kernel\model\OrmFactory;

/**
 * Revisión previa al despliegue.
 *
 * Tiene dos usos:
 * - En la máquina de desarrollo (por defecto): revisa el CÓDIGO que se va a subir.
 * - En el servidor (`--production`): revisa además la configuración y el entorno reales.
 *
 * Niveles: `critical` impide desplegar; `warning` debe revisarse (con `--strict` también impide);
 * `info` es un recordatorio.
 */
class CheckDeployCommand extends BaseCommand
{
    private const array CODE_DIRS = ['app', 'versaWYS/kernel'];

    /** @var list<array{level: string, phase: string, message: string, file?: string, line?: int, hint?: string}> */
    private array $findings = [];

    private string $phase = '';

    private string $root = '';

    private bool $verbose = false;

    private bool $strict = false;

    private bool $production = false;

    #[\Override]
    public function execute(array $args = []): int
    {
        $flags = array_merge($args, $_SERVER['argv'] ?? []);
        $this->verbose = array_intersect(['--verbose', '-v', '--debug'], $flags) !== [];
        $this->strict = in_array('--strict', $flags, true);
        $this->production = in_array('--production', $flags, true);
        $this->root = (string) getcwd();

        $phases = [
            'Sintaxis PHP' => $this->checkSyntax(...),
            'PSR-4, clases y rutas de archivos' => $this->checkPsr4(...),
            'Rutas de la aplicación' => $this->checkRoutes(...),
            'Plantillas Twig' => $this->checkTemplates(...),
            'Compatibilidad Windows → Linux' => $this->checkWindowsLeftovers(...),
            'Restos de depuración' => $this->checkDebugLeftovers(...),
            'Credenciales en el código' => $this->checkHardcodedSecrets(...),
            'Repositorio' => $this->checkRepository(...),
            'Configuración (config.json)' => $this->checkConfig(...),
            'Entorno del servidor' => $this->checkServer(...),
        ];

        CLIErrorHandler::printSeparator();
        echo CLIErrorHandler::colorize(
            '═══ REVISIÓN PREVIA AL DESPLIEGUE' . ($this->production ? ' · modo servidor' : '') . ($this->strict ? ' · estricto' : '') . " ═══\n",
            'blue',
        );
        CLIErrorHandler::printSeparator();
        echo "\n";

        $number = 0;
        foreach ($phases as $title => $check) {
            $this->phase = $title;
            $before = count($this->findings);
            $check();
            $this->printPhaseResult(++$number, count($phases), $title, array_slice($this->findings, $before));
        }

        $this->printSummary();

        return $this->blockingCount() > 0 ? 1 : 0;
    }

    // =====================================================================
    // Revisiones de código
    // =====================================================================

    /**
     * Un archivo con error de sintaxis es un error fatal en cuanto se carga.
     */
    private function checkSyntax(): void
    {
        foreach ($this->phpFiles([...self::CODE_DIRS, 'index.php', 'versaCLI']) as $file) {
            try {
                token_get_all((string) file_get_contents($file), TOKEN_PARSE);
            } catch (\ParseError $error) {
                $this->add('critical', 'Error de sintaxis: ' . $error->getMessage(), $file, $error->getLine());
            }
        }
    }

    private function checkPsr4(): void
    {
        ob_start();
        $result = (new CheckPsr4Command())->execute([]);
        $output = (string) ob_get_clean();

        if ($result === 0) {
            return;
        }

        $this->add(
            'critical',
            'Hay clases, namespaces o rutas de archivos que fallarán en Linux.',
            hint: 'Detalle: php versaCLI app:check:psr4',
        );

        if ($this->verbose) {
            echo $output;
        }
    }

    /**
     * Rutas que apuntan a controladores, métodos o middlewares que no existen.
     */
    private function checkRoutes(): void
    {
        try {
            $all = RouteManager::extractAllRouteData();
        } catch (\Throwable $exception) {
            $this->add('critical', 'No se pudieron analizar los archivos de rutas: ' . $exception->getMessage());

            return;
        }

        foreach ($all as $routeFile => $routes) {
            foreach ($routes as $route) {
                foreach ($route['issues'] as $issue) {
                    $this->add(
                        $issue['level'] === 'error' ? 'critical' : 'warning',
                        "{$route['method']} {$route['route']} — {$issue['message']}",
                        'app/routes/' . $routeFile,
                    );
                }
            }
        }
    }

    /**
     * Plantillas referenciadas desde PHP (render) o desde otras plantillas (extends, include...)
     * que no existen o cuyo nombre difiere en mayúsculas. En Windows funciona; en Linux no.
     */
    private function checkTemplates(): void
    {
        $templatesDir = $this->root . '/' . trim((string) ($this->projectConfig()['twig']['templates_dir'] ?? './app/templates'), './');
        if (!is_dir($templatesDir)) {
            $this->add('critical', 'No existe el directorio de plantillas: ' . $this->relative($templatesDir));

            return;
        }

        $references = [];
        foreach ($this->phpFiles(self::CODE_DIRS) as $file) {
            $this->collectMatches($file, '/->\s*render(?:Direct)?\(\s*([\'"])([^\'"$]+)\1/', $references);
        }
        foreach ($this->files($templatesDir, 'twig') as $file) {
            $this->collectMatches($file, '/\{%-?\s*(?:extends|include|embed|import|from|use)\s+([\'"])([^\'"]+)\1/', $references);
        }

        foreach ($references as [$file, $line, $name]) {
            $template = str_ends_with($name, '.twig') ? $name : $name . '.twig';
            [$found, $caseDiff] = CheckPsr4Command::locatePath($templatesDir . '/' . $template);

            if ($found === null) {
                $this->add('critical', "La plantilla '{$name}' no existe.", $file, $line);
            } elseif ($caseDiff !== null) {
                $this->add(
                    'critical',
                    "La plantilla '{$name}' difiere en mayúsculas: se escribió '{$caseDiff['used']}' y en disco es '{$caseDiff['expected']}'.",
                    $file,
                    $line,
                );
            }
        }
    }

    private function checkWindowsLeftovers(): void
    {
        $windowsCommands = ['dir' => 'ls', 'copy' => 'cp', 'move' => 'mv', 'del' => 'rm', 'cls' => 'clear', 'tasklist' => 'ps', 'taskkill' => 'kill'];

        foreach ($this->phpFiles(self::CODE_DIRS) as $file) {
            // Los propios comandos de revisión y el servidor de desarrollo tratan con rutas y comandos de Windows a propósito
            if (str_contains($file, '/kernel/cli/')) {
                continue;
            }

            foreach ($this->stringLiterals($file) as [$line, $value]) {
                if (preg_match('/^[A-Za-z]:[\\\\\/][^\s]/', $value) === 1) {
                    $this->add(
                        'warning',
                        'Ruta absoluta de Windows: ' . $this->excerpt($value),
                        $file,
                        $line,
                        'No existe en Linux. Usar rutas relativas al proyecto, o marcar con // deploy-check:ignore si solo se usa en Windows.',
                    );
                }
            }

            foreach ($this->lines($file) as $index => $text) {
                if (preg_match('/\b(?:shell_exec|exec|system|passthru|popen|proc_open)\s*\(\s*[\'"]\s*(\w+)\b/', $text, $match) !== 1) {
                    continue;
                }
                $command = strtolower($match[1]);
                if (isset($windowsCommands[$command])) {
                    $this->add(
                        'warning',
                        "Comando de Windows '{$command}' en una llamada al sistema.",
                        $file,
                        $index + 1,
                        "En Linux es '{$windowsCommands[$command]}'; o decidir según PHP_OS_FAMILY.",
                    );
                }
            }
        }

        // Un script con finales de línea CRLF no se ejecuta en Linux
        foreach (['versaCLI', ...array_map($this->relative(...), $this->glob('scripts/*.sh'))] as $script) {
            $path = $this->root . '/' . $script;
            if (is_file($path) && str_contains((string) file_get_contents($path), "\r\n")) {
                $this->add('critical', 'El archivo usa finales de línea CRLF.', $path, hint: 'Convertir a LF (dos2unix).');
            }
        }

        $gitattributes = $this->root . '/.gitattributes';
        if (!is_file($gitattributes) || !str_contains((string) file_get_contents($gitattributes), 'eol=lf')) {
            $this->add('info', '.gitattributes no fuerza finales de línea LF.', hint: 'Agregar: * text=auto eol=lf');
        }
    }

    /**
     * Llamadas de depuración olvidadas, detectadas por tokens para no confundirlas con texto o comentarios.
     */
    private function checkDebugLeftovers(): void
    {
        $functions = ['var_dump', 'dd', 'dump', 'print_r', 'debug_print_backtrace', 'phpinfo'];

        foreach ($this->phpFiles(self::CODE_DIRS) as $file) {
            // La consola y las páginas de error del framework imprimen a propósito
            if (preg_match('#/kernel/(cli/|ErrorHandler|versaTwig|Response)#', $file) === 1) {
                continue;
            }

            $tokens = token_get_all((string) file_get_contents($file));
            foreach ($tokens as $i => $token) {
                if (!is_array($token) || $token[0] !== T_STRING || !in_array(strtolower($token[1]), $functions, true)) {
                    continue;
                }

                $prev = $this->significant($tokens, $i, -1);
                $next = $this->significant($tokens, $i, 1);
                $isCall = $next === '('
                    && !(is_array($prev) && in_array($prev[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW], true));
                if (!$isCall) {
                    continue;
                }

                // print_r($x, true) devuelve el texto en vez de imprimirlo: es un uso legítimo
                if (strtolower($token[1]) === 'print_r' && preg_match('/print_r\s*\(.*,\s*true\s*\)/i', $this->lineOf($file, $token[2])) === 1) {
                    continue;
                }

                $this->add('warning', "Llamada de depuración {$token[1]}().", $file, $token[2]);
            }
        }
    }

    /**
     * Secretos escritos en el código. Los que tienen formato inconfundible (claves privadas, tokens
     * de proveedores) son críticos; las asignaciones sospechosas, un aviso.
     */
    private function checkHardcodedSecrets(): void
    {
        $knownFormats = [
            '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/' => 'clave privada',
            '/\bgh[pousr]_[A-Za-z0-9]{30,}\b/' => 'token de GitHub',
            '/\bAKIA[0-9A-Z]{16}\b/' => 'clave de acceso de AWS',
            '/\bsk_live_[A-Za-z0-9]{16,}\b/' => 'clave secreta de Stripe',
            '/\bAIza[0-9A-Za-z_\-]{35}\b/' => 'clave de API de Google',
            '/\bxox[baprs]-[A-Za-z0-9-]{10,}\b/' => 'token de Slack',
        ];
        $sensitiveName = '/(?:pass(?:word|wd)?|secret|token|api_?key|private_?key|clave)$/i';

        foreach ($this->phpFiles(self::CODE_DIRS) as $file) {
            if (str_contains($file, '/kernel/cli/commands/CheckDeployCommand.php')) {
                continue;
            }

            $tokens = token_get_all((string) file_get_contents($file));
            foreach ($tokens as $i => $token) {
                if (!is_array($token)) {
                    continue;
                }

                if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
                    foreach ($knownFormats as $pattern => $kind) {
                        if (preg_match($pattern, $token[1]) === 1) {
                            $this->add('critical', "Secreto con formato conocido ({$kind}) escrito en el código.", $file, $token[2], 'Moverla a config.json o a una variable de entorno, y rotarla.');
                        }
                    }
                }

                // $password = 'literal';   o   'api_key' => 'literal'
                $isVariable = $token[0] === T_VARIABLE && preg_match($sensitiveName, $token[1]) === 1;
                $isArrayKey = $token[0] === T_CONSTANT_ENCAPSED_STRING && preg_match($sensitiveName, trim($token[1], '\'"')) === 1;
                if (!$isVariable && !$isArrayKey) {
                    continue;
                }

                $operatorIndex = null;
                $valueIndex = null;
                $operator = $this->significant($tokens, $i, 1, $operatorIndex);
                $assigns = $isVariable ? $operator === '=' : (is_array($operator) && $operator[0] === T_DOUBLE_ARROW);
                $value = $assigns && $operatorIndex !== null ? $this->significant($tokens, $operatorIndex, 1, $valueIndex) : null;
                if (!is_array($value) || $value[0] !== T_CONSTANT_ENCAPSED_STRING || $valueIndex === null) {
                    continue;
                }

                // Solo si el literal es todo el valor (no parte de una concatenación o llamada)
                $after = $this->significant($tokens, $valueIndex, 1);
                $literal = trim($value[1], '\'"');
                if (in_array($after, [';', ',', ']', ')'], true) && $this->looksLikeSecret($literal)) {
                    $this->add(
                        'warning',
                        'Posible credencial escrita en el código: ' . trim($token[1], '\'"') . ' = ' . substr($literal, 0, 3) . '…',
                        $file,
                        $token[2],
                        'Leerla desde config.json o una variable de entorno.',
                    );
                }
            }
        }
    }

    /**
     * Un secreto real no tiene espacios, es largo y mezcla tipos de caracteres. Así se descartan
     * nombres de campos, mensajes y reglas de validación ('required|min:8').
     */
    private function looksLikeSecret(string $value): bool
    {
        if (strlen($value) < 8 || preg_match('/\s|[|:{}<>]|^\$/', $value) === 1) {
            return false;
        }

        $classes = (int) preg_match('/[a-z]/', $value) + (int) preg_match('/[A-Z]/', $value) + (int) preg_match('/\d/', $value);

        return $classes >= 2 || strlen($value) >= 20;
    }

    // =====================================================================
    // Repositorio, configuración y servidor
    // =====================================================================

    /**
     * Archivos con secretos que nunca deben estar versionados.
     */
    private function checkRepository(): void
    {
        $gitignore = is_file($this->root . '/.gitignore') ? (string) file_get_contents($this->root . '/.gitignore') : '';
        foreach (['.env' => '.env', 'config.json' => ConfigManager::dir() . '/config.json'] as $needle => $path) {
            if (!str_contains($gitignore, $needle)) {
                $this->add('critical', "{$path} no está en .gitignore.", hint: 'Contiene credenciales: agregarlo a .gitignore.');
            }
        }

        if (!is_dir($this->root . '/.git')) {
            return;
        }

        $secretFiles = [ConfigManager::dir() . '/config.json', ConfigManager::dir() . '/install.token', '.env'];
        exec('git -C ' . escapeshellarg($this->root) . ' ls-files -- ' . implode(' ', array_map(escapeshellarg(...), $secretFiles)) . ' 2>/dev/null', $tracked);
        foreach ($tracked as $file) {
            $this->add(
                'critical',
                "{$file} está versionado en git.",
                hint: "git rm --cached {$file}, y cambiar las credenciales que contenga: siguen en el historial.",
            );
        }
    }

    /**
     * Valores de config.json que son inseguros o incorrectos en producción. En la máquina de
     * desarrollo son solo avisos, porque el config.json local no es el del servidor.
     */
    private function checkConfig(): void
    {
        $configFile = $this->root . '/' . ConfigManager::dir() . '/config.json';
        if (!is_file($configFile)) {
            $this->add($this->production ? 'critical' : 'info', 'No existe config.json.', hint: 'Crearlo con: php versaCLI config:init');

            return;
        }

        $config = $this->projectConfig();
        $serious = $this->production ? 'critical' : 'warning';
        $minor = $this->production ? 'warning' : 'info';
        $file = ConfigManager::dir() . '/config.json';

        $rules = [
            [$serious, ($config['build']['debug'] ?? false) === true, 'build.debug está activo: expone rutas, trazas y SQL en los errores.', 'php versaCLI config:debug:false'],
            [$serious, strlen((string) ($config['session']['key'] ?? '')) < 32, 'session.key está vacía o es corta: con ella se firman los JWT.', 'Generar una clave aleatoria de 64 caracteres.'],
            [$serious, ($config['session']['user_cookie']['secure'] ?? false) !== true, 'session.user_cookie.secure está desactivado: la sesión viajaría por HTTP.', 'Activarlo; el sitio debe servirse por HTTPS.'],
            [$serious, ($config['session']['user_cookie']['http_only'] ?? false) !== true, 'session.user_cookie.http_only está desactivado: JavaScript podría leer la sesión.', null],
            [$serious, (string) ($config['database']['database'] ?? '') === '', 'database.database está vacío: no hay base de datos configurada.', null],
            [$serious, in_array('*', (array) ($config['cors']['allowed_origins'] ?? []), true), 'cors.allowed_origins contiene "*".', 'Listar solo los orígenes que deben llamar a la API.'],
            [$minor, (string) ($config['database']['password'] ?? '') === '', 'database.password está vacío.', null],
            [$minor, ($config['twig']['cache'] ?? false) !== true, 'twig.cache está desactivado: las plantillas se compilan en cada petición.', 'php versaCLI config:templateCache:true'],
            [$minor, ($config['rate_limiting']['enabled'] ?? false) !== true, 'rate_limiting está desactivado: el login queda sin límite de intentos por IP.', null],
            [$minor, ($config['csp']['enabled'] ?? true) !== true, 'csp.enabled está desactivado.', null],
            ['info', (string) ($config['mail']['host'] ?? '') === '', 'mail.host está vacío: no se enviarán correos (recuperación de contraseña).', null],
        ];

        foreach ($rules as [$level, $failed, $message, $hint]) {
            if ($failed) {
                $this->add($level, $message, $file, hint: $hint);
            }
        }
    }

    /**
     * Estado real del servidor. Solo con --production: en la máquina de desarrollo no dice nada útil.
     */
    private function checkServer(): void
    {
        if (!$this->production) {
            $this->add('info', 'Las revisiones del servidor (permisos, extensiones, base de datos, frontend compilado) se ejecutan con --production.', hint: 'En el servidor: php versaCLI app:check:deploy --production');

            return;
        }

        $config = $this->projectConfig();

        if (version_compare(PHP_VERSION, '8.3.0', '<')) {
            $this->add('critical', 'PHP ' . PHP_VERSION . ': se requiere 8.3 o superior.');
        }

        $driver = str_contains(strtolower((string) ($config['database']['driver'] ?? '')), 'mysql') ? 'pdo_mysql' : 'pdo_pgsql';
        foreach (['intl', 'pdo', $driver, 'mbstring', 'fileinfo', 'curl', 'zip', 'json'] as $extension) {
            if (!extension_loaded($extension)) {
                $this->add('critical', "Falta la extensión de PHP '{$extension}'.");
            }
        }
        if (!extension_loaded('Zend OPcache') || !(bool) ini_get('opcache.enable')) {
            $this->add('info', 'OPcache no está activo: PHP recompila cada archivo en cada petición.');
        }

        // Directorios donde la aplicación escribe
        $writable = [
            $config['logging']['dir'] ?? './versaWYS/logs/',
            dirname((string) ($config['build']['cacheRouteFile'] ?? './versaWYS/cache/routes.compiled.php')),
            $config['twig']['compiled_dir'] ?? './versaWYS/cache/twig/.cache',
            $config['rate_limiting']['cache_dir'] ?? './versaWYS/cache/rate_limits/',
            $config['assets']['dashboard']['avatars']['dist'] ?? 'public/dashboard/img/avatars',
            $config['assets']['dashboard']['store']['dist'] ?? 'public/dashboard/store',
            'versaWYS/kernel/config',
        ];
        foreach (array_unique($writable) as $directory) {
            $path = $this->root . '/' . trim((string) $directory, './');
            // Un directorio que aún no existe se crea solo si su padre es escribible
            $target = is_dir($path) ? $path : dirname($path);
            if (!is_dir($target) || !is_writable($target)) {
                $this->add('critical', 'Sin permiso de escritura: ' . $this->relative($path), hint: 'El usuario del servidor web debe poder escribir ahí.');
            }
        }

        // Dependencias y frontend
        $required = [
            'versaWYS/vendor/autoload.php' => 'Faltan las dependencias de PHP (composer install --no-dev).',
            'public/dashboard/js/vue-loader.js' => 'El frontend no está compilado (pnpm compile).',
            'public/dashboard/css/dashboard.css' => 'Los estilos no están compilados (pnpm compile).',
            'node_modules/vue/dist/vue.esm-browser.prod.js' => 'Falta Vue en node_modules: las páginas del panel saldrían en blanco (pnpm install --production).',
            'node_modules/sweetalert2/dist/sweetalert2.esm.all.js' => 'Falta SweetAlert2 en node_modules (pnpm install --production).',
        ];
        foreach ($required as $path => $message) {
            if (!is_file($this->root . '/' . $path)) {
                $this->add('critical', $message, $path);
            }
        }
        if (is_dir($this->root . '/versaWYS/vendor/pestphp') || is_dir($this->root . '/versaWYS/vendor/phpstan')) {
            $this->add('warning', 'Están instaladas las dependencias de desarrollo de PHP.', hint: 'composer install --no-dev --optimize-autoloader');
        }

        // Instalación y base de datos
        if (is_file($this->root . '/' . ConfigManager::dir() . '/install.token')) {
            $this->add('warning', 'Existe install.token: el instalador web (/install) sigue disponible para quien tenga el token.', hint: 'Completar la instalación o borrar el archivo.');
        }
        if (($config['build']['cacheRoute'] ?? false) === true && !is_file($this->root . '/' . trim((string) $config['build']['cacheRouteFile'], './'))) {
            $this->add('critical', 'build.cacheRoute está activo pero no existe el archivo de rutas compiladas.', hint: 'php versaCLI route:compile');
        }

        $this->checkDatabase($config);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function checkDatabase(array $config): void
    {
        if ((string) ($config['database']['database'] ?? '') === '') {
            return; // ya reportado en la revisión de configuración
        }

        try {
            $orm = OrmFactory::make($config);
            $executed = in_array('versa_migrations', $orm->schema('tables'), true)
                ? array_column($orm->exec('SELECT name FROM versa_migrations'), 'name')
                : [];
        } catch (\Throwable $exception) {
            $this->add('critical', 'No se pudo conectar a la base de datos: ' . strtok($exception->getMessage(), "\n"));

            return;
        }

        $files = array_map(static fn (string $file): string => basename($file, '.php'), $this->glob('app/migrations/*.php'));
        $pending = array_diff($files, $executed);
        if ($pending !== []) {
            $this->add('critical', count($pending) . ' migración(es) sin ejecutar: ' . implode(', ', array_slice($pending, 0, 3)) . (count($pending) > 3 ? '…' : ''), hint: 'php versaCLI migrate:up');
        }
    }

    // =====================================================================
    // Utilidades
    // =====================================================================

    private function add(string $level, string $message, ?string $file = null, ?int $line = null, ?string $hint = null): void
    {
        if ($file !== null && $line !== null && $this->isIgnored($file, $line)) {
            return;
        }

        $finding = ['level' => $level, 'phase' => $this->phase, 'message' => $message];
        if ($file !== null) {
            $finding['file'] = $this->relative($file);
        }
        if ($line !== null) {
            $finding['line'] = $line;
        }
        if ($hint !== null) {
            $finding['hint'] = $hint;
        }
        $this->findings[] = $finding;
    }

    /**
     * El código puede declarar que un hallazgo es intencional con un comentario:
     *   // deploy-check:ignore            en la misma línea o en la anterior
     *   // deploy-check:ignore-start ... // deploy-check:ignore-end   para un bloque
     */
    private function isIgnored(string $file, int $line): bool
    {
        static $ignoredLines = [];

        if (!isset($ignoredLines[$file])) {
            $ignoredLines[$file] = [];
            $inBlock = false;
            foreach ($this->lines($file) as $index => $text) {
                if (str_contains($text, 'deploy-check:ignore-start')) {
                    $inBlock = true;
                } elseif (str_contains($text, 'deploy-check:ignore-end')) {
                    $inBlock = false;
                } elseif (str_contains($text, 'deploy-check:ignore')) {
                    $ignoredLines[$file][$index + 1] = true;
                    $ignoredLines[$file][$index + 2] = true;
                }
                if ($inBlock) {
                    $ignoredLines[$file][$index + 1] = true;
                }
            }
        }

        return isset($ignoredLines[$file][$line]);
    }

    /** Hallazgos que impiden desplegar: los críticos y, en modo estricto, también los avisos. */
    private function blockingCount(): int
    {
        $blocking = $this->strict ? ['critical', 'warning'] : ['critical'];

        return count(array_filter($this->findings, static fn (array $finding): bool => in_array($finding['level'], $blocking, true)));
    }

    /**
     * @return array<string, mixed>
     */
    private function projectConfig(): array
    {
        return array_replace_recursive(ConfigManager::defaults(), ConfigManager::getConfig());
    }

    /**
     * @param list<string> $paths directorios o archivos, relativos a la raíz
     *
     * @return list<string>
     */
    private function phpFiles(array $paths): array
    {
        $files = [];
        foreach ($paths as $path) {
            $absolute = $this->root . '/' . $path;
            if (is_file($absolute)) {
                $files[] = $absolute;
            } elseif (is_dir($absolute)) {
                $files = [...$files, ...$this->files($absolute, 'php')];
            }
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function files(string $directory, string $extension): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === $extension) {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    /**
     * Agrega a $references las coincidencias del patrón como [archivo, línea, valor del segundo grupo].
     *
     * @param list<array{0: string, 1: int, 2: string}> $references
     */
    private function collectMatches(string $file, string $pattern, array &$references): void
    {
        foreach ($this->lines($file) as $index => $text) {
            if (preg_match_all($pattern, $text, $matches) > 0) {
                foreach ($matches[2] as $name) {
                    $references[] = [$file, $index + 1, $name];
                }
            }
        }
    }

    /**
     * Literales de texto de un archivo PHP, sin comentarios ni HTML.
     *
     * @return list<array{0: int, 1: string}>
     */
    private function stringLiterals(string $file): array
    {
        $literals = [];
        foreach (token_get_all((string) file_get_contents($file)) as $token) {
            if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                $literals[] = [$token[2], stripslashes(substr($token[1], 1, -1))];
            }
        }

        return $literals;
    }

    /**
     * Siguiente (o anterior) token que no es espacio ni comentario.
     *
     * @param array<int, mixed> $tokens
     */
    private function significant(array $tokens, int $index, int $direction, ?int &$foundIndex = null): mixed
    {
        for ($i = $index + $direction; isset($tokens[$i]); $i += $direction) {
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }
            $foundIndex = $i;

            return $tokens[$i];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function lines(string $file): array
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES);

        return $lines === false ? [] : $lines;
    }

    /**
     * @return list<string> rutas absolutas que coinciden con el patrón, relativo a la raíz
     */
    private function glob(string $pattern): array
    {
        $matches = glob($this->root . '/' . $pattern);

        return $matches === false ? [] : $matches;
    }

    private function lineOf(string $file, int $line): string
    {
        return $this->lines($file)[$line - 1] ?? '';
    }

    private function relative(string $path): string
    {
        return ltrim(str_replace($this->root, '', $path), '/');
    }

    private function excerpt(string $text): string
    {
        return strlen($text) > 60 ? substr($text, 0, 57) . '…' : $text;
    }

    // =====================================================================
    // Salida
    // =====================================================================

    /**
     * @param list<array<string, mixed>> $findings
     */
    private function printPhaseResult(int $number, int $total, string $title, array $findings): void
    {
        $count = array_count_values(array_column($findings, 'level'));
        $critical = $count['critical'] ?? 0;
        $warning = $count['warning'] ?? 0;

        [$icon, $color] = match (true) {
            $critical > 0 => ['✗', 'red'],
            $warning > 0 => ['⚠', 'yellow'],
            default => ['✓', 'green'],
        };

        $detail = [];
        if ($critical > 0) {
            $detail[] = $critical . ($critical === 1 ? ' crítico' : ' críticos');
        }
        if ($warning > 0) {
            $detail[] = $warning . ($warning === 1 ? ' aviso' : ' avisos');
        }

        echo CLIErrorHandler::colorize(sprintf('  %s %2d/%d  %s', $icon, $number, $total, $title), $color)
            . ($detail === [] ? '' : '  (' . implode(', ', $detail) . ')') . "\n";
    }

    private function printSummary(): void
    {
        $levels = [
            'critical' => ['🔴 CRÍTICOS — impiden desplegar', 'red'],
            'warning' => ['⚠️  AVISOS' . ($this->strict ? ' — en modo estricto impiden desplegar' : ' — revisar antes de desplegar'), 'yellow'],
            'info' => ['ℹ️  INFORMACIÓN', 'blue'],
        ];

        foreach ($levels as $level => [$title, $color]) {
            $items = array_values(array_filter($this->findings, static fn (array $finding): bool => $finding['level'] === $level));
            if ($items === []) {
                continue;
            }

            echo "\n" . CLIErrorHandler::colorize($title . ' (' . count($items) . ')', $color) . "\n";

            // Sin --verbose, cada fase muestra sus primeros hallazgos
            $shownByPhase = [];
            $hidden = 0;
            foreach ($items as $item) {
                $shownByPhase[$item['phase']] = ($shownByPhase[$item['phase']] ?? 0) + 1;
                if (!$this->verbose && $level !== 'critical' && $shownByPhase[$item['phase']] > 5) {
                    $hidden++;

                    continue;
                }

                $location = isset($item['file']) ? '  ' . $item['file'] . (isset($item['line']) ? ':' . $item['line'] : '') : '';
                echo "  [{$item['phase']}] {$item['message']}\n";
                if ($location !== '') {
                    echo '      ' . CLIErrorHandler::colorize(trim($location), 'cyan') . "\n";
                }
                if (isset($item['hint'])) {
                    echo "      💡 {$item['hint']}\n";
                }
            }

            if ($hidden > 0) {
                echo CLIErrorHandler::colorize("  … y {$hidden} más (usa --verbose para verlos todos)\n", 'blue');
            }
        }

        CLIErrorHandler::printSeparator();
        $blocking = $this->blockingCount();
        if ($blocking === 0) {
            echo CLIErrorHandler::colorize("✅ LISTO PARA DESPLEGAR\n", 'green');
        } else {
            echo CLIErrorHandler::colorize("❌ NO DESPLEGAR: {$blocking} problema(s) por resolver\n", 'red');
        }
        if (!$this->production) {
            echo "   Esta ejecución revisó el código. En el servidor, repetir con --production.\n";
        }
        CLIErrorHandler::printSeparator();
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Revisión previa al despliegue: código, rutas, plantillas, secretos, configuración y servidor';
    }

    #[\Override]
    public function getHelp(): string
    {
        return <<<'HELP'
            Uso: php versaCLI app:check:deploy [opciones]

            Revisa que el proyecto esté en condiciones de desplegarse. Termina con código 1
            si encuentra algo que impida el despliegue, así que puede usarse en CI.

            OPCIONES:
              --strict        Los avisos también impiden desplegar
              --production    Revisa además la configuración y el entorno reales. Para ejecutar
                              EN EL SERVIDOR (por ejemplo como último paso del despliegue)
              --verbose       Muestra todos los hallazgos y el detalle de PSR-4

            EXCEPCIONES:
              Un hallazgo intencional se silencia con un comentario en el código:
                // deploy-check:ignore                      (misma línea o la anterior)
                // deploy-check:ignore-start ... -end       (un bloque)

            REVISIONES:
               1. Sintaxis PHP                 Archivos que no se pueden cargar
               2. PSR-4, clases y archivos     Namespaces, clases inexistentes, require con mayúsculas distintas
               3. Rutas de la aplicación       Controladores, métodos o middlewares que no existen
               4. Plantillas Twig              render / extends / include a plantillas inexistentes o con otra capitalización
               5. Windows → Linux              Rutas C:\, comandos de Windows, finales de línea CRLF
               6. Restos de depuración         var_dump, dd, dump, print_r, phpinfo
               7. Credenciales en el código    Claves privadas, tokens de proveedores, contraseñas asignadas
               8. Repositorio                  config.json, .env o install.token versionados
               9. Configuración                debug, claves de sesión, cookies seguras, CORS, caché
              10. Entorno del servidor         (--production) PHP, extensiones, permisos, dependencias,
                                               frontend compilado, conexión y migraciones pendientes

            NIVELES:
              🔴 Crítico      Impide desplegar
              ⚠️  Aviso        Revisar; con --strict también impide desplegar
              ℹ️  Información  Recordatorio

            La configuración local no es la del servidor: sin --production sus problemas son
            avisos; con --production, los graves son críticos.

            EJEMPLOS:
              php versaCLI app:check:deploy                 Antes de subir cambios
              php versaCLI app:check:deploy --strict        En CI
              php versaCLI app:check:deploy --production    En el servidor, tras desplegar
            HELP;
    }
}
