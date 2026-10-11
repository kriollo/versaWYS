<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli\commands;

use versaWYS\kernel\cli\BaseCommand;
use versaWYS\kernel\cli\CLIErrorHandler;

class CheckDeployCommand extends BaseCommand
{
    private array $issues = [];
    private array $warnings = [];
    private array $info = [];
    private array $scannedFiles = [];
    private bool $verbose = false;
    private string $projectRoot = '';

    #[\Override]
    public function execute(array $args = []): int
    {
        $argv = $_SERVER['argv'] ?? [];
        $this->verbose =
            in_array('--verbose', $argv, true) || in_array('-v', $argv, true) || in_array('--debug', $argv, true);

        if ($this->verbose) {
            echo CLIErrorHandler::colorize("🔍 Modo verbose activado - Mostrando todos los detalles\n\n", 'blue');
        }

        // Calcular raíz del proyecto (4 niveles arriba desde versaWYS/kernel/cli/commands)
        $this->projectRoot = realpath(__DIR__ . '/../../../../') ?: dirname(__DIR__, 4);

        $this->printHeader();

        $psr4Result = $this->runPsr4Check();

        $this->scanWindowsPaths();

        $this->scanWindowsCommands();

        $this->scanLineEndings();

        $this->validateEnvironment();

        $this->validatePermissions();

        $this->scanHardcodedCredentials();

        $this->printSummary();

        return empty($this->issues) ? 0 : 1;
    }

    private function printHeader(): void
    {
        CLIErrorHandler::printSeparator();
        echo CLIErrorHandler::colorize("═══ VALIDACIÓN DE DEPLOYMENT (WINDOWS → LINUX) ═══\n", 'blue');
        CLIErrorHandler::printSeparator();
        echo "\n";
    }

    private function runPsr4Check(): int
    {
        echo CLIErrorHandler::colorize("▶ FASE 1/7: Validación PSR-4 y Case-Sensitivity\n", 'cyan');
        echo "  Ejecutando app:check:psr4...\n\n";

        $psr4Command = new CheckPsr4Command();
        $result = $psr4Command->execute([]);

        if (0 === $result) {
            CLIErrorHandler::success("  ✓ PSR-4: Sin problemas detectados\n");
        } else {
            $this->issues[] = [
                'phase' => 'PSR-4',
                'type' => 'critical',
                'message' => 'Se encontraron problemas de PSR-4 o case-sensitivity',
                'suggestion' => 'Ejecuta: php versaCLI app:check:psr4 para ver detalles',
            ];
        }

        echo "\n";

        return $result;
    }

    private function scanWindowsPaths(): void
    {
        echo CLIErrorHandler::colorize("▶ FASE 2/7: Buscando rutas de Windows\n", 'cyan');

        $directories = [
            $this->projectRoot . DIRECTORY_SEPARATOR . 'app',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'versaWYS' . DIRECTORY_SEPARATOR . 'kernel',
        ];

        $patterns = [
            '/["\"][A-Za-z]:[\\\\\/] [^"\"]*["\"]/i' => 'Ruta absoluta de Windows detectada',
        ];

        $foundIssues = false;

        foreach ($directories as $directory) {
            $this->scanDirectoryForPatterns($directory, $patterns, 'windows_path', function (
                $file,
                $line,
                $lineNumber,
                $pattern,
                $message,
            ) use (&$foundIssues) {
                if ('CheckDeployCommand.php' === basename($file)) {
                    return;
                }

                $foundIssues = true;
                $this->warnings[] = [
                    'phase' => 'Windows Paths',
                    'file' => $this->getRelativePath($file),
                    'line' => $lineNumber,
                    'content' => trim($line),
                    'message' => $message,
                    'suggestion' => 'Usar DIRECTORY_SEPARATOR o forward slashes que funcionan en ambos sistemas',
                ];
            });
        }

        if (!$foundIssues) {
            CLIErrorHandler::success("  ✓ Windows Paths: Sin problemas detectados\n");
        } else {
            echo
                CLIErrorHandler::colorize(
                    '  ⚠ Windows Paths: '
                    . count(array_filter($this->warnings, static fn($w) => 'Windows Paths' === $w['phase']))
                    . " advertencias encontradas\n",
                    'yellow',
                )
            ;
        }

        echo "\n";
    }

