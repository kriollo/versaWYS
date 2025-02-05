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
    protected static array $getRoutes = [];
    protected static array $postRoutes = [];
    protected static array $putRoutes = [];
    protected static array $patchRoutes = [];
    protected static array $deleteRoutes = [];
    protected static array $middlewares = [];
    protected string $lastRoute = '';

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
    public static function get(string $route, mixed $callback): Router
    {
        self::$getRoutes[$route] = $callback;
        $instance = new static();
        $instance->lastRoute = $route;
        return $instance;
    }

    /**
     * Registers a POST route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    public static function post(string $route, mixed $callback): Router
    {
        self::$postRoutes[$route] = $callback;
        $instance = new static();
        $instance->lastRoute = $route;
        return $instance;
    }

    /**
     * Registers a PUT route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    public static function put(string $route, mixed $callback): Router
    {
        self::$putRoutes[$route] = $callback;
        $instance = new static();
        $instance->lastRoute = $route;
        return $instance;
    }

    /**
     * Registers a PATCH route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    public static function patch(string $route, mixed $callback): Router
    {
        self::$patchRoutes[$route] = $callback;
        $instance = new static();
        $instance->lastRoute = $route;
        return $instance;
    }

    /**
     * Registers a DELETE route with the specified route and callback.
     *
     * @param string $route The route pattern.
     * @param mixed $callback The callback function or method to be executed.
     * @return Router The Router instance.
     */
    public static function delete(string $route, mixed $callback): Router
    {
        self::$deleteRoutes[$route] = $callback;
        $instance = new static();
        $instance->lastRoute = $route;
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
        foreach ($middlewares as $middleware) {
            self::$middlewares[$this->lastRoute][] = $middleware;
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
        global $config;

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
            Response::jsonError(
                [
                    'success' => 0,
                    'message' => 'Internal Server Error',
                ],
                500
            );
        }
    }

    /**
     * Resolves the request URL and finds the appropriate route callback to execute.
     *
     * @return mixed The result of executing the route callback.
     */
    public function resolve(): mixed
    {
        global $request, $config;
        try {
            $url = self::normalize_url($request->getUrl());
            $method = strtolower($request->getMethod());

            // Si es un archivo no se ejecuta el router
            if (
                preg_match('/\.(js|css|jpg|jpeg|png|gif|svg|pdf|json|csv|xlsx)$/', $url) ||
                str_starts_with($url, 'blob:') ||
                str_starts_with($url, 'data:')
            ) {
                return null;
            }

            $routes = self::${$method . 'Routes'} ?? [];
            foreach ($routes as $route => $callback) {
                $originalRoute = $route;
                if (str_contains($route, '{')) {
                    $route = preg_replace('#{[a-zA-Z0-9]+}#', '([a-zA-Z0-9]+)', $route);
                }

                if (preg_match("#^$route$#", $url, $matches)) {
                    $slug = array_slice($matches, 1);

                    if (isset(self::$middlewares[$originalRoute])) {
                        foreach (self::$middlewares[$originalRoute] as $middleware) {
                            [$middlewareClass, $method] = $middleware;
                            $response = (new $middlewareClass())->$method(...$slug);

                            if (is_array($response) || is_object($response)) {
                                return Response::json($response, $response['code'] ?? 200);
                            }
                        }
                    }

                    [$controllerClass, $method] = $callback;
                    $response = (new $controllerClass())->$method(...$slug);

                    if (is_array($response) || is_object($response)) {
                        return Response::json($response);
                    }

                    echo $response;
                    return true;
                }
            }

            // Manejo de ruta no encontrada
            if (!$request->isApiCall()) {
                $template404 = '/e404';
                if ($config['build']['debug']) {
                    Response::jsonError(
                        [
                            'success' => 0,
                            'message' => "Ruta no encontrada -> {$request->getMethod()}::{$request->getUrl()}",
                        ],
                        404
                    );
                } else {
                    Response::redirect($template404);
                }
            } else {
                if ($config['build']['debug']) {
                    Response::jsonError(
                        [
                            'success' => 0,
                            'message' => "Ruta no encontrada -> {$request->getMethod()}::{$request->getUrl()}",
                        ],
                        404
                    );
                } else {
                    throw new Exception('Error Processing Interno', 1);
                }
            }
        } catch (Exception $e) {
            self::catch($e);
        } catch (Throwable $th) {
            self::catch($th);
        }
        return false;
    }
}
