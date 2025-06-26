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

        echo "Creando ruta $routeName...\n";
        $routesFile = self::$path . "$routeName.php";

        if (file_exists($routesFile)) {
            echo "La ruta $routesFile ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen('php://stdin', 'r');
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";
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
use app\middleware\$middlewareName;

// Rutas de Navegador
// index => funcion que se ejecutara en el controlador
Router::get('/admin/$routeNameModule',
    [$controllerName::class,'index']
)->middleware([
    // [AuthMiddleware::class, 'checkSession'],
    // [AuthMiddleware::class, 'onlyUserWithAccess'],
    // [$middlewareName::class, 'middlewareMethod'],
]);

EOT;

        $template = str_replace('$routeNameModule', $routeNameModule, $template);
        $template = str_replace('$routeName', $routeName, $template);
        $template = str_replace('$controllerName', $controllerName, $template);
        $template = str_replace('$middlewareName', $middlewareName, $template);

        file_put_contents($routesFile, $template);
        echo "Ruta $routesFile creada.\n";
    }
    public static function deleteRoute(string $routeName): void
    {
        $routeName = ucfirst($routeName) . 'Routes';

        echo "Eliminando ruta $routeName...\n";
        $routesFile = self::$path . "$routeName.php";

        if (!file_exists($routesFile)) {
            echo "La ruta $routesFile no existe.\n";
            exit();
        }

        unlink($routesFile);
        echo "Ruta $routesFile eliminada.\n";
    }

    public static function getRoutes(): array
    {
        $routes = scandir(self::$path);
        $routes = array_filter($routes, function ($route) {
            return !in_array($route, ['.', '..']);
        });
        $routes = array_values($routes);

        return $routes;
    }

    public static function listRoutes(): void
    {
        $routes = self::getRoutes();

        echo "Rutas:\n";
        $n = 1;
        foreach ($routes as $route) {
            echo "$n - $route\n";
            $n++;
        }
        echo "\n";
        echo 'Seleccione una ruta: ';
        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        $line = trim($line);
        if (!is_numeric($line)) {
            echo "Saliendo...\n";
            exit();
        }
        $line = (int) $line;
        if ($line < 1 || $line > count($routes)) {
            echo "Saliendo...\n";
            exit();
        }

        $route = $routes[$line - 1];
        self::listarRutasFromRoute($route);
    }

    public static function getRoutesFromRouteFile($routeFile): array
    {
        $routeFile = self::$path . $routeFile;
        if (!file_exists($routeFile)) {
            echo "La ruta $routeFile no existe.\n";
            exit();
        }
        $routeContent = file_get_contents($routeFile);

        $pattern =
            '/Router::(\w+)\(\s*[\'"]([^\'"]+)[\'"](?:[^;]*\[\s*([^]]+),\s*([^]]+)])?(?:[^;]*\[\s*([^]]+),\s*([^]]+)])?\s*\)/s';
        $routeContent = preg_replace('/\s+/', ' ', $routeContent); // Eliminar saltos de línea y espacios en blanco adicionales
        preg_match_all($pattern, $routeContent, $matches, PREG_SET_ORDER);

        return $matches;
    }

    /**
     * Extrae datos de rutas de un archivo, incluyendo soporte para grupos anidados
     * @param string $routeFile Nombre del archivo de rutas
     * @return array Datos de las rutas
     */
    public static function extractRouteData(string $routeFile): array
    {
        $routeFile = self::$path . $routeFile;
        if (!file_exists($routeFile)) {
            echo "La ruta $routeFile no existe.\n";
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
            if (empty($line)) continue;

            // Capturar documentación
            if (strpos($line, '// description:') === 0) {
                $currentDescription = trim(substr($line, 15));
                continue;
            }

            // Capturar request-body
            if (strpos($line, '// request-body') === 0) {
                if (preg_match('/\/\/\s*request-body\s+name:(.+?)\s+type:(.+?)\s+description:(.+)$/', $line, $matches)) {
                    $currentRequestBody[] = [
                        'name' => trim($matches[1]),
                        'type' => trim($matches[2]),
                        'description' => trim($matches[3]),
                    ];
                }
                continue;
            }

            // Capturar query-param
            if (strpos($line, '// query-param') === 0) {
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
                    $nextLineIndex = array_search($line, $lines) + 1;
                    while ($nextLineIndex < count($lines)) {
                        $nextLine = trim($lines[$nextLineIndex]);
                        if (preg_match('/\},\s*\[(.*?)\]\s*\);/', $nextLine, $mwMatches)) {
                            $middlewareStr = $mwMatches[1];
                            $groupMiddleware = self::parseMiddleware($middlewareStr);
                            break;
                        }
                        if (strpos($nextLine, '});') === 0) {
                            break;
                        }
                        $nextLineIndex++;
                    }
                }

                // Guardar estado actual del grupo
                $groupStack[] = [
                    'prefix' => $groupPrefix,
                    'middleware' => $groupMiddleware,
                    'description' => $currentDescription
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
            if (strpos($line, '});') === 0 || strpos($line, '}, [') === 0) {
                // Quitar el último grupo de la pila
                if (!empty($groupStack)) {
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
            if (preg_match('/Router::(get|post|put|patch|delete)\s*\(\s*[\'"](.*?)[\'"](.*?)\[([^\]]+)\]\)/', $line, $matches)) {
                $method = strtoupper($matches[1]);
                $routePath = $matches[2];
                $callback = $matches[4];

                // Manejar rutas vacías dentro de grupos (como '')
                if (empty($routePath) && !empty($currentPath)) {
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
                if (empty($description) && !empty($groupStack)) {
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

    /**
     * Analiza una cadena de middleware y la convierte en un array estructurado
     * @param string $middlewareStr Cadena de middleware
     * @return array Array de middlewares
     */
    private static function parseMiddleware(string $middlewareStr): array
    {
        $middlewares = [];

        // Limpiar espacios y caracteres innecesarios
        $middlewareStr = preg_replace('/\s+/', '', $middlewareStr);

        // Manejar diferentes formatos de middleware
        if (strpos($middlewareStr, '[[') === 0) {
            // Formato [[Class,'method'],[Class,'method']]
            $items = explode('],[', substr($middlewareStr, 2, -2));
        } else if (strpos($middlewareStr, '[') === 0) {
            // Formato [Class,'method']
            $items = [substr($middlewareStr, 1, -1)];
        } else {
            // Otros formatos
            $items = explode('],[', $middlewareStr);
        }

        foreach ($items as $item) {
            if (empty($item)) continue;

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
}