    private function scanWindowsCommands(): void
    {
        echo CLIErrorHandler::colorize("▶ FASE 3/7: Buscando comandos de Windows\n", 'cyan');

        $directories = [
            $this->projectRoot . DIRECTORY_SEPARATOR . 'app',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'versaWYS' . DIRECTORY_SEPARATOR . 'kernel',
        ];

        $windowsCommands = [
            'dir' => 'ls',
            'copy' => 'cp',
            'move' => 'mv',
            'del' => 'rm',
            'type' => 'cat',
            'cls' => 'clear',
            'tasklist' => 'ps',
            'taskkill' => 'kill',
        ];

        $foundIssues = false;

        foreach ($directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
                $directory,
                \RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || 'php' !== $file->getExtension()) {
                    continue;
                }

                $filePath = $file->getPathname();
                $content = file_get_contents($filePath);
                $lines = explode("\n", $content);

                foreach ($lines as $lineNumber => $line) {
                    if (!preg_match('/(shell_exec|exec|system|passthru)\s*\(/', $line)) {
                        continue;
                    }

                    foreach ($windowsCommands as $winCmd => $linuxCmd) {
                        if (!preg_match('/\b' . preg_quote($winCmd, '/') . '\b/i', $line)) {
                            continue;
                        }

                        $foundIssues = true;
                        $this->warnings[] = [
                            'phase' => 'Windows Commands',
                            'file' => $this->getRelativePath($filePath),
                            'line' => $lineNumber + 1,
                            'content' => trim($line),
                            'message' => "Comando de Windows detectado: '{$winCmd}'",
                            'suggestion' => "Considerar usar '{$linuxCmd}' o detectar SO con PHP_OS_FAMILY",
                        ];
                    }
                }
            }
        }

        if (!$foundIssues) {
            CLIErrorHandler::success("  ✓ Windows Commands: Sin problemas detectados\n");
        } else {
            echo
                CLIErrorHandler::colorize(
                    '  ⚠ Windows Commands: '
                    . count(array_filter($this->warnings, static fn($w) => 'Windows Commands' === $w['phase']))
                    . " advertencias encontradas\n",
                    'yellow',
                )
            ;
        }

        echo "\n";
    }

    private function scanLineEndings(): void
    {
        echo CLIErrorHandler::colorize("▶ FASE 4/7: Validando line endings\n", 'cyan');

        $criticalFiles = [
            $this->projectRoot . DIRECTORY_SEPARATOR . 'versaCLI',
            $this->projectRoot . DIRECTORY_SEPARATOR . '.gitattributes',
        ];

        $foundIssues = false;

        foreach ($criticalFiles as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $content = file_get_contents($file);

            if (str_contains($content, "\r\n")) {
                $foundIssues = true;
                $this->warnings[] = [
                    'phase' => 'Line Endings',
                    'file' => $this->getRelativePath($file),
                    'message' => 'Archivo usa CRLF (Windows) en lugar de LF (Unix)',
                    'suggestion' =>
                        'Convertir a LF: dos2unix ' . basename($file) . ' o configurar Git con core.autocrlf=input',
                ];
            }
        }

        $gitattributes = $this->projectRoot . DIRECTORY_SEPARATOR . '.gitattributes';
        if (!file_exists($gitattributes)) {
            $this->info[] = [
                'phase' => 'Line Endings',
                'message' => 'No existe .gitattributes',
                'suggestion' => 'Crear .gitattributes con: * text=auto eol=lf',
            ];
        } else {
            $content = file_get_contents($gitattributes);
            if (!str_contains($content, 'eol=lf')) {
                $this->info[] = [
                    'phase' => 'Line Endings',
                    'file' => '.gitattributes',
                    'message' => 'No se especifica eol=lf en .gitattributes',
                    'suggestion' => 'Agregar: * text=auto eol=lf',
                ];
            }
        }

        if (!$foundIssues) {
            CLIErrorHandler::success("  ✓ Line Endings: Sin problemas críticos detectados\n");
        } else {
            echo
                CLIErrorHandler::colorize(
                    '  ⚠ Line Endings: '
                    . count(array_filter($this->warnings, static fn($w) => 'Line Endings' === $w['phase']))
                    . " advertencias encontradas\n",
                    'yellow',
                )
            ;
        }

        echo "\n";
    }

    private function validateEnvironment(): void
    {
        echo CLIErrorHandler::colorize("▶ FASE 5/7: Validando configuración de entorno\n", 'cyan');

        $gitignore = $this->projectRoot . DIRECTORY_SEPARATOR . '.gitignore';
        if (file_exists($gitignore)) {
            $content = file_get_contents($gitignore);
            if (!str_contains($content, '.env')) {
                $this->issues[] = [
                    'phase' => 'Environment',
                    'type' => 'critical',
                    'message' => '.env no está en .gitignore',
                    'suggestion' => 'Agregar .env a .gitignore para evitar commit de credenciales',
                ];
            }
        } else {
            $this->warnings[] = [
                'phase' => 'Environment',
                'message' => 'No existe archivo .gitignore',
                'suggestion' => 'Crear .gitignore con .env, /vendor/, /node_modules/, etc.',
            ];
        }

        $configPath =
            $this->projectRoot
            . DIRECTORY_SEPARATOR
            . 'versaWYS'
            . DIRECTORY_SEPARATOR
            . 'kernel'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'config.json';
        if (file_exists($configPath)) {
            $configContent = file_get_contents($configPath);
            $config = json_decode($configContent, true);

            if (isset($config['build']['debug']) && true === $config['build']['debug']) {
                $this->warnings[] = [
                    'phase' => 'Environment',
                    'file' => 'versaWYS/kernel/config/config.json',
                    'message' => 'Debug mode está habilitado',
                    'suggestion' => 'Desactivar debug en producción: build.debug = false',
                ];
            }

            if (isset($config['app']['env']) && 'production' !== $config['app']['env']) {
                $this->info[] = [
                    'phase' => 'Environment',
                    'file' => 'versaWYS/kernel/config/config.json',
                    'message' => "Entorno configurado como: {$config['app']['env']}",
                    'suggestion' => 'Verificar que en producción sea: app.env = production',
                ];
            }

            $this->checkConfigCredentials($config);
        }

        $writableDirs = $this->extractPathsFromConfig();

        foreach ($writableDirs as $dir => $reason) {
            $fullPath = $this->projectRoot . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($fullPath)) {
                $this->info[] = [
                    'phase' => 'Environment',
                    'message' => "Directorio {$dir} no existe",
                    'suggestion' => "Crear directorio y asegurar permisos 775 en Linux (Razón: {$reason})",
                ];
            }
        }

        $issueCount = count(array_filter($this->issues, static fn($i) => 'Environment' === $i['phase']));
        if (0 === $issueCount) {
            CLIErrorHandler::success("  ✓ Environment: Sin problemas críticos detectados\n");
        } else {
            echo CLIErrorHandler::colorize("  ✗ Environment: {$issueCount} problemas críticos encontrados\n", 'red');
        }

        echo "\n";
    }

    private function validatePermissions(): void
    {
        echo CLIErrorHandler::colorize("▶ FASE 6/7: Validando estructura de permisos\n", 'cyan');

        $writableDirs = $this->extractPathsFromConfig();

        echo '  ' . CLIErrorHandler::colorize("Directorios que necesitan permisos de escritura en Linux:\n", 'blue');

        foreach ($writableDirs as $dir => $reason) {
            $fullPath = $this->projectRoot . DIRECTORY_SEPARATOR . $dir;
            $exists = is_dir($fullPath);
            $existsText = $exists ? CLIErrorHandler::colorize('✓', 'green') : CLIErrorHandler::colorize('✗', 'red');

            echo "    {$existsText} {$dir}\n";
            echo "       Razón: {$reason}\n";
            echo '       Comando: ' . CLIErrorHandler::colorize("chmod 775 {$dir}", 'yellow') . "\n";

            if (!$exists) {
                $this->info[] = [
                    'phase' => 'Permissions',
                    'message' => "Directorio {$dir} debe crearse en producción",
                    'suggestion' => "mkdir -p {$dir} && chmod 775 {$dir}",
                ];
            }
        }

        $versaCLI = $this->projectRoot . DIRECTORY_SEPARATOR . 'versaCLI';
        if (file_exists($versaCLI)) {
            echo "\n  " . CLIErrorHandler::colorize("Archivo ejecutable:\n", 'blue');
            echo "    ✓ versaCLI\n";
            echo '       Comando: ' . CLIErrorHandler::colorize('chmod +x versaCLI', 'yellow') . "\n";
        }

        CLIErrorHandler::success("\n  ✓ Permissions: Estructura validada\n");
        echo "\n";
    }

    private function checkConfigCredentials(array $config): void
    {
        $sensitiveKeys = [
            'database.password' => 'Contraseña de base de datos',
            'session.key' => 'Clave de sesión',
            'session.user_cookie.key_encript' => 'Clave de encriptación de cookies',
            'mail.password' => 'Contraseña de correo',
            'mail.username' => 'Usuario de correo',
            'redis.password' => 'Contraseña de Redis',
        ];

        foreach ($sensitiveKeys as $keyPath => $description) {
            $value = $this->getNestedConfigValue($config, $keyPath);

            if ($value !== null && !empty($value)) {
                $isDevelopment = $this->isDevelopmentCredential($value);

                if ($isDevelopment) {
                    $this->warnings[] = [
                        'phase' => 'Environment',
                        'file' => 'versaWYS/kernel/config/config.json',
                        'message' => "{$description} parece ser de desarrollo: " . substr($value, 0, 10) . '...',
                        'suggestion' => 'Usar variables de entorno o cambiar credenciales en producción',
                    ];
                }
            }
        }
    }

    private function getNestedConfigValue(array $config, string $keyPath): mixed
    {
        $keys = explode('.', $keyPath);
        $value = $config;

        foreach ($keys as $key) {
            if (!isset($value[$key])) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    private function isDevelopmentCredential(string $value): bool
    {
        $devPatterns = [
            '/^(test|dev|desarrollo|prueba)$/i',
            '/^(admin|password|123456|secret)$/i',
            '/localhost/i',
            '/example\.com/i',
        ];

        foreach ($devPatterns as $pattern) {
            if (!preg_match($pattern, $value)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function extractPathsFromConfig(): array
    {
        $configPath =
            $this->projectRoot
            . DIRECTORY_SEPARATOR
            . 'versaWYS'
            . DIRECTORY_SEPARATOR
            . 'kernel'
            . DIRECTORY_SEPARATOR
            . 'config'
            . DIRECTORY_SEPARATOR
            . 'config.json';
        $writableDirs = [];

        if (!file_exists($configPath)) {
            return [
                'versaWYS/logs' => 'Logs de aplicación (fallback)',
                'versaWYS/cache' => 'Caché del framework (fallback)',
            ];
        }

        $configContent = file_get_contents($configPath);
        $config = json_decode($configContent, true);

        if (!$config) {
            return [];
        }

        if (isset($config['database']['log_path'])) {
            $path = $this->normalizePath($config['database']['log_path']);
            $writableDirs[$path] = 'Logs de ORM/Database';
        }

        if (isset($config['twig']['compiled_dir'])) {
            $path = $this->normalizePath($config['twig']['compiled_dir']);
            $writableDirs[$path] = 'Caché de plantillas Twig';
        }

        if (isset($config['logging']['dir'])) {
            $path = $this->normalizePath($config['logging']['dir']);
            $writableDirs[$path] = 'Logs de aplicación';
        }

        if (isset($config['build']['cacheRouteFile'])) {
            $path = $this->normalizePath(dirname($config['build']['cacheRouteFile']));
            $writableDirs[$path] = 'Caché de rutas compiladas';
        }

        if (isset($config['rate_limiting']['cache_dir'])) {
            $path = $this->normalizePath($config['rate_limiting']['cache_dir']);
            $writableDirs[$path] = 'Caché de rate limiting';
        }

        if (isset($config['assets']['dashboard'])) {
            foreach ($config['assets']['dashboard'] as $assetType => $assetConfig) {
                if (!isset($assetConfig['dist'])) {
                    continue;
                }

                $path = $this->normalizePath($assetConfig['dist']);
                $description = ucfirst($assetType);
                $writableDirs[$path] = "Assets compilados ({$description})";
            }
        }

        return array_unique($writableDirs);
    }

    private function normalizePath(string $path): string
    {
        $path = preg_replace('/^\.\//', '', $path);
        return rtrim($path, '/');
    }

    private function scanHardcodedCredentials(): void
    {
        echo CLIErrorHandler::colorize("▶ FASE 7/7: Buscando credenciales hardcodeadas\n", 'cyan');

        $directories = [
            $this->projectRoot . DIRECTORY_SEPARATOR . 'app',
            $this->projectRoot . DIRECTORY_SEPARATOR . 'versaWYS' . DIRECTORY_SEPARATOR . 'kernel',
        ];

        $patterns = [
            '/password\s*=\s*["\"][^"\"]{3,}["\"](?!\s*;)/i' => 'Password hardcodeado posible',
            '/api[_-]?key\s*=\s*["\"][^"\"]{10,}["\"](?!\s*;)/i' => 'API key hardcodeada posible',
            '/secret\s*=\s*["\"][^"\"]{5,}["\"](?!\s*;)/i' => 'Secret hardcodeado posible',
            '/token\s*=\s*["\"][^"\"]{10,}["\"](?!\s*;)/i' => 'Token hardcodeado posible',
        ];

        $foundIssues = false;

        foreach ($directories as $directory) {
            $this->scanDirectoryForPatterns($directory, $patterns, 'credentials', function (
                $file,
                $line,
                $lineNumber,
                $pattern,
                $message,
            ) use (&$foundIssues) {
                if (preg_match('/(getenv|env\(|config\(|\$_ENV|\$config)/i', $line)) {
                    return;
                }

                if (preg_match('/(class|function|const)\s+/i', $line)) {
                    return;
                }

                $foundIssues = true;
                $this->warnings[] = [
                    'phase' => 'Credentials',
                    'file' => $this->getRelativePath($file),
                    'line' => $lineNumber,
                    'content' => trim($line),
                    'message' => $message,
                    'suggestion' => 'Mover a variables de entorno (.env) o config.json',
                ];
            });
        }

        if (!$foundIssues) {
            CLIErrorHandler::success("  ✓ Credentials: Sin problemas detectados\n");
        } else {
            echo
                CLIErrorHandler::colorize(
                    '  ⚠ Credentials: '
                    . count(array_filter($this->warnings, static fn($w) => 'Credentials' === $w['phase']))
                    . " advertencias encontradas\n",
                    'yellow',
                )
            ;
        }

        echo "\n";
    }

    private function scanDirectoryForPatterns(
        string $directory,
        array $patterns,
        string $type,
        callable $callback,
    ): void {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $directory,
            \RecursiveDirectoryIterator::SKIP_DOTS,
        ));

        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) {
                continue;
            }

            $filePath = $file->getPathname();

            $scanKey = $type . '::' . $filePath;
            if (in_array($scanKey, $this->scannedFiles, true)) {
                continue;
            }
            $this->scannedFiles[] = $scanKey;

            $content = file_get_contents($filePath);
            $lines = explode("\n", $content);

            foreach ($lines as $lineNumber => $line) {
                foreach ($patterns as $pattern => $message) {
                    if (!preg_match($pattern, $line)) {
                        continue;
                    }

                    $callback($filePath, $line, $lineNumber + 1, $pattern, $message);
                }
            }
        }
    }

    private function printSummary(): void
    {
        CLIErrorHandler::printSeparator();
        echo CLIErrorHandler::colorize("═══ RESUMEN DE DEPLOYMENT ═══\n", 'blue');
        CLIErrorHandler::printSeparator();
        echo "\n";

        if (!empty($this->issues)) {
            echo CLIErrorHandler::colorize('🔴 PROBLEMAS CRÍTICOS (' . count($this->issues) . "):\n", 'red');
            foreach ($this->issues as $issue) {
                echo "  [{$issue['phase']}] {$issue['message']}\n";
                if (isset($issue['file'])) {
                    echo "    Archivo: {$issue['file']}" . (isset($issue['line']) ? ":{$issue['line']}" : '') . "\n";
                }
                if (isset($issue['suggestion'])) {
                    echo '    ' . CLIErrorHandler::colorize("💡 {$issue['suggestion']}\n", 'yellow');
                }
                echo "\n";
            }
        }

        if (!empty($this->warnings)) {
            echo CLIErrorHandler::colorize('⚠️  ADVERTENCIAS (' . count($this->warnings) . "):\n", 'yellow');

            $grouped = [];
            foreach ($this->warnings as $warning) {
                $grouped[$warning['phase']][] = $warning;
            }

            foreach ($grouped as $phase => $warnings) {
                echo "  [{$phase}] " . count($warnings) . " advertencias\n";

                $limit = $this->verbose ? count($warnings) : 3;
                foreach (array_slice($warnings, 0, $limit) as $warning) {
                    if (isset($warning['file'])) {
                        echo "    • {$warning['file']}" . (isset($warning['line']) ? ":{$warning['line']}" : '') . "\n";
                        if (isset($warning['content'])) {
                            $content = $this->verbose ? $warning['content'] : substr($warning['content'], 0, 80);
                            echo '      ' . CLIErrorHandler::colorize($content, 'yellow') . "\n";
                        }
                    }
                    if (isset($warning['message'])) {
                        echo "      {$warning['message']}\n";
                    }
                    if (isset($warning['suggestion']) && $this->verbose) {
                        echo '    ' . CLIErrorHandler::colorize("💡 {$warning['suggestion']}\n", 'cyan');
                    }
                }

                if (!$this->verbose && count($warnings) > 3) {
                    echo
                        '    '
                            . CLIErrorHandler::colorize(
                                '... y ' . (count($warnings) - 3) . " más (usa --verbose para ver todos)\n",
                                'blue',
                            )
                    ;
                }
                echo "\n";
            }
        }

        if (!empty($this->info)) {
            echo CLIErrorHandler::colorize('ℹ️  INFORMACIÓN (' . count($this->info) . "):\n", 'blue');
            foreach ($this->info as $info) {
                if (isset($info['phase'])) {
                    echo "  [{$info['phase']}] {$info['message']}\n";
                } else {
                    echo "  {$info['message']}\n";
                }
                if (isset($info['suggestion'])) {
                    echo '    ' . CLIErrorHandler::colorize("💡 {$info['suggestion']}\n", 'cyan');
                }
            }
            echo "\n";
        }

        CLIErrorHandler::printSeparator();
        if (empty($this->issues)) {
            echo CLIErrorHandler::colorize("✅ LISTO PARA DEPLOYMENT\n", 'green');
            if (!empty($this->warnings)) {
                echo
                    CLIErrorHandler::colorize(
                        '⚠️  Revisar ' . count($this->warnings) . " advertencias antes de desplegar\n",
                        'yellow',
                    )
                ;
            }
        } else {
            echo CLIErrorHandler::colorize("❌ NO LISTO PARA DEPLOYMENT\n", 'red');
            echo
                CLIErrorHandler::colorize(
                    'Resolver ' . count($this->issues) . " problemas críticos antes de continuar\n",
                    'red',
                )
            ;
        }
        CLIErrorHandler::printSeparator();
    }

    private function getRelativePath(string $path): string
    {
        $projectRoot = $this->projectRoot !== '' ? realpath($this->projectRoot) : realpath(__DIR__ . '/../../');
        $realPath = realpath($path);

        if (false !== $realPath && str_starts_with($realPath, $projectRoot)) {
            return str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $realPath);
        }

        return $path;
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Valida compatibilidad de deployment Windows → Linux (PSR-4 + config.json + 6 validaciones)';
    }

    #[\Override]
    public function getHelp(): string
    {
        return <<<'HELP'
            Uso: php versaCLI app:check:deploy [opciones]

            Este comando ejecuta 7 fases de validación para asegurar compatibilidad
            entre el entorno de desarrollo Windows y el servidor Linux de producción.
            Lee dinámicamente las rutas desde config.json para validar los directorios
            que realmente necesita el proyecto.

            OPCIONES:
              --verbose, -v, --debug   Mostrar todos los detalles de advertencias (no solo primeras 3)

            FASES DE VALIDACIÓN:
              1. PSR-4 y Case-Sensitivity  - Valida namespaces, use statements y paths
              2. Windows Paths             - Detecta backslashes y rutas absolutas C:\\
              3. Windows Commands          - Busca comandos específicos de Windows (dir, copy, etc.)
              4. Line Endings              - Verifica CRLF vs LF en archivos críticos
              5. Environment Config        - Valida .env, .gitignore, debug mode, credenciales en config.json
              6. Permissions Structure     - Lee rutas desde config.json y valida directorios necesarios
              7. Hardcoded Credentials     - Detecta passwords/tokens hardcodeados en código PHP

            SALIDA:
              🔴 Problemas Críticos - Deben resolverse antes de deployment
              ⚠️  Advertencias      - Requieren revisión
              ℹ️  Información       - Recordatorios y sugerencias

            EXIT CODES:
              0 - Sin problemas críticos (listo para deployment)
              1 - Problemas críticos encontrados (no desplegar)

            EJEMPLOS:
              php versaCLI app:check:deploy              Ejecutar validación (resumen)
              php versaCLI app:check:deploy --verbose    Ver todos los detalles
              php versaCLI app:check:deploy -v           Alias de --verbose
            HELP;
    }
}
