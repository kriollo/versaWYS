<?php

declare(strict_types=1);

namespace versaWYS\kernel\helpers;

use DateException;
use DateTime;
use Exception;
use IntlDateFormatter;
use Random\RandomException;


class Functions
{
    public static function dump($data): void
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
        echo '</pre>';
    }

    /**
     * Cleans a string by removing leading and trailing whitespace and converting special characters to HTML entities.
     *
     * @param string $string The string to be cleaned.
     * @return string The cleaned string.
     */
    public static function cleanString(string $string): string
    {
        $string = trim($string);
        $string = htmlspecialchars($string);
        return $string;
    }

    /**
     * Valida los parametros de la peticion
     *
     * @param array $params => $rules | $params = ['email' => 'required|email', 'password' => 'required|min:8']
     * @return array $errors
     */

    public static function validateParams(array $params, array $rules): array
    {
        try {
            $errors = [];

            // Definir las validaciones en un arreglo tipo hash
            $validations = [
                'required' => function ($field, $value) {
                    return empty($value) ? "El campo $field es requerido" : null;
                },
                'email' => function ($field, $value) {
                    return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "El campo $field no es un email valido" : null;
                },
                'rut' => function ($field, $value) {
                    return !self::validarRut($value) ? "El campo $field no es un rut valido" : null;
                },
                'min' => function ($field, $value, $param) {
                    return strlen($value) < $param ? "El campo $field debe tener minimo $param caracteres" : null;
                },
                'max' => function ($field, $value, $param) {
                    return strlen($value) > $param ? "El campo $field debe tener maximo $param caracteres" : null;
                },
                'numeric' => function ($field, $value) {
                    return !is_numeric($value) ? "El campo $field debe ser numerico" : null;
                },
                'int' => function ($field, $value, $param) {
                    if (!is_numeric($value)) {
                        return "El campo $field debe ser numerico";
                    }
                    $value = (int) $value;
                    if (str_contains($param, '-')) {
                        [$min, $max] = explode('-', $param);
                        return $value < $min || $value > $max
                            ? "El campo $field debe ser numerico entre $min y $max"
                            : null;
                    } elseif (str_contains($param, ',')) {
                        $valid = explode(',', $param);
                        return !in_array($value, $valid)
                            ? "El campo $field debe ser numerico y debe ser uno de los siguientes valores " .
                                    implode(',', $valid)
                            : null;
                    }
                    return null;
                },
                'time' => function ($field, $value) {
                    return !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)
                        ? "El campo $field debe ser una hora valida"
                        : null;
                },
                'date' => function ($field, $value) {
                    try {
                        new DateTime($value);
                        return null;
                    } catch (Exception $e) {
                        return "El campo $field debe ser una fecha valida";
                    }
                },
                'array' => function ($field, $value) {

                    if(is_array($value)) {
                        return null;
                    }

                    return !(is_array(json_decode($value, true)) && json_last_error() == JSON_ERROR_NONE) ? "El campo $field debe ser un arreglo" : null;
                },
            ];

            foreach ($rules as $field => $rule) {
                $rulesArray = explode('|', $rule);

                foreach ($rulesArray as $rul) {
                    [$rulName, $param] = array_pad(explode(':', $rul), 2, null);

                    // Verificar si el campo existe
                    if (!isset($params[$field])) {
                        $errors[$field] = "El campo $field no existe";
                    } else {
                        // Ejecutar la validación correspondiente
                        if (isset($validations[$rulName])) {
                            $error = $validations[$rulName]($field, $params[$field], $param);
                            if ($error) {
                                $errors[$field] = $error;
                            }
                        }
                    }
                }
            }

            return $errors;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public static function validarRut(string $rut): bool
    {
        $rut = str_replace('.', '', $rut);
        $rut = str_replace('-', '', $rut);

        $rut = explode('-', $rut);
        $rut = $rut[0];
        $dv = strtoupper($rut[strlen($rut) - 1]);
        $rut = substr($rut, 0, -1);

        $i = 2;
        $suma = 0;
        foreach (array_reverse(str_split($rut)) as $v) {
            if ($i == 8) {
                $i = 2;
            }
            $suma += $v * $i;
            ++$i;
        }

        $dvr = 11 - ($suma % 11);

        if ($dvr == 11) {
            $dvr = 0;
        }
        if ($dvr == 10) {
            $dvr = 'K';
        }

        return $dv == $dvr;
    }

    public static function validateJson(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
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
     * @throws RandomException
     */
    public static function generateCSRFToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validates the CSRF token.
     *
     * @param string $tokenSession
     * @param string $tokenFormUser
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
     * @throws Exception
     */
    public static function formaFechaFullES(string $fecha): string
    {
        $fecha = new DateTime($fecha);
        $formatter = new IntlDateFormatter('es_ES', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
        return $formatter->format($fecha);
    }

    /**
     * Generates the URL for an asset file.
     *
     * @param string $assetsPath The path to the asset file.
     * @return string The URL of the asset file.
     */
    public static function assets_url(string $assetsPath): string
    {
        global $request;

        if (str_starts_with($assetsPath, '/')) {
            $assetsPath = substr($assetsPath, 1);
        }

        return $request->getBaseUrl() . '/' . $assetsPath;
    }

    /**
     * Reemplaza los espacios en una cadena por guiones bajos.
     *
     * @param string $string La cadena en la que se reemplazarán los espacios.
     * @return string La cadena con los espacios reemplazados por guiones bajos.
     */
    public static function replace_spaces_with_underscore(string $string): string
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
        return "<input type=\"hidden\" name=\"_method\" value=\"$method\">";
    }

    /**
     * Generates a hidden input field with the specified CSRF token.
     *
     * @return string The generated HTML code for the hidden input field format raw for twig.
     */
    public static function csrf_field(string $id = 'csrf_token'): string
    {
        global $session;
        $token = $session->get('csrf_token');
        return "<input type=\"hidden\" name=\"_csrf_token\" id=\"$id\" value=\"$token\">";
    }

    /**
     * @throws Exception
     */
    public static function getAssets(string $module, string $type, string $pathFile, bool $addURL = true): string
    {
        global $config, $request;

        if (!isset($config['assets'][$module][$type])) {
            throw new Exception("No existe el tipo de archivo $type en el modulo $module");
        }

        // verificar si tienen el slash al inicio
        $file = str_starts_with($pathFile, '/') ? substr($pathFile, 1) : $pathFile;

        $pathSource = 'dist';

        return ($addURL ? $request->getBaseUrl() . '/' : '') .
            $config['assets'][$module][$type][$pathSource] .
            '/' .
            $file;
    }

    /**
     * Removes escape characters from a string.
     *
     * @param string $e The string to remove escape characters from.
     * @return string The string without escape characters.
     */
    public static function removeScape(string $e): string
    {
        return stripslashes($e);
    }

    /**
     * Formats a Chilean RUT (Rol Único Tributario) by removing dots and hyphens,
     * then adding a period every three digits and appending the verification digit.
     *
     * @param string $rut The RUT to be formatted.
     * @return string The formatted RUT.
     */
    public static function formatRut(string $rut): string
    {
        $rut = str_replace('.', '', $rut);
        $rut = str_replace('-', '', $rut);

        $rut = explode('-', $rut);
        $rut = $rut[0];
        $dv = strtoupper($rut[strlen($rut) - 1]);
        $rut = substr($rut, 0, -1);

        $rut = number_format((float) $rut, 0, ',', '.');
        return "$rut-$dv";
    }

    /**
     * Returns an array of Twig filters.
     *
     * @return array The array of Twig filters.
     */
    public function setTwigFilters(): array
    {
        return [
            ['replace_spaces_with_underscore', 'versaWYS\kernel\helpers\Functions::replace_spaces_with_underscore'],
            ['removeScape', 'versaWYS\kernel\helpers\Functions::removeScape'],
            ['formatRut', 'versaWYS\kernel\helpers\Functions::formatRut'],
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
