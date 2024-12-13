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
     * @return mixed
     */
    public static function json(array $data, int $code = 200): bool|string
    {
        if (error_get_last() !== null) {
            $data = [
                'success' => 0,
                'message' => 'Error en la petición',
                'errors' => error_get_last(),
                'code' => 500,
            ];
            $code = 500;
            self::jsonError($data, $code);
            return false;
        }
        if (is_array($data) || is_object($data)) {
            header('Content-Type: application/json');
            http_response_code($code);
            print json_encode($data);
        } else {
            self::jsonError(['message' => 'Error en la petición', 'code' => 500], 500);
            return false;
        }
        return true;
    }

    public static function jsonError(array $data, int $code = 200): bool
    {
        global $twig;
        http_response_code($code);
        echo $twig->render('versaWYS/debugError.twig', ['data' => $data]);
        exit();
    }

    /**
     * Redirects the user to the specified route.
     *
     * @param string $route The route to redirect to.
     * @return void
     */
    public static function redirect(string $route): void
    {
        header('Location: ' . $route);
    }
}
