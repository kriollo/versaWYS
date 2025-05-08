<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Exception;
use Throwable;

/**
 * The Router class handles routing and middleware functionality for the application.
 */
class Router
{
    // Stacks para prefijos y middlewares de grupo
    protected static array $groupPrefixes = [];
    protected static array $groupMiddlewares = [];
    protected static array $getRoutes = [];
    protected static array $postRoutes = [];
    protected static array $putRoutes = [];
    protected static array $patchRoutes = [];
    protected static array $deleteRoutes = [];
    protected static array $middlewares = []; // Estructura: $middlewares[method][route][]
    protected string $lastRoute = '';
    protected string $lastRouteMethod = '';

    /**
     * Normalizes a URL by removing leading and trailing slashes and extracting the path.
     *
     * @param string $url The URL to be normalized.
     * @return string The normalized URL path.
     */
    public static function normalize_url(string $url): string
    {
        $url = trim($url, '/');
        $url = parse_url($url, PHP_URL_PATH);
        return '/' . $url;
    }

    /**
     * Registers a GET route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    /**
     * Combina los middlewares de grupo y de ruta de forma robusta.
     */
    private static function mergeMiddlewares(array $middlewares = []): array
    {
        $mergeArrays = array_merge(
            self::$groupMiddlewares ?: [],
            [is_array($middlewares) ? $middlewares : []]
        );
        return array_merge(...$mergeArrays);
    }

    /**
     * Agrupa rutas con un prefijo y/o middlewares compartidos
     *
     * @param string $prefix Prefijo de ruta para el grupo
     * @param callable $callback Función que define las rutas del grupo
     * @param array $middlewares Middlewares compartidos para todas las rutas del grupo
     */
    public static function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        // Guardar el estado actual antes de modificarlo
        $currentGroupPrefixes = self::$groupPrefixes;
        $currentGroupMiddlewares = self::$groupMiddlewares;

        // Añadir el nuevo prefijo y middlewares
        self::$groupPrefixes[] = $prefix;
        self::$groupMiddlewares[] = $middlewares;

        // Ejecutar la función que define las rutas del grupo
        $callback();

