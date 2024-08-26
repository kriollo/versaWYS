<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class RouteManager
{
    private static string $path = 'app/Routes/';
    public static function createRoute(string $routeNameModule): void
    {
        $controllerName = ucfirst($routeNameModule) . 'Controller';
        $routeName = ucfirst($routeNameModule) . 'Routes';
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

namespace app\Routes;

use versaWYS\kernel\Router;
use app\middleware\AuthMiddleware;
use app\middleware\$middlewareName;

Router::get('/admin/$routeName',
    [\app\controllers\$controllerName::class,'index']
);

EOT;

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

    public static function listRoutes(): void
    {
        $routes = scandir(self::$path);
        $routes = array_filter($routes, function ($route) {
            return !in_array($route, ['.', '..']);
        });
        $routes = array_values($routes);

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

    public static function listarRutasFromRoute($routeFile): void
    {
        $routeFile = self::$path . $routeFile;
        if (!file_exists($routeFile)) {
            echo "La ruta $routeFile no existe.\n";
            exit();
        }
        $routeContent = file_get_contents($routeFile);

        $pattern =
            '/Router::(\w+)\(\s*[\'"]([^\'"]+)[\'"](?:[^;]*\[\s*([^]]+),\s*([^]]+)])?(?:[^;]*\[\s*([^]]+),\s*([^]]+)])?\s*\)/s';
        preg_match_all($pattern, $routeContent, $matches, PREG_SET_ORDER);

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
