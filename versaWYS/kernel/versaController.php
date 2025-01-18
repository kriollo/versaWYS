<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models as Models;
use versaWYS\kernel\cli\RouteManager;
use versaWYS\kernel\GlobalControllers;
use versaWYS\kernel\helpers\Functions;

class versaController extends GlobalControllers
{
    public function __construct()
    {
        global $twig, $session;
        parent::__construct($twig, $session);
    }

    public function versaRoute()
    {
        global $request;

        $params = $request->getAllParams();

        $opcionActual = [];
        $routesFiles = [];
        $routesFromRouteFile = [];
        if (isset($params['route'])) {
            $opcionActual = [
                ['link' => '/doc', 'name' => 'VersaRoutes - Files'],
                [
                    'link' => '/doc?route=' . $params['route'],
                    'name' => 'VersaRoutes - ' . $params['route'],
                ],
            ];

            $routesFromRouteFile = RouteManager::extractRouteData($params['route']);

            // dump($routesFromRouteFile);
        } else {
            $routesFiles = RouteManager::getRoutes();
        }

        return $this->template->render('versaWYS/versaRoute', [
            'opcionActual' => $opcionActual,
            'routesFiles' => $routesFiles,
            'routesFromRouteFile' => $routesFromRouteFile,
        ]);
    }
}
