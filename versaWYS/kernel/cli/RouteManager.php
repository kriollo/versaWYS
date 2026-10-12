<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class RouteManager
{
    private static string $path = 'app/routes/';

    public static function createRoute(string $routeNameModule): void
    {
        $controllerName = ucfirst($routeNameModule) . 'Controller';
        $routeName = ucfirst($routeNameModule) . 'Routes';
        $routeNameModule = strtolower($routeNameModule);
        $middlewareName = ucfirst($routeNameModule) . 'Middleware';

        CLIErrorHandler::info("Creando ruta {$routeName}...");
        $routesFile = self::$path . ($routeName . '.php');

        if (file_exists($routesFile)) {
            if (!CLIHelper::confirm("La ruta {$routesFile} ya existe. Desea sobreescribirlo?")) {
                CLIErrorHandler::info('Operación cancelada.');
                exit();
            }

            unlink($routesFile);
        }

        $template = <<<'EOT'
            <?php

            declare(strict_types=1);

            namespace app\routes;

            use versaWYS\kernel\Router;
            use app\controllers\$controllerName;
            use app\middleware\AuthMiddleware;
            // use app\middleware\$middlewareName;

            // Rutas de Navegador
            // index => funcion que se ejecutara en el controlador
            Router::get('/admin/$routeNameModule',
                [$controllerName::class,'index']
            )->middleware([
                [AuthMiddleware::class, 'checkSession'],
                [AuthMiddleware::class, 'onlyUserWithAccess'],
                // [$middlewareName::class, 'middlewareMethod'],
            ]);

            EOT;

        $template = str_replace('$routeNameModule', $routeNameModule, $template);
        $template = str_replace('$routeName', $routeName, $template);
        $template = str_replace('$controllerName', $controllerName, $template);
        $template = str_replace('$middlewareName', $middlewareName, $template);

        CLIHelper::ensureDirectory(self::$path);

        if (file_put_contents($routesFile, $template)) {
            chmod($routesFile, 0o775);
            CLIErrorHandler::success("Ruta {$routesFile} creada.");
        } else {
            CLIErrorHandler::warning("No se pudo crear el archivo: {$routesFile}");
        }
    }

    public static function deleteRoute(string $routeName): void
    {
        $routeName = ucfirst($routeName) . 'Routes';

        CLIErrorHandler::info("Eliminando ruta {$routeName}...");
        $routesFile = self::$path . ($routeName . '.php');

        if (!file_exists($routesFile)) {
            CLIErrorHandler::warning("La ruta {$routesFile} no existe.");
            exit();
        }

        if (unlink($routesFile)) {
            CLIErrorHandler::success("Ruta {$routesFile} eliminada.");
        } else {
            CLIErrorHandler::warning("No se pudo eliminar: {$routesFile}");
        }
    }

    public static function getRoutes(): array
    {
        if (!is_dir(self::$path)) {
            return [];
        }
        $routes = scandir(self::$path);
        $routes = array_filter($routes, static fn ($route): bool => !in_array($route, ['.', '..'], strict: true));
        return array_values($routes);
    }

    public static function listRoutes(): void
    {
        $routes = self::getRoutes();

        CLIErrorHandler::info('Rutas:');
        $n = 1;
        foreach ($routes as $route) {
            echo sprintf('%d - %s%s', $n, $route, PHP_EOL);
            $n++;
        }

        echo "\n";
        $line = CLIHelper::ask('Seleccione una ruta');

        if (!is_numeric($line)) {
            CLIErrorHandler::info('Saliendo...');
            exit();
        }

        $line = (int) $line;
        if ($line < 1 || $line > count($routes)) {
            CLIErrorHandler::info('Saliendo...');
            exit();
        }

        $route = $routes[$line - 1];
        self::listarRutasFromRoute($route);
    }

    public static function getRoutesFromRouteFile($routeFile): array
    {
        $routeFile = self::$path . $routeFile;
        if (!file_exists($routeFile)) {
            echo "La ruta {$routeFile} no existe.\n";
            exit();
        }

        $routeContent = file_get_contents($routeFile);

        $pattern = '/Router::(\w+)\(\s*[\'"]([^\'"]+)[\'"](?:[^;]*\[\s*([^]]+),\s*([^]]+)])?(?:[^;]*\[\s*([^]]+),\s*([^]]+)])?\s*\)/s';
        $routeContent = preg_replace('/\s+/', ' ', $routeContent); // Eliminar saltos de línea y espacios en blanco adicionales
        preg_match_all($pattern, $routeContent, $matches, PREG_SET_ORDER);

        return $matches;
    }

    /**
     * Extrae datos de rutas de un archivo, incluyendo soporte para grupos anidados.
     *
     * @param string $routeFile Nombre del archivo de rutas
     *
     * @return array Datos de las rutas
     */
    public static function extractRouteData(string $routeFile): array
    {
        $routeFile = self::$path . $routeFile;
        if (!file_exists($routeFile)) {
            echo "La ruta {$routeFile} no existe.\n";
            exit();
        }

        $routeContent = file_get_contents($routeFile);

        // Dividir el contenido en líneas para procesar
        $lines = preg_split('/\r\n|\r|\n/', $routeContent);

        // Estructura para almacenar rutas
        $allRoutesData = [];

        // Estructura para manejar grupos y su anidamiento
        $groupStack = [];
        $currentPath = '';
        $currentMiddleware = [];

        // Variables para almacenar documentación
        $currentDescription = '';
        $currentRequestBody = [];
        $currentQueryParams = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line === '0') {
                continue;
            }

            // Capturar documentación
            if (str_starts_with($line, '// description:')) {
                $currentDescription = trim(substr($line, 15));

                continue;
            }

            // Capturar request-body
            if (str_starts_with($line, '// request-body')) {
                if (preg_match(
                    '/\/\/\s*request-body\s+name:(.+?)\s+type:(.+?)\s+description:(.+)$/',
                    $line,
                    $matches,
                )) {
                    $currentRequestBody[] = [
                        'name' => trim($matches[1]),
                        'type' => trim($matches[2]),
                        'description' => trim($matches[3]),
                    ];
                }

                continue;
            }

            // Capturar query-param
            if (str_starts_with($line, '// query-param')) {
                if (preg_match('/\/\/\s*query-param\s+name:(.+?)\s+type:(.+?)\s+description:(.+)$/', $line, $matches)) {
                    $currentQueryParams[] = [
                        'name' => trim($matches[1]),
                        'type' => trim($matches[2]),
                        'description' => trim($matches[3]),
                    ];
                }

                continue;
            }

            // Detectar inicio de grupo
            if (preg_match('/Router::group\s*\(\s*[\'"](.*?)[\'"](.*?)function\s*\(\s*\)\s*\{/', $line, $matches)) {
                $groupPrefix = $matches[1];

                // Detectar middleware del grupo
                // Primero verificamos si hay middleware en la línea actual
                $groupMiddleware = [];
                if (preg_match('/,\s*\[(.*?)\]\s*\)/', $matches[2], $mwMatches)) {
                    $middlewareStr = $mwMatches[1];
                    $groupMiddleware = self::parseMiddleware($middlewareStr);
                } else {
                    // Si no, buscamos en las próximas líneas por un cierre de grupo con middleware
                    $nextLineIndex = array_search($line, $lines, true) + 1;
                    while ($nextLineIndex < count($lines)) {
                        $nextLine = trim($lines[$nextLineIndex]);
                        if (preg_match('/\},\s*\[(.*?)\]\s*\);/', $nextLine, $mwMatches)) {
                            $middlewareStr = $mwMatches[1];
                            $groupMiddleware = self::parseMiddleware($middlewareStr);
                            break;
                        }

                        if (str_starts_with($nextLine, '});')) {
                            break;
                        }

                        $nextLineIndex++;
                    }
                }

                // Guardar estado actual del grupo
                $groupStack[] = [
                    'prefix' => $groupPrefix,
                    'middleware' => $groupMiddleware,
                    'description' => $currentDescription,
                ];

                // Actualizar path actual con el prefijo del grupo
                $currentPath = '';
                foreach ($groupStack as $group) {
                    $currentPath .= $group['prefix'];
                }

                // Actualizar middleware actual con el del grupo
                $currentMiddleware = [];
                foreach ($groupStack as $group) {
                    $currentMiddleware = array_merge($currentMiddleware, $group['middleware']);
                }

                // Reiniciar descripción después de usarla
                $currentDescription = '';

                continue;
            }

            // Detectar cierre de grupo
            if (str_starts_with($line, '});') || str_starts_with($line, '}, [')) {
                // Quitar el último grupo de la pila
                if ($groupStack !== []) {
                    array_pop($groupStack);
                }

                // Recalcular path y middleware actuales
                $currentPath = '';
                $currentMiddleware = [];
                foreach ($groupStack as $group) {
                    $currentPath .= $group['prefix'];
                    $currentMiddleware = array_merge($currentMiddleware, $group['middleware']);
                }

                continue;
            }

            // Detectar definición de ruta
            if (preg_match(
                '/Router::(group|get|post|put|patch|delete)\s*\(\s*[\'"](.*?)[\'"](.*?)\[([^\]]+)\]\)/',
                $line,
                $matches,
            )) {
                $method = strtoupper($matches[1]);
                $routePath = $matches[2];
                $callback = $matches[4];

                // Manejar rutas vacías dentro de grupos (como '')
                if (($routePath === '' || $routePath === '0') && ($currentPath !== '' && $currentPath !== '0')) {
                    $routePath = '';
                }

                // Path completo incluyendo prefijos de grupo
                $fullPath = $currentPath . $routePath;

                // Extraer parámetros de ruta
                preg_match_all('/\{([^\}]+)\}/', $fullPath, $routeParamsMatches);
                $routeParams = $routeParamsMatches[1] ?? [];

                // Extraer clase y método del controlador
                $callbackParts = explode(',', str_replace(['[', ']', ' ', "'"], '', $callback));
                $class = $callbackParts[0] ?? null;
                $class_method = $callbackParts[1] ?? null;

                // Detectar middleware de la ruta
                $routeMiddleware = [];
                if (preg_match('/->middleware\s*\(\s*\[(.*?)\]\s*\)/', $line, $mwMatches)) {
                    $middlewareStr = $mwMatches[1];
                    $routeMiddleware = self::parseMiddleware($middlewareStr);
                }

                // Combinar middleware de grupos y ruta
                $allMiddleware = array_merge($currentMiddleware, $routeMiddleware);

                // Usar descripción de la ruta o heredar del grupo más cercano si está vacía
                $description = $currentDescription;
                if (($description === '' || $description === '0') && $groupStack !== []) {
                    $lastGroup = end($groupStack);
                    $description = $lastGroup['description'];
                }

                // Guardar datos de la ruta
                $allRoutesData[] = [
                    'route' => $fullPath,
                    'method' => $method,
                    'routeParams' => $routeParams,
                    'description' => $description,
                    'requestBody' => $currentRequestBody,
                    'queryParams' => $currentQueryParams,
                    'class' => $class,
                    'class_method' => $class_method,
                    'middleware' => $allMiddleware,
                ];

                // Reiniciar documentación después de usarla
                $currentDescription = '';
                $currentRequestBody = [];
                $currentQueryParams = [];
            }
        }

        return $allRoutesData;
    }

    public static function listarRutasFromRoute($routeFile): void
    {
        $matches = self::getRoutesFromRouteFile($routeFile);

        // Encabezados
        echo str_pad('Method', 10) . '|';
        echo str_pad('URI', 40) . '|';
        echo str_pad('Controller', 50) . '|';
        echo str_pad('Action', 20) . "|\n";
        // Línea separadora
        echo str_repeat('-', 10) . '|';
        echo str_repeat('-', 40) . '|';
        echo str_repeat('-', 50) . '|';
        echo str_repeat('-', 20) . "|\n";

        foreach ($matches as $match) {
            $method = $match[1];
            $path = $match[2];
            $controller = $match[3] ?? '';
            $actionController = $match[4] ?? '';

            echo str_pad($method, 10) . '|';
            echo str_pad($path, 40) . '|';
            echo str_pad($controller, 50) . '|';
            echo str_pad($actionController, 20) . "|\n";
        }
    }

    /**
     * Compila todas las rutas en un archivo único para optimizar el rendimiento.
     */
    public static function compileRoutes(): void
    {
        CLIErrorHandler::info('Iniciando compilación de rutas...');

        // Cargar configuración
        $configPath = __DIR__ . '/../config/config.json';
        if (!file_exists($configPath)) {
            CLIErrorHandler::warning('Error: No se encontró el archivo de configuración.');
            return;
        }

        $config = json_decode(file_get_contents($configPath), true);
        if ($config === null) {
            CLIErrorHandler::warning('Error: El archivo de configuración está corrupto.');
            return;
        }

        // Obtener configuración de caché
        $cacheEnabled = $config['build']['cacheRoute'] ?? false;
        $cacheFile = $config['build']['cacheRouteFile'] ?? './versaWYS/cache/routes.compiled.php';

        // Crear directorio de caché si no existe
        $cacheDir = dirname($cacheFile);

        try {
            CLIHelper::ensureDirectory($cacheDir, 0o775);
        } catch (\Exception $e) {
            CLIErrorHandler::warning($e->getMessage());
            return;
        }

        // Obtener todas las rutas
        $routes = self::getRoutes();
        $routesPath = self::$path;

        // Generar contenido compilado
        $compiledContent = "<?php\n\n";
        $compiledContent .= "// Archivo de rutas compilado generado automáticamente\n";
        $compiledContent .= '// Fecha de compilación: ' . date('Y-m-d H:i:s') . "\n";
        $compiledContent .= "// No editar manualmente este archivo\n\n";
        $compiledContent .= "declare(strict_types=1);\n\n";

        $totalRoutes = 0;

        foreach ($routes as $routeFile) {
            if (pathinfo($routeFile, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $routeFilePath = $routesPath . $routeFile;

            if (!file_exists($routeFilePath)) {
                CLIErrorHandler::warning(sprintf('Advertencia: No se pudo encontrar %s', $routeFilePath));
                continue;
            }

            echo sprintf('📄 Procesando: %s%s', $routeFile, PHP_EOL);

            // Leer el contenido del archivo de rutas
            $content = file_get_contents($routeFilePath);

            // Remover el <?php y declare del inicio
            $content = preg_replace('/^<\?php\s*/', '', $content);
            $content = preg_replace('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/', '', $content);

            // Remover los namespace y use statements, los agregaremos al inicio
            $content = preg_replace('/namespace\s+[^;]+;\s*/', '', $content);

            // Extraer use statements para agregarlos al inicio
            preg_match_all('/use\s+[^;]+;\s*/', $content, $useMatches);
            $content = preg_replace('/use\s+[^;]+;\s*/', '', $content);

            // Agregar comentario del archivo origen
            $compiledContent .= "\n// === Rutas de {$routeFile} ===\n";

            // Agregar use statements únicos
            foreach ($useMatches[0] as $useStatement) {
                if (str_contains($compiledContent, trim($useStatement))) {
                    continue;
                }

                $compiledContent .= $useStatement;
            }

            // Agregar el contenido procesado
            $compiledContent .= $content;

            // Contar rutas en el archivo
            $routeMatches = preg_match_all('/Router::(group|get|post|put|delete|patch|options)\s*\(/', $content);
            $totalRoutes += $routeMatches;
        }

        // Escribir archivo compilado
        if (file_put_contents($cacheFile, $compiledContent) !== false) {
            chmod($cacheFile, 0o775);
            CLIErrorHandler::success('Rutas compiladas exitosamente!');
            echo sprintf('📊 Total de rutas procesadas: %d%s', $totalRoutes, PHP_EOL);
            echo sprintf('📁 Archivo generado: %s%s', $cacheFile, PHP_EOL);

            // Minificar el archivo compilado
            echo "🗜️  Minificando archivo compilado...\n";
            $minifiedContent = self::minifyPhpCode($compiledContent);
            $minifiedFile = str_replace('.compiled.php', '.compiled.min.php', $cacheFile);

            if (file_put_contents($minifiedFile, $minifiedContent) !== false) {
                chmod($minifiedFile, 0o775);
                $originalSize = filesize($cacheFile);
                $minifiedSize = filesize($minifiedFile);
                $reduction = round((($originalSize - $minifiedSize) / $originalSize) * 100, 2);

                echo "✅ Archivo minificado creado!\n";
                echo sprintf('📁 Archivo minificado: %s%s', $minifiedFile, PHP_EOL);
                echo '💾 Tamaño original: ' . self::formatBytes($originalSize) . "\n";
                echo '💾 Tamaño minificado: ' . self::formatBytes($minifiedSize) . "\n";
                echo "📉 Reducción: {$reduction}%\n";

                // Actualizar configuración para usar archivo minificado
                if ($cacheEnabled) {
                    $config['build']['cacheRouteFile'] = str_replace(
                        '.compiled.php',
                        '.compiled.min.php',
                        $config['build']['cacheRouteFile'],
                    );
                    file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    chmod($configPath, 0o660);
                    echo "🔧 Configuración actualizada para usar archivo minificado.\n";
                }
            } else {
                CLIErrorHandler::warning('No se pudo crear el archivo minificado, usando el original.');
            }

            echo '💾 Tamaño del archivo: ' . self::formatBytes(filesize($cacheFile)) . "\n";

            // Actualizar la configuración para activar el caché si no está activo
            if (!$cacheEnabled) {
                CLIErrorHandler::info('El caché de rutas no está activo en la configuración.');

                if (CLIHelper::confirm('¿Desea activarlo ahora?', true)) {
                    $config['build']['cacheRoute'] = true;
                    if (file_put_contents($configPath, json_encode(
                        $config,
                        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                    ))) {
                        chmod($configPath, 0o660);
                        CLIErrorHandler::success('Caché de rutas activado en la configuración.');
                    } else {
                        CLIErrorHandler::warning('Error al actualizar la configuración.');
                    }
                }
            }
        } else {
            CLIErrorHandler::warning('Error al escribir el archivo compilado.');
        }
    }

    /**
     * Muestra el estado del sistema de caché de rutas.
     */
    public static function cacheStatus(): void
    {
        CLIErrorHandler::info('Estado del Sistema de Caché de Rutas');
        echo str_repeat('=', 50) . "\n";

        // Cargar configuración
        $configPath = __DIR__ . '/../config/config.json';
        if (!file_exists($configPath)) {
            CLIErrorHandler::warning('Error: No se encontró el archivo de configuración.');
            return;
        }

        $config = json_decode(file_get_contents($configPath), true);
        if ($config === null) {
            CLIErrorHandler::warning('Error: El archivo de configuración está corrupto.');
            return;
        }

        $cacheEnabled = $config['build']['cacheRoute'] ?? false;
        $cacheFile = $config['build']['cacheRouteFile'] ?? './versaWYS/cache/routes.compiled.php';
        $minifiedFile = str_replace('.compiled.php', '.compiled.min.php', $cacheFile);

        // Estado del caché
        echo '🔧 Estado del caché: ' . ($cacheEnabled ? '✅ ACTIVO' : '❌ INACTIVO') . "\n";
        echo sprintf('📁 Archivo de caché: %s%s', $cacheFile, PHP_EOL);
        echo sprintf('📁 Archivo minificado: %s%s', $minifiedFile, PHP_EOL);

        // Verificar si existe el archivo compilado
        $mainFileExists = file_exists($cacheFile);
        $minFileExists = file_exists($minifiedFile);

        if ($mainFileExists || $minFileExists) {
            if ($minFileExists) {
                $fileSize = filesize($minifiedFile);
                $modTime = filemtime($minifiedFile);
                echo "📄 Archivo minificado: ✅ EXISTE\n";

                if ($mainFileExists) {
                    $originalSize = filesize($cacheFile);
                    $reduction = round((($originalSize - $fileSize) / $originalSize) * 100, 2);
                    echo "📊 Reducción: {$reduction}%\n";
                }
            } else {
                $fileSize = filesize($cacheFile);
                $modTime = filemtime($cacheFile);
                echo "📄 Archivo compilado: ✅ EXISTE (sin minificar)\n";
            }

            echo '💾 Tamaño: ' . self::formatBytes($fileSize) . "\n";
            echo '🕒 Última compilación: ' . date('Y-m-d H:i:s', $modTime) . "\n";

            // Verificar si las rutas han cambiado desde la última compilación
            $routes = self::getRoutes();
            $routesPath = self::$path;
            $needsRecompile = false;

            foreach ($routes as $routeFile) {
                $routeFilePath = $routesPath . $routeFile;
                if (file_exists($routeFilePath) && filemtime($routeFilePath) > $modTime) {
                    $needsRecompile = true;
                    require_once $routeFilePath;
                    break;
                }
            }

            if ($needsRecompile) {
                CLIErrorHandler::warning('Estado: REQUIERE RECOMPILACIÓN (archivos de rutas modificados)');
                CLIErrorHandler::info('💡 Ejecute: php versaCLI route:compile');
            } else {
                CLIErrorHandler::success('Estado: ACTUALIZADO');
            }
        } else {
            CLIErrorHandler::info('Archivo compilado: ❌ NO EXISTE');
            if ($cacheEnabled) {
                CLIErrorHandler::warning('El caché está activo pero no hay archivo compilado.');
                CLIErrorHandler::info('💡 Ejecute: php versaCLI route:compile');
            }
        }

        // Estadísticas de rutas
        $routes = self::getRoutes();
        echo "\n📊 Estadísticas de Rutas:\n";
        echo '📁 Archivos de rutas: ' . count($routes) . "\n";

        $totalRoutes = 0;
        foreach ($routes as $routeFile) {
            $routeFilePath = self::$path . $routeFile;
            if (file_exists($routeFilePath)) {
                $content = file_get_contents($routeFilePath);
                $routeMatches = preg_match_all('/Router::(group|get|post|put|delete|patch|options)\s*\(/', $content);
                $totalRoutes += $routeMatches;
            }
        }

        echo sprintf('🛣️  Total de rutas: %d%s', $totalRoutes, PHP_EOL);
    }

    /**
     * Limpia el caché de rutas.
     */
    public static function clearCache(): void
    {
        CLIErrorHandler::info('Limpiando caché de rutas...');

        // Cargar configuración
        $configPath = __DIR__ . '/../config/config.json';
        if (!file_exists($configPath)) {
            CLIErrorHandler::warning('Error: No se encontró el archivo de configuración.');
            return;
        }

        $config = json_decode(file_get_contents($configPath), true);
        if ($config === null) {
            CLIErrorHandler::warning('Error: El archivo de configuración está corrupto.');
            return;
        }

        $cacheFile = $config['build']['cacheRouteFile'] ?? './versaWYS/cache/routes.compiled.php';
        $minifiedFile = str_replace('.compiled.php', '.compiled.min.php', $cacheFile);

        $deletedFiles = [];

        if (file_exists($cacheFile) && unlink($cacheFile)) {
            $deletedFiles[] = $cacheFile;
        }

        if (file_exists($minifiedFile) && unlink($minifiedFile)) {
            $deletedFiles[] = $minifiedFile;
        }

        if ($deletedFiles !== []) {
            CLIErrorHandler::success('Caché de rutas eliminado exitosamente.');
            foreach ($deletedFiles as $deletedFile) {
                echo sprintf('📁 Archivo eliminado: %s%s', $deletedFile, PHP_EOL);
            }
        } else {
            CLIErrorHandler::info('No hay archivos de caché para eliminar.');
        }

        // Actualizar configuración para desactivar caché
        $config['build']['cacheRoute'] = false;
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));
        chmod($configPath, 0o660);
        CLIErrorHandler::success('Configuración actualizada para desactivar el caché de rutas.');
    }

    /**
     * Analiza una cadena de middleware y la convierte en un array estructurado.
     *
     * @param string $middlewareStr Cadena de middleware
     *
     * @return array Array de middlewares
     */
    private static function parseMiddleware(string $middlewareStr): array
    {
        $middlewares = [];

        // Limpiar espacios y caracteres innecesarios
        $middlewareStr = preg_replace('/\s+/', '', $middlewareStr);

        // Manejar diferentes formatos de middleware
        if (str_starts_with($middlewareStr, '[[')) {
            // Formato [[Class,'method'],[Class,'method']]
            $items = explode('],[', substr($middlewareStr, 2, -2));
        } elseif (str_starts_with($middlewareStr, '[')) {
            // Formato [Class,'method']
            $items = [substr($middlewareStr, 1, -1)];
        } else {
            // Otros formatos
            $items = explode('],[', $middlewareStr);
        }

        foreach ($items as $item) {
            if ($item === '' || $item === '0') {
                continue;
            }

            // Extraer clase y método
            $parts = explode(',', $item);
            if (count($parts) >= 2) {
                $class = trim($parts[0], "'[]");
                $method = trim($parts[1], "'[]");

                $middlewares[] = [
                    'class' => $class,
                    'class_method' => $method,
                ];
            }
        }

        return $middlewares;
    }

    /**
     * Formatea bytes en una unidad legible.
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Minifica código PHP eliminando comentarios, espacios innecesarios y optimizando el formato.
     */
    private static function minifyPhpCode(string $code): string
    {
        // Obtener configuración de nivel de minificación
        $configPath = __DIR__ . '/../config/config.json';
        $aggressiveMinify = true; // Por defecto agresiva

        if (file_exists($configPath)) {
            $config = json_decode(file_get_contents($configPath), true);
            if ($config !== null) {
                $minifyLevel = $config['build']['cacheRouteMinifyLevel'] ?? 'aggressive';
                $aggressiveMinify = $minifyLevel === 'aggressive';
            }
        }

        if ($aggressiveMinify) {
            return self::aggressiveMinify($code);
        }

        return self::conservativeMinify($code);
    }

    /**
     * Minificación agresiva - elimina TODO excepto lo esencial para la ejecución.
     */
    private static function aggressiveMinify(string $code): string
    {
        // Eliminar TODOS los comentarios de línea
        $code = preg_replace('/\/\/.*$/m', '', $code);

        // Eliminar TODOS los comentarios multilínea
        $code = preg_replace('/\/\*.*?\*\//s', '', $code);

        // Eliminar líneas vacías y espacios en blanco excesivos
        $code = preg_replace('/\s*\n\s*/', "\n", $code);

        // Procesar línea por línea para minificación agresiva
        $lines = explode("\n", $code);
        $minifiedLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Omitir líneas vacías
            if ($trimmedLine === '' || $trimmedLine === '0') {
                continue;
            }

            // Minificar cada línea agresivamente
            $minifiedLine = self::aggressiveMinifyLine($trimmedLine);
            if ($minifiedLine !== '' && $minifiedLine !== '0') {
                $minifiedLines[] = $minifiedLine;
            }
        }

        // Unir todo en una sola línea o líneas mínimas
        $result = implode('', $minifiedLines);

        // Header ultra-minificado
        $header = '<?php declare(strict_types=1);';

        // Remover header original
        $result = preg_replace('/^<\?php.*?declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/s', '', $result);

        return $header . $result;
    }

    /**
     * Minificación conservadora - mantiene comentarios importantes.
     */
    private static function conservativeMinify(string $code): string
    {
        // Remover comentarios de una línea que no sean importantes para la ejecución
        $code = preg_replace('/\/\/ (?!===).*$/m', '', $code);

        // Remover comentarios multilínea excepto los que contienen información crítica
        $code = preg_replace('/\/\*(?!\*\/|.*?===).*?\*\//s', '', $code);

        // Preservar comentarios de sección importantes (=== Rutas de ... ===)
        // Estos ya están manejados por las expresiones regulares anteriores

        // Remover líneas vacías múltiples, dejando máximo una línea vacía
        $code = preg_replace('/\n\s*\n\s*\n/', "\n\n", $code);

        // Remover espacios en blanco al final de las líneas
        $code = preg_replace('/[ \t]+$/m', '', $code);

        // Remover espacios múltiples entre tokens (excepto en strings)
        $lines = explode("\n", $code);
        $minifiedLines = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Preservar líneas vacías importantes y comentarios de sección
            if ($trimmedLine === '' || $trimmedLine === '0' || str_starts_with($trimmedLine, '// ===')) {
                $minifiedLines[] = $trimmedLine;

                continue;
            }

            // Minificar espacios en líneas de código
            $minifiedLine = self::minifyCodeLine($trimmedLine);
            $minifiedLines[] = $minifiedLine;
        }

        // Unir las líneas y limpiar espacios finales
        $code = implode("\n", $minifiedLines);

        // Remover líneas vacías al inicio y final
        $code = trim($code);

        // Agregar header minificado
        $header = "<?php\n";
        $header .= '// Rutas compiladas y minificadas - ' . date('Y-m-d H:i:s') . "\n";
        $header .= "declare(strict_types=1);\n";

        // Remover el header original y reemplazarlo
        $code = preg_replace('/^<\?php.*?declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*/s', '', $code);

        return $header . $code;
    }

    /**
     * Minificación agresiva de una línea individual.
     */
    private static function aggressiveMinifyLine(string $line): string
    {
        // Preservar strings completos
        $tokens = token_get_all('<?php ' . $line);
        $result = '';

        foreach ($tokens as $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_OPEN_TAG:
                        // Omitir el tag de apertura que agregamos temporalmente
                        break;
                    case T_WHITESPACE:
                        // Agregar espacio solo donde sea absolutamente necesario
                        if (
                            $result !== ''
                            && $result !== '0'
                            && !in_array(
                                substr($result, -1),
                                [
                                    '{',
                                    '}',
                                    '(',
                                    ')',
                                    '[',
                                    ']',
                                    ',',
                                    ';',
                                    '=',
                                    '.',
                                    '-',
                                    '+',
                                    '*',
                                    '/',
                                    '%',
                                    '!',
                                    '<',
                                    '>',
                                    '&',
                                    '|',
                                    '^',
                                    '~',
                                    '?',
                                    ':',
                                ],
                                strict: true,
                            )
                        ) {
                            $result .= ' ';
                        }

                        break;
                    case T_COMMENT:
                    case T_DOC_COMMENT:
                        // Eliminar todos los comentarios
                        break;
                    default:
                        $result .= $token[1];
                        break;
                }
            } else {
                $result .= $token;
            }
        }

        return trim($result);
    }

    /**
     * Minifica una línea individual de código PHP.
     */
    private static function minifyCodeLine(string $line): string
    {
        // No minificar comentarios de sección importantes
        if (str_starts_with($line, '// ===')) {
            return $line;
        }

        // Preservar strings y no minificar su contenido
        $inString = false;
        $stringChar = '';
        $result = '';
        $i = 0;
        $length = strlen($line);

        while ($i < $length) {
            $char = $line[$i];

            // Detectar inicio/fin de string
            if (!$inString && ($char === '"' || $char === "'")) {
                $inString = true;
                $stringChar = $char;
                $result .= $char;
            } elseif ($inString && $char === $stringChar && $line[$i - 1] !== '\\') {
                $inString = false;
                $stringChar = '';
                $result .= $char;
            } elseif ($inString) {
                // Dentro de un string, preservar todo tal como está
                $result .= $char;
            } elseif ($char === ' ' || $char === "\t") {
                // Fuera de strings, minificar espacios
                // Agregar espacio solo si es necesario (entre palabras/tokens)
                if (
                    $result !== ''
                    && $result !== '0'
                    && !in_array(
                        substr($result, -1),
                        [
                            ' ',
                            '(',
                            '[',
                            '{',
                            ',',
                            ';',
                            '=',
                            '.',
                            '-',
                            '+',
                            '*',
                            '/',
                            '%',
                            '!',
                            '<',
                            '>',
                            '&',
                            '|',
                            '^',
                            '~',
                            '?',
                            ':',
                        ],
                        strict: true,
                    )
                ) {
                    $nextChar = ($i + 1) < $length ? $line[$i + 1] : '';
                    if (!in_array(
                        $nextChar,
                        [
                            '(',
                            ')',
                            '[',
                            ']',
                            '{',
                            '}',
                            ',',
                            ';',
                            '=',
                            '.',
                            '-',
                            '+',
                            '*',
                            '/',
                            '%',
                            '!',
                            '<',
                            '>',
                            '&',
                            '|',
                            '^',
                            '~',
                            '?',
                            ':',
                            ' ',
                            "\t",
                        ],
                        strict: true,
                    )) {
                        $result .= ' ';
                    }
                }
            } else {
                $result .= $char;
            }

            $i++;
        }

        return $result;
    }
}
