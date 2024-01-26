<?php

declare(strict_types=1);

namespace versaWYS\kernel\helpers;

class Functions
{
    public static function dump($data)
    {
        echo "<style>
                .xdebug-var-dump {
                    position: fixed;
                    display: block;
                    width: 100%;
                    overflow: auto;
                    background: #fff;
                    color: #000;
                    text-align: left;
                    font-family: monospace;
                    font-size: 12px;
                    line-height: 1.4em;
                    border: 1px solid #ccc;
                    padding: 10px;
                    border-radius: 5px;
                    box-shadow: inset 0 0 5px #ccc;
                    z-index: 9999;
                }
                .xdebug-var-dump pre {
                    margin: 0;
                    padding: 0;
                    display: inline-block;
                    vertical-align: top;
                }
                .xdebug-var-dump pre span {
                    display: inline-block;
                    vertical-align: top;
                }
                .xdebug-var-dump pre span.type {
                    color: #000;
                }
                .xdebug-var-dump pre span.colon {
                    color: #000;
                }
                .xdebug-var-dump pre span.array,
                .xdebug-var-dump pre span.object {
                    color: #000;
                }
                .xdebug-var-dump pre span.null {
                    color: #000;
                }
                .xdebug-var-dump pre span.bool {
                    color: #000;
                }
            </style>
        <script>
            window.addEventListener('load', function() {
                window.addEventListener('load', function() {
                    let offset = 0;
                    document.querySelectorAll('.xdebug-var-dump').forEach(function(pre) {
                        pre.style.top = offset + 'px';
                        offset += pre.offsetHeight;
                    });
                });
            });
        </script>
        ";
        var_dump($data);
        echo "</pre>";
    }

