<?php

declare(strict_types=1);

namespace app\controllers;

use app\Models as Models;
use versaWYS\kernel\Response;

class E404Controller
{
    /**
     * Renders the 404 error page.
     *
     * @return string The rendered HTML of the 404 error page.
     */
    public function e404(): string
    {
        global $twig;
        return $twig->render('versaWYS/404');
    }
}