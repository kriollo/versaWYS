<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use versaWYS\kernel\cli\RouteManager;

class versaController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    /**
     * Documentación interactiva de rutas (/doc). Sin `route` muestra el índice de archivos;
     * `?route=Archivo.php` las rutas de un archivo y `?route=all` las de todos.
     */
    public function versaRoute(): string
    {
        global $request;

        $route = (string) $request->get('route', '');
        $allRoutes = RouteManager::extractAllRouteData();

        $opcionActual = [];
        $routesFromRouteFile = [];
        $routesSummary = [];

        if ($route === '') {
            foreach ($allRoutes + ['all' => array_merge(...array_values($allRoutes))] as $file => $routes) {
                $routesSummary[$file] = self::summarize($routes);
            }
        } else {
            // Solo archivos reales de app/routes: cualquier otro valor vuelve al índice
            if ($route !== 'all' && !isset($allRoutes[$route])) {
                Response::redirect('/doc');

                return '';
            }

            $routesFromRouteFile = $route === 'all' ? array_merge(...array_values($allRoutes)) : $allRoutes[$route];
            $opcionActual = [
                ['link' => '/doc', 'name' => 'VersaRoutes - Files'],
                ['link' => '/doc?route=' . rawurlencode($route), 'name' => 'VersaRoutes - ' . ($route === 'all' ? 'Todas' : $route)],
            ];
        }

        return $this->template->render('versaWYS/versaRoute', [
            'opcionActual' => $opcionActual,
            'routesFiles' => $route === '' ? array_keys($allRoutes) : [],
            'routesSummary' => $routesSummary,
            'routesFromRouteFile' => $routesFromRouteFile,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $routes
     *
     * @return array{routes: int, errors: int, warnings: int}
     */
    private static function summarize(array $routes): array
    {
        $summary = ['routes' => count($routes), 'errors' => 0, 'warnings' => 0];
        foreach ($routes as $route) {
            $levels = array_column($route['issues'], 'level');
            $summary['errors'] += in_array('error', $levels, strict: true) ? 1 : 0;
            $summary['warnings'] += in_array('warning', $levels, strict: true) ? 1 : 0;
        }

        return $summary;
    }
}
