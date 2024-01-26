<?php

declare(strict_types=1);

namespace app\Routes;

use versaWYS\kernel\Router;


Router::get('/e404',
    [\app\controllers\E404Controller::class, 'e404']
);
