<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;
use PDO;
use PDOException;

class StatusManager
{
    public static function handleStatus(bool $verbose = false): void
    {
        if ($verbose) {
            CLIErrorHandler::info('Verificando estado del sistema versaWYS');
        }

        echo CLIErrorHandler::colorize('📊 Estado del Sistema versaWYS', 'cyan') . "\n";
        echo str_repeat('═', 50) . "\n\n";

        // Información básica del sistema
        echo CLIErrorHandler::colorize('🖥️  Sistema:', 'yellow') . "\n";
        echo '   PHP: ' . PHP_VERSION . "\n";
        echo '   OS: ' . PHP_OS . ' (' . php_uname('m') . ")\n";
        echo '   SAPI: ' . PHP_SAPI . "\n\n";

        // Información runtime/extensiones (informativa, no bloqueante)
        self::printPostDeployRuntimeInfo();

        // Información del proyecto
        echo CLIErrorHandler::colorize('📁 Proyecto:', 'yellow') . "\n";
        echo '   Directorio: ' . getcwd() . "\n";

        $packageJsonPath = getcwd() . '/package.json';
        if (file_exists($packageJsonPath)) {
            $packageData = json_decode(file_get_contents($packageJsonPath), true);
            if (isset($packageData['version'])) {
                echo '   Versión: ' . $packageData['version'] . "\n";
            }

            if (isset($packageData['name'])) {
                echo '   Nombre: ' . $packageData['name'] . "\n";
            }
        }

        // Verificar configuración
        echo "\n" . CLIErrorHandler::colorize('⚙️  Configuración:', 'yellow') . "\n";
        $configPath = ConfigManager::path();
        if (file_exists($configPath)) {
            echo "   ✅ Archivo de configuración encontrado\n";
            $config = json_decode(file_get_contents($configPath), true);
            if ($config) {
                echo '   Debug: ' . ($config['build']['debug'] ?? false ? '🟢 Activado' : '🔴 Desactivado') . "\n";
                echo '   Cache Twig: ' . ($config['twig']['cache'] ?? false ? '🟢 Activado' : '🔴 Desactivado') . "\n";
                echo
                    '   Cache Rutas: '
                        . ($config['build']['cacheRoute'] ?? false ? '🟢 Activado' : '🔴 Desactivado')
                        . "\n"
                ;
            }
        } else {
            echo "   ❌ Archivo de configuración no encontrado\n";
        }

        // Verificar base de datos
        echo "\n" . CLIErrorHandler::colorize('🗄️  Base de Datos:', 'yellow') . "\n";

        try {
            $configPath = ConfigManager::path();
            if (file_exists($configPath)) {
                $config = json_decode(file_get_contents($configPath), true);
                if (isset($config['database'])) {
                    $dbConfig = $config['database'];
                    echo '   Host: ' . ($dbConfig['host'] ?? 'No configurado') . "\n";
                    echo '   Base de datos: ' . ($dbConfig['database'] ?? 'No configurado') . "\n";
                    echo '   Puerto: ' . ($dbConfig['port'] ?? 'No configurado') . "\n";

                    // Intentar conexión
                    if (isset($dbConfig['host'], $dbConfig['database'], $dbConfig['username'])) {
                        echo '   Estado: ';

                        try {
                            // Usar el driver configurado en config.json
                            $driver = $dbConfig['driver'] ?? 'mysql';
                            // Mapear 'postgresql' a 'pgsql' para PDO
                            if ($driver === 'postgresql') {
                                $driver = 'pgsql';
                            }
                            $dsn = sprintf(
                                '%s:host=%s;port=%s;dbname=%s',
                                $driver,
                                $dbConfig['host'],
                                $dbConfig['port'],
                                $dbConfig['database'],
                            );
                            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'] ?? '');
                            echo "🟢 Conectado\n";
                        } catch (PDOException $e) {
                            echo "🔴 Error de conexión\n";
                            if ($verbose) {
                                echo '   Error: ' . $e->getMessage() . "\n";
                            }
                        }
                    }
                } else {
                    echo "   ❌ Configuración de base de datos no encontrada\n";
                }
            }
        } catch (Exception $exception) {
            echo "   ❌ Error verificando base de datos\n";
            if ($verbose) {
                echo '   Error: ' . $exception->getMessage() . "\n";
            }
        }

        // Verificar directorios importantes
        echo "\n" . CLIErrorHandler::colorize('📂 Directorios:', 'yellow') . "\n";
        $directories = [
            'app/controllers' => 'Controladores',
            'app/models' => 'Modelos',
            'app/routes' => 'Rutas',
            'app/middleware' => 'Middleware',
            'app/migrations' => 'Migraciones',
            'app/seeders' => 'Seeders',
            'app/templates' => 'Templates',
            'src/dashboard/js' => 'JavaScript Dashboard',
            'public/dashboard' => 'Assets públicos',
            'versaWYS/logs' => 'Logs',
            'versaWYS/cache' => 'Cache',
        ];

        foreach ($directories as $dir => $name) {
            $status = is_dir($dir) ? '🟢' : '🔴';
            echo "   {$status} {$name} ({$dir})\n";
        }

        // Archivos importantes
        echo "\n" . CLIErrorHandler::colorize('📄 Archivos importantes:', 'yellow') . "\n";
        $files = [
            'index.php' => 'Punto de entrada',
            'versaCLI' => 'CLI versaWYS',
            'package.json' => 'Configuración Node.js',
            'tsconfig.json' => 'Configuración TypeScript',
            'tailwind.config.js' => 'Configuración Tailwind',
            'versacompile.config.ts' => 'Configuración del compilador',
        ];

        foreach ($files as $file => $name) {
            $status = file_exists($file) ? '🟢' : '🔴';
            echo "   {$status} {$name} ({$file})\n";
        }

        echo "\n" . str_repeat('═', 50) . "\n";
        echo CLIErrorHandler::colorize('Estado del sistema verificado ✓', 'green') . "\n\n";
    }