    /**
     * Valida los parametros de la peticion
     *
     * @param array $params => $rules | $params = ['email' => 'required|email', 'password' => 'required|min:8']
     * @return array $errors
     */
    public static function validateParams(array $params, array $rules)
    {

        try {
            $errors = [];

            foreach ($rules as $field => $rule) {
                $rules = explode('|', $rule);

                foreach ($rules as $rule) {
                    $rule = explode(':', $rule);

                    switch ($rule[0]) {
                        case 'required':
                            if (!isset($params[$field]) || empty($params[$field])) {
                                $errors[$field] = 'El campo ' . $field . ' es requerido';
                            }
                            break;
                        case 'email':
                            if (!filter_var($params[$field], FILTER_VALIDATE_EMAIL)) {
                                $errors[$field] = 'El campo ' . $field . ' no es un email valido';
                            }
                            break;
                        case 'min':
                            if (strlen($params[$field]) < $rule[1]) {
                                $errors[$field] = 'El campo ' . $field . ' debe tener minimo ' . $rule[1] . ' caracteres';
                            }
                            break;
                        case 'max':
                            if (strlen($params[$field]) > $rule[1]) {
                                $errors[$field] = 'El campo ' . $field . ' debe tener maximo ' . $rule[1] . ' caracteres';
                            }
                            break;
                        case 'numeric':
                            if (!is_numeric($params[$field])) {
                                $errors[$field] = 'El campo ' . $field . ' debe ser numerico';
                            }
                            break;
                        case 'alpha':
                            if (!ctype_alpha($params[$field])) {
                                $errors[$field] = 'El campo ' . $field . ' debe ser alfabetico';
                            }
                            break;
                        case 'alpha_numeric':
                            if (!ctype_alnum($params[$field])) {
                                $errors[$field] = 'El campo ' . $field . ' debe ser alfanumerico';
                            }
                            break;
                        case 'alpha_numeric_space':
                            if (!ctype_alnum(str_replace(' ', '', $params[$field]))) {
                                $errors[$field] = 'El campo ' . $field . ' debe ser alfanumerico con espacios';
                            }
                            break;
                        case 'alpha_space':
                            if (!ctype_alpha(str_replace(' ', '', $params[$field]))) {
                                $errors[$field] = 'El campo ' . $field . ' debe ser alfabetico con espacios';
                            }
                            break;
                        case 'int':
                            if (!is_numeric($params[$field])) {
                                $errors[$field] = 'El campo ' . $field . ' debe ser numerico';
                            } else {
                                $params[$field] = (int) $params[$field];

                                if (str_contains($rule[1], '-')) {
                                    $valid = explode('-', $rule[1]);

                                    if ($params[$field] < $valid[0] || $params[$field] > $valid[1]) {
                                        $errors[$field] = 'El campo ' . $field . ' debe ser numerico entre ' . $valid[0] . ' y ' . $valid[1];
                                    }
                                } elseif (str_contains($rule[1], ',')) {
                                    $valid = explode(',', $rule[1]);

                                    if (!in_array($params[$field], $valid)) {
                                        $errors[$field] = 'El campo ' . $field . ' debe ser numerico y debe ser uno de los siguientes valores ' . implode(',', $valid);
                                    }
                                }
                            }
                            break;
                    }
                }
            }

            return $errors;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Permite generar un token para la contraseña
     * @param string $password
     * @return string
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Permite comparar el token generado con la contraseña
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyHash(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generates a CSRF token.
     *
     * @return string The generated CSRF token.
     */
    public static function generateCSRFToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validates the CSRF token.
     *
     * @param string $token The CSRF token to validate.
     * @return bool Returns true if the token is valid, false otherwise.
     */
    public static function validateCSRFToken(string $tokenSession, string $tokenFormUser): bool
    {
        return hash_equals($tokenSession, $tokenFormUser);
    }

    /**
     * Generates an MD5 hash based on the current time.
     *
     * @return string The generated MD5 hash.
     */
    public static function generateHashMd5Time(): string
    {
        return md5((string) time());
    }

    /**
     * Formatea una fecha en formato completo en español.
     *
     * @param string $fecha La fecha a formatear.
     * @return string La fecha formateada en formato completo en español.
     */
    public static function formaFechaFullES($fecha): string
    {
        $fecha = new \DateTime($fecha);
        $formatter = new \IntlDateFormatter('es_ES', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE);
        return $formatter->format($fecha);
    }

    /**
     * Generates the URL for an asset file.
     *
     * @param string $assetsPath The path to the asset file.
     * @return string The URL of the asset file.
     */
    public static function assets_url($assetsPath)
    {
        global $request;

        $url = $request->getBaseUrl() . '/' . $assetsPath;

        return $url;
    }

    /**
     * Reemplaza los espacios en una cadena por guiones bajos.
     *
     * @param string $string La cadena en la que se reemplazarán los espacios.
     * @return string La cadena con los espacios reemplazados por guiones bajos.
     */
    public static function replace_spaces_with_underscore($string)
    {
        return str_replace(' ', '_', $string);
    }

    /**
     * Generates a hidden input field with the specified HTTP method.
     *
     * @param string $method The HTTP method to be set.
     * @return string The generated HTML code for the hidden input field.
     */
    public static function method_field(string $method): string
    {
        $method = strtoupper(trim($method));
        return '<input type="hidden" name="_method" value="' . $method . '">';
    }

    /**
     * Generates a hidden input field with the specified CSRF token.
     *
     * @param string $token The CSRF token to be set.
     * @return string The generated HTML code for the hidden input field format raw for twig.
     */
    public static function csrf_field(): string
    {
        global $session;
        $token = $session->get('csrf_token');
        return '<input type="hidden" name="_csrf_token" id="csrf_token" value="' . $token . '">';
    }

    public static function getAssets(string $module, string $type, string $pathFile, bool $addURL = true)
    {
        global $config, $request;

        if (!isset($config['assets'][$module][$type])) throw new \Exception("No existe el tipo de archivo $type en el modulo $module");

        // verificar si tienen el slash al inicio
        $file = substr($pathFile, 0, 1) == '/' ? substr($pathFile, 1) : $pathFile;

        $pathSource = 'dist';

        $asset = ($addURL ? $request->getBaseUrl() . "/" : '') . $config['assets'][$module][$type][$pathSource] . "/" . $file;
        return $asset;
    }



    /**
     * Returns an array of Twig filters.
     *
     * @return array The array of Twig filters.
     */
    public function setTwigFilters(): array
    {
        return [
            ['formaFechaFullES', 'versaWYS\kernel\helpers\Functions::formaFechaFullES'],
            ['replace_spaces_with_underscore', 'versaWYS\kernel\helpers\Functions::replace_spaces_with_underscore'],
        ];
    }

    /**
     * Returns an array of Twig functions.
     *
     * @return array The array of Twig functions.
     */
    public function setTwigFunctions(): array
    {
        return [
            ['assets_url', 'versaWYS\kernel\helpers\Functions::assets_url'],
            ['method_field', 'versaWYS\kernel\helpers\Functions::method_field'],
            ['csrf_field', 'versaWYS\kernel\helpers\Functions::csrf_field'],
            ['getAssets', 'versaWYS\kernel\helpers\Functions::getAssets'],
        ];
    }
}
