<?php

/**
 * FILEPATH: /c:/Nextcloud/htdocs/versaWYS-PHP/app/routes/http_dashboard.php
 *
 * This file contains the route definitions for the dashboard related endpoints.
 */

declare(strict_types=1);

namespace app\Routes;

use app\controllers\DashBoardController;
use app\middleware\AuthMiddleware;
use versaWYS\kernel\Router;

// Rutas de ingreso a la raiz del sitio
Router::get('/', [DashBoardController::class, 'index']);

/**
 * Define the route for the admin dashboard.
 * This route requires authentication.
 */
Router::get('/admin', [DashBoardController::class, 'dashboard'])->middleware([[AuthMiddleware::class, 'checkSession']]);

/**
 * Define the route for the admin login page.
 * This route redirects if a session is already active.
 */
Router::get('/admin/login', [DashBoardController::class, 'login'])->middleware([
    [AuthMiddleware::class, 'redirectIfSession'],
]);

/**
 * Define the route for the authentication process of the admin login.
 * This route requires CSRF token validation, login parameter validation, and login attempts validation.
 */
Router::post('/admin/login/autentication', [DashBoardController::class, 'autentication'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'validateParamsLogin'],
    [AuthMiddleware::class, 'validateAttemps'],
]);

/**
 * Define the route for the admin logout.
 * This route requires authentication.
 */
Router::get('/admin/logout', [DashBoardController::class, 'logout'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
]);

/**
 * Define the route for the admin dashboard.
 * This route requires authentication and is a protected route.
 */
Router::get('/admin/dashboard', [DashBoardController::class, 'dashboard'])->middleware([
    [AuthMiddleware::class, 'checkSession'],
]);

/**
 * Define the route for the lost password page.
 * This route redirects if a session is already active.
 */
Router::get('/admin/lost-password', [DashBoardController::class, 'lostPassword'])->middleware([
    [AuthMiddleware::class, 'redirectIfSession'],
]);

/**
 * Define the route for sending the lost password email.
 * This route requires CSRF token validation and email validation.
 */
Router::patch('/admin/lost-password/send', [DashBoardController::class, 'sendLostPassword'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'checkMail'],
]);

/**
 * Define the route for the reset password page.
 */
Router::get('/admin/reset-password', [DashBoardController::class, 'resetPassword']);

/**
 * Define the route for applying the password reset.
 * This route requires CSRF token validation and parameter validation.
 */
Router::post('/admin/login/apply-reset-password', [DashBoardController::class, 'applyChangePassword'])->middleware([
    [AuthMiddleware::class, 'validateCSRFToken'],
    [AuthMiddleware::class, 'validateParamsApplyResetPassword'],
]);