        // Restaurar el estado anterior (desapilar)
        self::$groupPrefixes = $currentGroupPrefixes;
        self::$groupMiddlewares = $currentGroupMiddlewares;
    }

    /**
     * Registra una ruta GET, soportando prefijos y middlewares de grupo y ruta.
     */
    public static function get(string $route, mixed $callback, array $middlewares = []): Router
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$getRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if (!empty($allMiddlewares)) {
            self::$middlewares['get'][$fullRoute] = $allMiddlewares;
        }
        $instance = new static();
        $instance->lastRoute = $fullRoute;
        $instance->lastRouteMethod = 'get';
        return $instance;
    }

    /**
     * Registers a POST route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    public static function post(string $route, mixed $callback, array $middlewares = []): Router
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$postRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if (!empty($allMiddlewares)) {
            self::$middlewares['post'][$fullRoute] = $allMiddlewares;
        }
        $instance = new static();
        $instance->lastRoute = $fullRoute;
        $instance->lastRouteMethod = 'post';
        return $instance;
    }

    /**
     * Registers a PUT route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    public static function put(string $route, mixed $callback, array $middlewares = []): Router
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$putRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if (!empty($allMiddlewares)) {
            self::$middlewares['put'][$fullRoute] = $allMiddlewares;
        }
        $instance = new static();
        $instance->lastRoute = $fullRoute;
        $instance->lastRouteMethod = 'put';
        return $instance;
    }

    /**
     * Registers a PATCH route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    public static function patch(string $route, mixed $callback, array $middlewares = []): Router
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$patchRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if (!empty($allMiddlewares)) {
            self::$middlewares['patch'][$fullRoute] = $allMiddlewares;
        }
        $instance = new static();
        $instance->lastRoute = $fullRoute;
        $instance->lastRouteMethod = 'patch';
        return $instance;
    }

    /**
     * Registers a DELETE route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    public static function delete(string $route, mixed $callback, array $middlewares = []): Router
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$deleteRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if (!empty($allMiddlewares)) {
            self::$middlewares['delete'][$fullRoute] = $allMiddlewares;
        }
        $instance = new static();
        $instance->lastRoute = $fullRoute;
        $instance->lastRouteMethod = 'delete';
        return $instance;
    }

    /**
     * Adds middleware to be executed for the last registered route.
     *
     * @param array $middlewares The array of middleware classes and methods.
     * @return Router The Router instance.
     */
    public function middleware(array $middlewares): static
    {
        $route = $this->lastRoute;
        $method = $this->lastRouteMethod;
        if ($route && $method) {
            // Combina con los middlewares de grupo si existen
            $allMiddlewares = self::mergeMiddlewares($middlewares);
            self::$middlewares[$method][$route] = $allMiddlewares;
        }
        return $this;
    }

    /**
     * Handles the catching and handling of exceptions in the Router class.
     *
     * @param Exception|Throwable $e The exception to be caught and handled.
     * @return void
     */
    private function catch($e): void
    {
        global $config, $request;

        if ($config['build']['debug']) {
            Response::jsonError(
                [
                    'success' => 0,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ],
                500
            );
        } else {
            error_log('Error en el router: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            $this->respondNotFound($request, $config);
        }
    }

    /**
     * Resolves the request URL and finds the appropriate route callback to execute.
     *
     * @return mixed The result of executing the route callback.
     */
    public function resolve(): mixed
    {
        // Control de tiempo máximo de ejecución para prevenir bucles infinitos
        $startTime = microtime(true);
        $maxExecutionTime = 5; // 5 segundos máximo

        global $request, $config;
        try {
            $url = self::normalize_url($request->getUrl());
            $method = strtolower($request->getMethod());

            // Si es un archivo no se ejecuta el router
            if (
                preg_match('/\.(js|css|jpg|jpeg|png|gif|svg|pdf|json|csv|xlsx|ico|woff|woff2|ttf|otf|webp|avif)$/', $url) ||
                str_starts_with($url, 'blob:') ||
                str_starts_with($url, 'data:')
            ) {
                return null;
            }

            $routes = self::${$method . 'Routes'} ?? [];

            // === OPTIMIZACIÓN: Validación rápida de prefijos ===
            // Extraer todos los prefijos de las rutas registradas (incluyendo rutas parametrizadas)
            $prefixes = [];
            foreach (array_keys($routes) as $routePattern) {
                // Extraer el primer segmento de cada ruta (después del slash inicial)
                $parts = explode('/', ltrim($routePattern, '/'));
                if (!empty($parts[0])) {
                    $prefixes[] = $parts[0];
                }
            }
            $prefixes = array_unique($prefixes);

            // Extraer el primer segmento de la URL solicitada
            $urlParts = explode('/', ltrim($url, '/'));
            $urlPrefix = $urlParts[0] ?? '';

            // Si la URL no inicia con ningún prefijo registrado, retornar 404 inmediatamente
            if (!in_array($urlPrefix, $prefixes, true) && !empty($urlPrefix)) {
                return $this->respondNotFound($request, $config);
            }


            // Optimización: primero intentar coincidencia exacta (más rápido)
            // no rutas dinamicas
            if (isset($routes[$url])) {
                $callback = $routes[$url];
                $originalRoute = $url;
                // Ejecutar middlewares y controlador de forma centralizada
                $result = null;
                if (isset(self::$middlewares[$method][$originalRoute])) {
                    $result = $this->executeMiddlewares(self::$middlewares[$method][$originalRoute], []);
                }
                if ($result !== null) return $result;
                return $this->executeController($callback, []);
            }

            // Si no hay coincidencia exacta, buscar rutas con parámetros
            // Precalcular cantidad de segmentos de la URL solicitada
            $urlSegmentCount = count(array_filter(explode('/', trim($url, '/'))));
            foreach ($routes as $route => $callback) {
                // Verificar tiempo de ejecución excesivo
                if (microtime(true) - $startTime > $maxExecutionTime) {
                    return Response::jsonError(['success' => 0, 'message' => 'Tiempo de ejecución excedido'], 500);
                }

                // Saltar rutas sin parámetros (ya verificamos coincidencia exacta)
                if (!str_contains($route, '{')) {
                    continue;
                }

                // Optimización: solo intentar match si el número de segmentos coincide
                $routeSegmentCount = count(array_filter(explode('/', trim($route, '/'))));
                if ($routeSegmentCount !== $urlSegmentCount) {
                    continue;
                }

                $originalRoute = $route;
                try {
                    $route = preg_replace('#{[a-zA-Z0-9]+}#', '([a-zA-Z0-9]+)', $route);
                } catch (Throwable $e) {
                    if (!empty($config['build']['debug'])) {
                        error_log('Error al procesar patrón de ruta: ' . $e->getMessage());
                    }
                    continue; // Saltar esta ruta si hay un error
                }

                if (preg_match("#^$route$#", $url, $matches)) {
                    $slug = array_slice($matches, 1);

                    // Ejecutar middlewares y controlador de forma centralizada para rutas parametrizadas
                    $result = null;
                    if (isset(self::$middlewares[$method][$originalRoute])) {
                        $result = $this->executeMiddlewares(self::$middlewares[$method][$originalRoute], $slug);
                    }
                    if ($result !== null) return $result;
                    return $this->executeController($callback, $slug);
                }
            }

            // Manejo de ruta no encontrada
            return $this->respondNotFound($request, $config);
        } catch (Exception $e) {
            self::catch($e);
        } catch (Throwable $th) {
            self::catch($th);
        }
        return false;
    }

    /**
     * Ejecuta los middlewares de forma centralizada
     */
    private function executeMiddlewares($middlewares, $slug = [])
    {
        global $request, $config;
        foreach ($middlewares as $middleware) {
            try {
                [$middlewareClass, $middlewareMethodName] = $middleware;
                $response = (new $middlewareClass())->$middlewareMethodName(...$slug);
                if (is_array($response) || is_object($response)) {
                    return Response::json($response, $response['code'] ?? 200);
                }
            } catch (Throwable $e) {
                error_log('Error en middleware: ' . $e->getMessage() . ' en ' . $middlewareClass . '::' . $middlewareMethodName);
                return $this->respondNotFound($request, $config, $e->getMessage());
            }
        }
        return null;
    }

    /**
     * Ejecuta el controlador de forma centralizada
     */
    private function executeController($callback, $slug = [])
    {
        global $request, $config;
        try {
            [$controllerClass, $controllerMethodName] = $callback;
            $response = (new $controllerClass())->$controllerMethodName(...$slug);
            if (is_array($response) || is_object($response)) {
                return Response::json($response);
            }
            echo $response;
            return true;
        } catch (Throwable $e) {
            if (isset($controllerClass) && isset($controllerMethodName)) {
                error_log('Error en controlador: ' . $e->getMessage() . ' en ' . $controllerClass . '::' . $controllerMethodName);
            } else {
                error_log('Error en controlador: ' . $e->getMessage());
            }
            return $this->respondNotFound($request, $config, $e->getMessage());
        }
    }

    /**
     * Centraliza la respuesta para rutas no encontradas o errores internos
     */
    private function respondNotFound($request, $config, $e = "ruta no encontrada")
    {
        $template404 = '/e404';
        if (!$request->isApiCall()) {
            if ($config['build']['debug']) {
                Response::jsonError(
                    [
                        'success' => 0,
                        'message' => "$e -> {$request->getMethod()}::{$request->getUrl()}",
                    ],
                    404
                );
            } else {
                Response::redirect($template404);
            }
        } else {
            if ($config['build']['debug']) {
                return Response::jsonError(
                    [
                        'success' => 0,
                        'message' => "$e -> {$request->getMethod()}::{$request->getUrl()}",
                    ],
                    404
                );
            } else {
                return Response::json(
                    [
                        'success' => 0,
                        'message' => "Error interno",
                    ],
                    500
                );
            }
        }
        return false;
    }
}