    /**
     * Sección informativa de runtime para despliegue Linux (no bloqueante).
     */
    private static function printPostDeployRuntimeInfo(): void
    {
        echo CLIErrorHandler::colorize('📦 Runtime de despliegue (informativo):', 'yellow') . "\n";

        $extensions = self::getComposerRuntimeExtensions();
        $polyfills = self::getAvailablePolyfills();
        $config = self::readProjectConfig();

        if ($extensions === []) {
            echo "   ⚠ No se pudo leer versaWYS/composer.lock para validar extensiones\n\n";
            return;
        }

        $loadedCount = 0;
        $polyfillCount = 0;
        $missingCount = 0;

        foreach ($extensions as $extension => $constraint) {
            $extensionName = substr($extension, 4);
            $isLoaded = extension_loaded($extensionName);
            $hasPolyfill = $polyfills[$extension] ?? false;

            if ($isLoaded) {
                $loadedCount++;
                continue;
            }

            if ($hasPolyfill) {
                $polyfillCount++;
                continue;
            }

            $missingCount++;
        }

        echo '   Extensiones runtime (composer): ';
        echo CLIErrorHandler::colorize((string) $loadedCount, 'green') . ' cargadas';
        echo ', ' . CLIErrorHandler::colorize((string) $polyfillCount, 'yellow') . ' por polyfill';
        echo ', ' . CLIErrorHandler::colorize((string) $missingCount, 'red') . " faltantes\n";

        echo "\n";
        echo CLIErrorHandler::colorize('   Obligatorias por proyecto:', 'blue') . "\n";

        $projectRequired = self::getProjectRequiredExtensions($config);
        $missingProject = [];
        foreach ($projectRequired as $ext => $reason) {
            $loaded = extension_loaded($ext);
            $icon = $loaded ? CLIErrorHandler::colorize('✓', 'green') : CLIErrorHandler::colorize('✗', 'red');
            echo "     {$icon} ext-{$ext} ({$reason})\n";

            if (!$loaded) {
                $missingProject[] = 'ext-' . $ext;
            }
        }

        echo "\n";
        echo CLIErrorHandler::colorize('   Obligatorias por Composer (runtime):', 'blue') . "\n";

        $missingComposer = [];
        foreach ($extensions as $extension => $constraint) {
            $extensionName = substr($extension, 4);
            $isLoaded = extension_loaded($extensionName);
            $hasPolyfill = $polyfills[$extension] ?? false;

            if ($isLoaded) {
                $icon = CLIErrorHandler::colorize('✓', 'green');
                echo "     {$icon} {$extension} ({$constraint})\n";
                continue;
            }

            if ($hasPolyfill) {
                $icon = CLIErrorHandler::colorize('~', 'yellow');
                echo "     {$icon} {$extension} (cubierta por polyfill)\n";
                continue;
            }

            $icon = CLIErrorHandler::colorize('✗', 'red');
            echo "     {$icon} {$extension} (faltante)\n";
            $missingComposer[] = $extension;
        }

        // Features relevantes del proyecto
        $databaseDriver = (string) ($config['database']['driver'] ?? '');
        if ($databaseDriver === 'postgresql') {
            $dbOk = extension_loaded('pdo_pgsql');
            echo '   PostgreSQL (pdo_pgsql): ' . ($dbOk ? '🟢' : '🔴') . "\n";
        }

        $curlOk = function_exists('curl_init');
        echo '   cURL (curl_init): ' . ($curlOk ? '🟢' : '🔴') . "\n";

        $redisEnabled = (bool) ($config['redis']['enabled'] ?? false);
        if ($redisEnabled) {
            $redisOk = extension_loaded('redis');
            echo '   Redis extension (redis): ' . ($redisOk ? '🟢' : '🔴') . "\n";
        }

        $missingAll = array_values(array_unique(array_merge($missingProject, $missingComposer)));
        if ($missingAll !== []) {
            $packages = self::mapExtensionsToLinuxPackages($missingAll);
            echo "\n";
            echo CLIErrorHandler::colorize('   Paquetes PHP sugeridos para instalar (Linux):', 'yellow') . "\n";
            if ($packages !== []) {
                echo '     sudo apt install -y ' . implode(' ', $packages) . "\n";
            } else {
                echo '     (No se pudo mapear automáticamente. Revisa ext faltantes arriba)\n';
            }
        }

        echo "\n";
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, string> [extension_name => reason]
     */
    private static function getProjectRequiredExtensions(array $config): array
    {
        $required = [
            'pdo' => 'Base de datos vía PDO',
            'curl' => 'Webhooks/API outbound',
            'json' => 'Intercambio de datos JSON',
            'mbstring' => 'Manejo UTF-8',
            'fileinfo' => 'Detección MIME de archivos',
            'zip' => 'Archivos Office/zip',
            'xml' => 'Procesamiento XML',
            'dom' => 'DOM/XML requerido por librerías',
        ];

        $databaseDriver = (string) ($config['database']['driver'] ?? '');
        if ($databaseDriver === 'postgresql') {
            $required['pdo_pgsql'] = 'Driver PostgreSQL';
        }

        $redisEnabled = (bool) ($config['redis']['enabled'] ?? false);
        if ($redisEnabled) {
            $required['redis'] = 'Cache/colas Redis habilitado en config';
        }

        return $required;
    }

    /**
     * @param array<int, string> $extensions Extensiones con prefijo ext-
     * @return array<int, string>
     */
    private static function mapExtensionsToLinuxPackages(array $extensions): array
    {
        $map = [
            'ext-pdo_pgsql' => 'php-pgsql',
            'ext-curl' => 'php-curl',
            'ext-mbstring' => 'php-mbstring',
            'ext-zip' => 'php-zip',
            'ext-gd' => 'php-gd',
            'ext-xml' => 'php-xml',
            'ext-dom' => 'php-xml',
            'ext-simplexml' => 'php-xml',
            'ext-xmlreader' => 'php-xml',
            'ext-xmlwriter' => 'php-xml',
            'ext-redis' => 'php-redis',
            'ext-intl' => 'php-intl',
        ];

        $packages = [];
        foreach ($extensions as $extension) {
            if (!isset($map[$extension])) {
                continue;
            }

            $packages[] = $map[$extension];
        }

        $packages = array_values(array_unique($packages));
        sort($packages);

        return $packages;
    }

    /**
     * Obtiene extensiones runtime requeridas desde composer.lock (sección packages).
     *
     * @return array<string, string>
     */
    private static function getComposerRuntimeExtensions(): array
    {
        $lockPath = getcwd() . '/versaWYS/composer.lock';
        if (!file_exists($lockPath)) {
            return [];
        }

        $content = file_get_contents($lockPath);
        if (!is_string($content) || $content === '') {
            return [];
        }

        $lock = json_decode($content, true);
        if (!is_array($lock) || !isset($lock['packages']) || !is_array($lock['packages'])) {
            return [];
        }

        $extensions = [];
        foreach ($lock['packages'] as $package) {
            if (!is_array($package) || !isset($package['require']) || !is_array($package['require'])) {
                continue;
            }

            foreach ($package['require'] as $dependency => $constraint) {
                if (!is_string($dependency) || !str_starts_with($dependency, 'ext-')) {
                    continue;
                }

                if (!isset($extensions[$dependency])) {
                    $extensions[$dependency] = is_string($constraint) ? $constraint : '*';
                }
            }
        }

        ksort($extensions);

        return $extensions;
    }

    /**
     * @return array<string, bool>
     */
    private static function getAvailablePolyfills(): array
    {
        return [
            'ext-ctype' => class_exists('Symfony\\Polyfill\\Ctype\\Ctype'),
            'ext-mbstring' => class_exists('Symfony\\Polyfill\\Mbstring\\Mbstring'),
            'ext-iconv' => class_exists('Symfony\\Polyfill\\Iconv\\Iconv'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function readProjectConfig(): array
    {
        $configPath = ConfigManager::path();
        if (!file_exists($configPath)) {
            return [];
        }

        $content = file_get_contents($configPath);
        if (!is_string($content) || $content === '') {
            return [];
        }

        $json = json_decode($content, true);

        return is_array($json) ? $json : [];
    }
}
