<?php

declare(strict_types=1);

namespace app\controllers;

class E404Controller
{
    /**
     * Renders the 404 error page.
     *
     * @return string the rendered HTML of the 404 error page
     */
    public function e404(): string
    {
        global $twig;

        return $twig->render('versaWYS/404');
    }
}
