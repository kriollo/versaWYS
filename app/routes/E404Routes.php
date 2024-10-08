<?php

declare(strict_types=1);

namespace app\routes;

use app\controllers\E404Controller;
use versaWYS\kernel\Router;

Router::get('/e404', [E404Controller::class, 'e404']);
