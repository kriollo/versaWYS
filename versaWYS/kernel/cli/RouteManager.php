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

    public static function extractRouteData(string $routeFile): array
    {
        $routeFile = self::$path . $routeFile;
        if (!file_exists($routeFile)) {
            echo "La ruta $routeFile no existe.\n";
            exit();
        }
        $routeContent = file_get_contents($routeFile);

        $allRoutesData = [];

        // Expresión regular corregida (captura la descripción correctamente)
        $pattern =
            '/(?:(?:\/\/\s*description:\s*(.*?)\n)?(?:(?:\/\/\s*request-body\s+name:(.+?)\s+type:(.+?)\s+description:(.+?)\n)+)?(?:(?:\/\/\s*query-param\s+name:(.+?)\s+type:(.+?)\s+description:(.+?)\n)+)?)*Router::(post|get|put|patch|delete)\(\'([^\']+)\',\s*\[([^\]]+)\]\)(?:\-\>middleware\(([^)]+)\))?;/s';

        if (preg_match_all($pattern, $routeContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $route = $match[9];
                $method = strtoupper($match[8]);
                preg_match_all('/\{([^\}]+)\}/', $route, $routeParamsMatches);
                $routeParams = [];
                foreach ($routeParamsMatches[1] as $param) {
                    $routeParams[] = $param;
                }
                $data = [
                    'route' => $route,
                    'method' => $method,
                    'routeParams' => $routeParams,
                    'description' => trim($match[1] ?? ''), // Extracción de la descripción
                    'requestBody' => [],
                    'queryParams' => [],
                    'class' => null,
                    'class_method' => null,
                    'middleware' => [],
                ];
                if (isset($match[10])) {
                    $classMethod = explode(',', str_replace(['[', ']', ' ', "'"], '', $match[10]));
                    $data['class'] = $classMethod[0] ?? null;
                    $data['class_method'] = $classMethod[1] ?? null;
                }
                // Extrae request bodies
                preg_match_all(
                    '/\/\/\s*request-body\s+name:(.+?)\s+type:(.+?)\s+description:(.+?)\n/',
                    $match[0],
                    $requestBodyMatches,
                    PREG_SET_ORDER
                );
                foreach ($requestBodyMatches as $requestBodyMatch) {
                    $data['requestBody'][] = [
                        'name' => trim($requestBodyMatch[1]),
                        'type' => trim($requestBodyMatch[2]),
                        'description' => trim($requestBodyMatch[3]),
                    ];
                }

                // Extrae query params
                preg_match_all(
                    '/\/\/\s*query-param\s+name:(.+?)\s+type:(.+?)\s+description:(.+?)\n/',
                    $match[0],
                    $queryParamsMatches,
                    PREG_SET_ORDER
                );
                foreach ($queryParamsMatches as $queryParamMatch) {
                    $data['queryParams'][] = [
                        'name' => trim($queryParamMatch[1]),
                        'type' => trim($queryParamMatch[2]),
                        'description' => trim($queryParamMatch[3]),
                    ];
                }
                if (isset($match[11])) {
                    $middlewareString = trim($match[11]);
                    if (strpos($middlewareString, '[[') === 0) {
                        $middlewareString = substr($middlewareString, 1, -1);
                    }
                    $middlewareItems = explode('],', $middlewareString);
                    foreach ($middlewareItems as $item) {
                        $item = str_replace(['[', ']', ' ', "'"], '', $item);
                        $middlewareParts = explode(',', $item);
                        if (count($middlewareParts) === 2) {
                            $data['middleware'][] = [
                                'class' => $middlewareParts[0],
                                'class_method' => $middlewareParts[1],
                            ];
                        }
                    }
                }

                $allRoutesData[] = $data;
            }
        }

        // dump($allRoutesData);

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
}
