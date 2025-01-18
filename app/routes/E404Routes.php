<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\E404Controller;
use versaWYS\kernel\Router;

// description: Define the route for the 404 page.
Router::get('/e404', [E404Controller::class, 'e404']);
