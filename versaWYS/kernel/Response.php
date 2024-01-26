<?php

declare(strict_types=1);

namespace versaWYS\kernel;

class Response
{
    /**
     * Sends a JSON response.
     *
     * @param array $data The data to be encoded as JSON.
     * @param int $code The HTTP response code (default: 200).
     * @return void
     */
    public static function json(array $data, int $code = 200): void
    {

        if (error_get_last() !== null) {
            $data = [
                'success' => 0,
                'message' => 'Error en la petición',
                'errors' => error_get_last(),
                'code' => 500
            ];
            $code = 500;
            self::jsonError($data, $code);
            die();
        }
        header('Content-Type: application/json');
        echo json_encode($data);
        http_response_code($code);
    }


    public static function jsonError(array $data, int $code = 200): void
    {
        global $twig;
        header('Content-Type: text/html; charset=utf-8');
        echo $twig->render('versaWYS/debugError', ['data' => $data]);
    }

    /**
     * Redirects the user to the specified route.
     *
     * @param string $route The route to redirect to.
     * @return void
     */
    public static function redirect(string $route)
    {
        header('Location: ' . $route);
    }
}