<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Error;
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

    /** @var array<string, array<string, true>> Rutas que renuncian a la validación CSRF automática */
    protected static array $csrfExempt = [];

    protected string $lastRoute = '';

    protected string $lastRouteMethod = '';

    /**
     * Normalizes a URL by removing leading and trailing slashes and extracting the path.
     * Ahora incluye sanitización de seguridad contra inyección SQL.
     *
     * @param string $url the URL to be normalized
     *
     * @return string the normalized URL path
     */
    public static function normalize_url(string $url): string
    {
        // Aplicar sanitización de seguridad
        return SecurityUtils::validateAndCleanUrl($url);
    }

    /**
     * Agrupa rutas con un prefijo y/o middlewares compartidos.
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
    public static function get(string $route, mixed $callback, array $middlewares = []): self
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$getRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if ($allMiddlewares !== []) {
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
     * @param string $route the route pattern
     * @param mixed $callback the callback function or method to be executed
     *
     * @return Router the Router instance
     */
    public static function post(string $route, mixed $callback, array $middlewares = []): self
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$postRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if ($allMiddlewares !== []) {
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
     * @param string $route the route pattern
     * @param mixed $callback the callback function or method to be executed
     *
     * @return Router the Router instance
     */
    public static function put(string $route, mixed $callback, array $middlewares = []): self
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$putRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if ($allMiddlewares !== []) {
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
     * @param string $route the route pattern
     * @param mixed $callback the callback function or method to be executed
     *
     * @return Router the Router instance
     */
    public static function patch(string $route, mixed $callback, array $middlewares = []): self
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$patchRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if ($allMiddlewares !== []) {
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
     * @param string $route the route pattern
     * @param mixed $callback the callback function or method to be executed
     *
     * @return Router the Router instance
     */
    public static function delete(string $route, mixed $callback, array $middlewares = []): self
    {
        $fullRoute = implode('', self::$groupPrefixes) . $route;
        self::$deleteRoutes[$fullRoute] = $callback;
        $allMiddlewares = self::mergeMiddlewares($middlewares);
        if ($allMiddlewares !== []) {
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
     * @param array $middlewares the array of middleware classes and methods
     *
     * @return Router the Router instance
     */
    /**
     * Excluye la ruta de la validación CSRF automática. Solo para endpoints que autentican la
     * petición por otro medio (webhooks firmados, APIs con token propio).
     */
    public function withoutCsrf(): static
    {
        self::$csrfExempt[$this->lastRouteMethod][$this->lastRoute] = true;

        return $this;
    }

    /**
     * Middlewares de una ruta. Toda ruta que modifica estado (POST, PUT, PATCH, DELETE) valida
     * primero el token CSRF, salvo que lo declare ella misma o use withoutCsrf().
     *
     * @return array<int, array{0: class-string, 1: string}>
     */
    private function routeMiddlewares(string $method, string $route): array
    {
        $middlewares = self::$middlewares[$method][$route] ?? [];

        if ($method === 'get' || isset(self::$csrfExempt[$method][$route])) {
            return $middlewares;
        }

        foreach ($middlewares as $middleware) {
            if (($middleware[1] ?? '') === 'validateCSRFToken') {
                return $middlewares;
            }
        }

        return [[GlobalMiddleware::class, 'validateCSRFToken'], ...$middlewares];
    }

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
     * Resolves the request URL and finds the appropriate route callback to execute.
     *
     * @return mixed the result of executing the route callback
     */
    public function resolve(): mixed
    {
        // Control de tiempo máximo de ejecución para prevenir bucles infinitos
        $startTime = microtime(true);
        $maxExecutionTime = 5; // 5 segundos máximo

        global $request, $config;

        try {
            // 🔒 VALIDAR MÉTODOS HTTP PERMITIDOS PRIMERO
            $method = strtolower($request->getMethod());
            $allowedMethods = ['get', 'post', 'put', 'patch', 'delete', 'options', 'head'];

            if (!in_array($method, $allowedMethods, true)) {
                // Método no permitido, responder 405
                http_response_code(405);
                header('Allow: ' . implode(', ', array_map('strtoupper', $allowedMethods)));

                return Response::jsonError([
                    'success' => 0,
                    'message' => 'Method Not Allowed',
                    'allowed_methods' => array_map('strtoupper', $allowedMethods),
                ], 405);
            }

            // �🚨 RATE LIMITING GLOBAL - Aplicar antes de procesar rutas
            if ($this->shouldApplyGlobalRateLimit($request)) {
                $rateLimitResult = SecurityUtils::checkGlobalRateLimit($request);
                if ($rateLimitResult !== true) {
                    return Response::jsonError($rateLimitResult, 429);
                }
            }

            $url = self::normalize_url($request->getUrl());

            $customAdminUrl = $config['admin']['custom_url'] ?? 'admin';
            $hideDefault = $config['admin']['hide_default'] ?? false;

            // Bloquear acceso a /admin/ si está oculto
            if ($hideDefault && (str_starts_with($url, '/admin/') || $url === '/admin')) {
                return $this->respondNotFound($request, $config, 'Página no encontrada');
            }

            // Mapear URL personalizada a admin
            if (str_starts_with($url, sprintf('/%s/', $customAdminUrl))) {
                $url = str_replace(sprintf('/%s/', $customAdminUrl), '/admin/', $url);
            } elseif ($url === '/' . $customAdminUrl) {
                $url = '/admin';
            }

            // Si es un archivo no se ejecuta el router
            if (
                preg_match(
                    '/\.(js|css|jpg|jpeg|png|gif|svg|pdf|json|csv|xlsx|ico|woff|woff2|ttf|otf|webp|avif)$/',
                    $url,
                )
                || str_starts_with($url, 'blob:')
                || str_starts_with($url, 'data:')
            ) {
                // Un archivo estático que llegó hasta aquí no existe o está protegido: nunca 200 vacío
                http_response_code(404);

                return null;
            }

            // Las peticiones HEAD deben usar las rutas GET (estándar HTTP)
            // HEAD es idéntico a GET pero sin cuerpo de respuesta
            $routeMethod = $method === 'head' ? 'get' : $method;
            $routes = self::${$routeMethod . 'Routes'} ?? [];

            // === OPTIMIZACIÓN: Validación rápida de prefijos ===
            // Extraer todos los prefijos de las rutas registradas (incluyendo rutas parametrizadas)
            $prefixes = [];
            foreach (array_keys($routes) as $routePattern) {
                // Extraer el primer segmento de cada ruta (después del slash inicial)
                $parts = explode('/', ltrim($routePattern, '/'));
                if (isset($parts[0]) && ($parts[0] !== '' && $parts[0] !== '0')) {
                    $prefixes[] = $parts[0];
                }
            }

            $prefixes = array_unique($prefixes);

            // Extraer el primer segmento de la URL solicitada
            $urlParts = explode('/', ltrim($url, '/'));
            $urlPrefix = $urlParts[0] ?? '';

            // Si la URL no inicia con ningún prefijo registrado, retornar 404 inmediatamente
            if (!in_array($urlPrefix, $prefixes, true) && ($urlPrefix !== '' && $urlPrefix !== '0')) {
                return $this->respondNotFound($request, $config);
            }

            // Optimización: primero intentar coincidencia exacta (más rápido)
            // no rutas dinamicas
            if (isset($routes[$url])) {
                $callback = $routes[$url];
                $originalRoute = $url;
                // Ejecutar middlewares y controlador de forma centralizada
                $result = null;
                $routeMiddlewares = $this->routeMiddlewares($routeMethod, $originalRoute);
                if ($routeMiddlewares !== []) {
                    $result = $this->executeMiddlewares($routeMiddlewares, []);
                }

                if ($result !== null) {
                    return $result;
                }

                return $this->executeController($callback, []);
            }

            // Si no hay coincidencia exacta, buscar rutas con parámetros
            // Precalcular cantidad de segmentos de la URL solicitada
            $urlSegmentCount = count(array_filter(explode('/', trim($url, '/'))));
            foreach ($routes as $route => $callback) {
                // Verificar tiempo de ejecución excesivo
                if ((microtime(true) - $startTime) > $maxExecutionTime) {
                    return Response::jsonError([
                        'success' => 0,
                        'message' => 'Tiempo de ejecución excedido',
                    ], 500);
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
                    // Permitir guiones en nombres de parámetros y valores
                    $route = preg_replace('#{[a-zA-Z0-9_]+}#', '([a-zA-Z0-9_\-]+)', $route);
                } catch (Throwable $e) {
                    if (!empty($config['build']['debug'])) {
                        Loggin::error('Error al procesar patrón de ruta: ' . $e->getMessage(), 'router', [
                            'timestamp' => date('Y-m-d H:i:s'),
                            'request_uri' => $request->getUrl(),
                            'request_method' => $request->getMethod(),
                            'client_ip' => $request->getIp(),
                        ]);
                    }

                    continue; // Saltar esta ruta si hay un error
                }

                if (preg_match(sprintf('#^%s$#', $route), $url, $matches)) {
                    $slug = array_slice($matches, 1);

                    // Ejecutar middlewares y controlador de forma centralizada para rutas parametrizadas
                    $result = null;
                    $routeMiddlewares = $this->routeMiddlewares($routeMethod, $originalRoute);
                    if ($routeMiddlewares !== []) {
                        $result = $this->executeMiddlewares($routeMiddlewares, $slug);
                    }

                    if ($result !== null) {
                        return $result;
                    }

                    return $this->executeController($callback, $slug);
                }
            }

            // Manejo de ruta no encontrada - pero primero verificar si la ruta existe con otro método
            return $this->handleRouteNotFound($request, $config, $url, $method);
        } catch (Exception $e) {
            self::catch($e);
        } catch (Throwable $th) {
            self::catch($th);
        }

        return false;
    }

    /**
     * Registers a GET route with the specified route and callback.
     *
     * @param string $route the route pattern
     * @param mixed $callback the callback function or method to be executed
     *
     * @return Router the Router instance
     */
    /**
     * Combina los middlewares de grupo y de ruta de forma robusta.
     */
    private static function mergeMiddlewares(array $middlewares = []): array
    {
        $mergeArrays = array_merge(self::$groupMiddlewares ?: [], [is_array($middlewares) ? $middlewares : []]);

        return array_merge(...$mergeArrays);
    }

    /**
     * Handles the catching and handling of exceptions in the Router class.
     *
     * @param Exception|Throwable $e the exception to be caught and handled
     */
    private function catch(Exception|Error $e): void
    {
        global $config, $request;

        if ($config['build']['debug']) {
            Response::jsonError([
                'success' => 0,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        } else {
            Loggin::error(
                'Error en el router: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine(),
                'router',
                [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'request_uri' => $request->getUrl(),
                    'request_method' => $request->getMethod(),
                    'client_ip' => $request->getIp(),
                ],
            );
            $this->respondNotFound($request, $config);
        }
    }

    /**
     * Ejecuta los middlewares de forma centralizada.
     */
    private function executeMiddlewares($middlewares, array $slug = []): bool|string|null
    {
        global $request, $config;
        foreach ($middlewares as $middleware) {
            try {
                [$middlewareClass, $middlewareMethodName] = $middleware;

                if (!class_exists($middlewareClass)) {
                    $errorMsg = sprintf("Middleware class '%s' no encontrada", $middlewareClass);

                    throw new Exception($errorMsg);
                }

                $middlewareClassTemp = new $middlewareClass();
                if (!method_exists($middlewareClassTemp, $middlewareMethodName)) {
                    $errorMsg = sprintf(
                        "Método '%s' no encontrado en middleware '%s'",
                        $middlewareMethodName,
                        $middlewareClass,
                    );

                    throw new Exception($errorMsg);
                }

                $response = $middlewareClassTemp->$middlewareMethodName(...$slug);
                if (is_array($response) || is_object($response)) {
                    $status = is_array($response) ? (int) ($response['code'] ?? 200) : 200;

                    return Response::json((array) $response, $status >= 100 && $status <= 599 ? $status : 200);
                }
            } catch (Throwable $e) {
                Loggin::error(
                    'Error en middleware: '
                    . $e->getMessage()
                    . ' en '
                    . ($middlewareClass ?? 'desconocido')
                    . '::'
                    . ($middlewareMethodName ?? 'desconocido'),
                    'router',
                    [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'request_uri' => $request->getUrl(),
                        'request_method' => $request->getMethod(),
                        'client_ip' => $request->getIp(),
                    ],
                );

                // MEJORA: En modo debug, dejar que la excepción se propague al ErrorHandler global
                if (isset($config['build']['debug']) && $config['build']['debug']) {
                    throw $e; // Re-lanzar la excepción para ErrorHandler detallado
                }

                // Un fallo interno en una llamada API es un 500, no una ruta inexistente
                if ($request->isApiCall()) {
                    return Response::json(['success' => 0, 'message' => 'Error interno'], 500);
                }

                // En modo producción, usar respuesta limpia
                return $this->respondNotFound($request, $config, 'Error interno del middleware');
            }
        }

        return null;
    }

    /**
     * Ejecuta el controlador de forma centralizada.
     */
    private function executeController($callback, array $slug = []): bool|string
    {
        global $request, $config;

        try {
            [$controllerClass, $controllerMethodName] = $callback;
            $controllerClassTemp = new $controllerClass();
            if (!method_exists($controllerClassTemp, $controllerMethodName)) {
                throw new Exception(sprintf(
                    "Método '%s' no encontrado en controlador '%s'",
                    $controllerMethodName,
                    $controllerClass,
                ));
            }

            $response = $controllerClassTemp->$controllerMethodName(...$slug);
            if (is_array($response) || is_object($response)) {
                // Igual que en los middlewares: la clave `code` define el estado HTTP
                // Solo si es un estado HTTP válido: hay respuestas con code 0 o con el código de una excepción
                $status = is_array($response) ? (int) ($response['code'] ?? 200) : 200;

                return Response::json((array) $response, $status >= 100 && $status <= 599 ? $status : 200);
            }

            echo $response;

            return true;
        } catch (Throwable $throwable) {
            if (isset($controllerClass, $controllerMethodName)) {
                Loggin::error(
                    'Error en controlador: '
                    . $throwable->getMessage()
                    . ' en '
                    . $controllerClass
                    . '::'
                    . $controllerMethodName,
                    'router',
                    [
                        'timestamp' => date('Y-m-d H:i:s'),
                        'request_uri' => $request->getUrl(),
                        'request_method' => $request->getMethod(),
                        'client_ip' => $request->getIp(),
                    ],
                );
            } else {
                Loggin::error('Error en controlador: ' . $throwable->getMessage(), 'router', [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'request_uri' => $request->getUrl(),
                    'request_method' => $request->getMethod(),
                    'client_ip' => $request->getIp(),
                ]);
            }

            // MEJORA: En modo debug, dejar que la excepción se propague al ErrorHandler global
            // para obtener información más detallada (stack trace, contexto, etc.)
            if (isset($config['build']['debug']) && $config['build']['debug']) {
                throw $throwable; // Re-lanzar la excepción para que la capture ErrorHandler::handleException
            }

            // Un fallo interno en una llamada API es un 500, no una ruta inexistente
            if ($request->isApiCall()) {
                return Response::json(['success' => 0, 'message' => 'Error interno'], 500);
            }

            // En modo producción, usar respuesta limpia
            return $this->respondNotFound($request, $config, 'Error interno');
        }
    }

    /**
     * Centraliza la respuesta para rutas no encontradas o errores internos.
     */
    private function respondNotFound($request, array $config, string $e = 'ruta no encontrada'): bool|string
    {
        // Un 404 no es un fallo del sistema: nivel notice, sin traza (los escáneres generan miles)
        Loggin::notice('Ruta no encontrada: ' . $request->getUrl(), 'router', [
            'timestamp' => date('Y-m-d H:i:s'),
            'request_uri' => $request->getUrl(),
            'request_method' => $request->getMethod(),
            'client_ip' => $request->getIp(),
        ]);

        // Detectar peticiones SPA usando el método centralizado de Request
        $isSPARequest = Request::isSPARequestStatic();

        if (!$request->isApiCall()) {
            // En modo debug con petición SPA, devolver error en formato JSON SPA
            if ($config['build']['debug'] && $isSPARequest) {
                $errorMessage = sprintf('%s -> %s::%s', $e, $request->getMethod(), $request->getUrl());
                $errorHtml =
                    '<div class="flex flex-col justify-center items-center px-6 mx-auto h-screen xl:px-0">'
                    . '<div class="text-center xl:max-w-4xl">'
                    . '<h1 class="mb-3 text-2xl font-bold leading-tight text-red-600 sm:text-4xl lg:text-5xl">Error 404 - Debug Mode</h1>'
                    . '<pre class="mb-5 text-left bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-auto text-sm">'
                    . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8')
                    . '</pre>'
                    . '<div class="flex justify-center mt-8">'
                    . '<a onclick="history.go(-1)" data-spa class="px-8 py-3 bg-gray-600 text-white rounded-xl font-bold hover:bg-gray-700 transition-all cursor-pointer">'
                    . 'Volver</a>'
                    . '</div></div></div>';

                return Response::json([
                    'type' => 'html',
                    'content' => $errorHtml,
                    'scripts' => [],
                    'styles' => [],
                    'urlActual' => $request->getUrl(),
                ], 404);
            }

            // En modo debug sin SPA, mostrar error detallado tradicional
            if ($config['build']['debug']) {
                Response::jsonError([
                    'success' => 0,
                    'message' => sprintf('%s -> %s::%s', $e, $request->getMethod(), $request->getUrl()),
                ], 404);
            } elseif ($isSPARequest) {
                // Para peticiones SPA en producción, renderizar la página 404 como contenido JSON SPA
                $content404 = $this->render404TemplateForSPA();

                return Response::json([
                    'type' => 'html',
                    'content' => $content404,
                    'scripts' => [],
                    'styles' => [],
                    'urlActual' => $request->getUrl(),
                ], 404);
            } else {
                // Petición regular en producción: renderizar template 404
                try {
                    http_response_code(404);
                    $template404Content = $this->render404Template();
                    echo $template404Content;
                    exit();
                } catch (Throwable $th) {
                    // Si falla el render, log y mostrar fallback
                    Loggin::error('Error renderizando template 404: ' . $th->getMessage(), 'router');
                    http_response_code(404);
                    echo
                        '<!DOCTYPE html><html><head><title>404 - Página no encontrada</title></head><body><h1>404 - Página no encontrada</h1><p>La página que buscas no existe.</p></body></html>'
                    ;
                    exit();
                }
            }
        } else {
            if ($config['build']['debug']) {
                return Response::jsonError([
                    'success' => 0,
                    'message' => sprintf('%s -> %s::%s', $e, $request->getMethod(), $request->getUrl()),
                ], 404);
            }

            return Response::json([
                'success' => 0,
                'message' => 'Recurso no encontrado',
            ], 404);
        }

        return false;
    }

    /**
     * Determina si debe aplicar rate limiting global basado en la petición.
     */
    private function shouldApplyGlobalRateLimit($request): bool
    {
        $url = $request->getUrl();

        // Aplicar rate limiting global a:
        // - Todas las rutas que comienzan con /api/
        // - Rutas de login y autenticación
        // - Peticiones identificadas como API calls
        return (
            str_contains($url, '/api/')
            || str_ends_with($url, '/login')
            || str_ends_with($url, '/authentication')
            || $request->isApiCall()
        );
    }

    /**
     * Maneja rutas no encontradas, distinguiendo entre 404 (ruta no existe) y 405 (método no permitido).
     */
    private function handleRouteNotFound($request, array $config, string $url, string $method): bool|string
    {
        // Verificar si la ruta existe con otros métodos HTTP
        $allRoutes = array_merge(
            self::$getRoutes,
            self::$postRoutes,
            self::$putRoutes,
            self::$patchRoutes,
            self::$deleteRoutes,
        );

        $routeExists = false;
        $allowedMethods = [];

        // Verificar coincidencia exacta primero
        if (array_key_exists($url, $allRoutes)) {
            $routeExists = true;
        }

        // Si no hay coincidencia exacta, verificar rutas con parámetros
        if (!$routeExists) {
            foreach (array_keys($allRoutes) as $route) {
                if (!str_contains($route, '{')) {
                    continue;
                }

                $routePattern = preg_replace('#{[a-zA-Z0-9_]+}#', '([a-zA-Z0-9_\-]+)', $route);
                if (preg_match(sprintf('#^%s$#', $routePattern), $url)) {
                    $routeExists = true;
                    break;
                }
            }
        }

        // Si la ruta existe, determinar qué métodos están permitidos
        if ($routeExists) {
            foreach (['get', 'post', 'put', 'patch', 'delete'] as $httpMethod) {
                $routes = self::${$httpMethod . 'Routes'} ?? [];
                if (array_key_exists($url, $routes)) {
                    $allowedMethods[] = strtoupper($httpMethod);
                } else {
                    // Verificar rutas con parámetros
                    foreach (array_keys($routes) as $route) {
                        if (!str_contains($route, '{')) {
                            continue;
                        }

                        $routePattern = preg_replace('#{[a-zA-Z0-9_]+}#', '([a-zA-Z0-9_\-]+)', $route);
                        if (preg_match(sprintf('#^%s$#', $routePattern), $url)) {
                            $allowedMethods[] = strtoupper($httpMethod);
                            break;
                        }
                    }
                }
            }

            // Si la ruta existe pero el método no está permitido, responder 405
            if ($allowedMethods !== []) {
                http_response_code(405);
                header('Allow: ' . implode(', ', $allowedMethods));

                return Response::jsonError([
                    'success' => 0,
                    'message' => 'Method Not Allowed',
                    'allowed_methods' => $allowedMethods,
                ], 405);
            }
        }

        // Si llegamos aquí, la ruta no existe (404)
        return $this->respondNotFound($request, $config);
    }

    /**
     * Renderiza el template 404 sin redirección.
     */
    private function render404Template(): string
    {
        try {
            // Usar Twig para renderizar la página 404
            global $twig;

            return $twig->render('versaWYS/404');
        } catch (Throwable) {
            Loggin::error('No se pudo cargar Twig para la página 404.', 'router', [
                'timestamp' => date('Y-m-d H:i:s'),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);

            // Fallback si no se puede cargar Twig
            return '<!DOCTYPE html><html><head><title>404 - Página no encontrada</title></head><body><h1>404 - Página no encontrada</h1><p>La página que buscas no existe.</p></body></html>';
        }
    }

    /**
     * Renderiza el contenido del template 404 para respuestas SPA.
     * Devuelve solo el HTML del contenido interno (sin layout) ya que SPA
     * solo necesita el fragmento que se inyecta en #content-general.
     */
    private function render404TemplateForSPA(): string
    {
        global $twig;
        return $twig->render('versaWYS/404Content');
    }
}
