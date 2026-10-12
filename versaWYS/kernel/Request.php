<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Exception;
use RuntimeException;
use Throwable;
use versaWYS\kernel\helpers\Functions;

class Request
{
    protected mixed $contentType;

    protected string $method;

    protected mixed $url;

    protected array $params;

    protected array $files;

    protected mixed $ip;

    protected mixed $accept;

    protected mixed $rawBody;

    protected mixed $rawBodyLength;

    public function __construct()
    {
        try {
            $this->contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            $this->method = strtoupper($_SERVER['REQUEST_METHOD']) ?? 'GET';
            $this->url = $_SERVER['REQUEST_URI'] ?? '/';
            $this->params = $_REQUEST;
            $this->files = $_FILES;
            $this->ip = $_SERVER['REMOTE_ADDR'];
            $this->accept = $_SERVER['HTTP_ACCEPT'] ?? '*/*';
            $this->prepareFiles();
            $this->prepareParams();

            $this->rawBody = file_get_contents('php://input') ?? null;
            $this->rawBodyLength = strlen($this->rawBody) ?? 0;

            // _method solo puede elevar un POST de formulario: nunca convertir un GET en escritura
            if ($this->method === 'POST' && isset($this->params['_method'])) {
                $this->method = strtoupper($this->params['_method']);
            }
        } catch (Throwable $throwable) {
            $error_message = 'Error al inicializar la solicitud: ' . $throwable->getMessage();
            Loggin::error($error_message, 'request', [
                'exception' => $throwable,
            ]);

            // Handle error according to debug mode
            global $config;
            if (isset($config['build']['debug']) && $config['build']['debug']) {
                // In debug mode, use ErrorHandler to show detailed error
                if (class_exists('versaWYS\\kernel\\ErrorHandler')) {
                    $errorHandler = new ErrorHandler();
                    $errorHandler->handleException(new RuntimeException($error_message));
                    exit();
                }

                // Fallback if ErrorHandler is not available yet
                Loggin::error('ErrorHandler no disponible: ' . $error_message, 'request', [$throwable]);

                throw new Exception($error_message, $throwable->getCode(), $throwable);
            }

            // In production mode, show clean error
            Loggin::error('Request initialization error in production: ' . $error_message, 'request');

            // Use our static method to determine if it's an API call
            $isApi = $this->isStaticApiCall();

            if ($isApi) {
                Response::jsonError([
                    'message' => 'Error interno del servidor',
                    'code' => 500,
                ], 500);
                exit();
            }

            http_response_code(500);
            echo '<h1>Error del sistema</h1><p>Error de inicialización. Por favor, contacte al administrador.</p>';
            exit();
        }
    }

    public function procesaFormData($params): array
    {
        $boundary = '--' . explode('boundary=', $_SERVER['CONTENT_TYPE'])[1];
        $parts = explode($boundary, $params);
        $data = [];
        $files = [];

        foreach ($parts as $part) {
            if (!($part !== '' && $part !== '0' && str_contains($part, 'name='))) {
                continue;
            }

            preg_match('/name="(.*?)"/', $part, $matches);
            $fieldName = $matches[1];

            if (strpos($part, 'filename=') && $_FILES === []) {
                $file = $this->extractFilesFormData($part);
                $files[$fieldName] = $file;

                continue;
            }

            preg_match('/\r\n\r\n(.*?)\r\n/', $part, $matches);

            $fieldValue = $matches[1];
            $data[$fieldName] = $fieldValue;
        }

        if ($files !== [] && $_FILES === []) {
            $this->files = filter_var_array($files, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $data;
    }

    public function setParam(string $fieldName, mixed $value): void
    {
        $this->params[$fieldName] = $value;
    }

    /**
     * Obtiene el valor de un campo de entrada de la solicitud.
     *
     * @param string $fieldName el nombre del campo de entrada
     *
     * @return mixed el valor del campo de entrada o null si no está presente
     */
    public function get(string $fieldName, mixed $default = null): mixed
    {
        return $this->params[$fieldName] ?? $default;
    }

    /**
     * Returns the file with the given file name from the request.
     *
     * @param string $fileName the name of the file to retrieve
     *
     * @throws Exception
     *
     * @return File|null the file object if it exists, or null if it doesn't
     */
    public function file(string $fileName): ?File
    {
        if (isset($this->files[$fileName])) {
            return new File($this->files[$fileName]);
        }

        return null;
    }

    /**
     * Obtiene todos los parámetros de la solicitud.
     * Ahora incluye sanitización de seguridad.
     *
     * @return array los parámetros de la solicitud
     */
    public function getAllParams(): array
    {
        // Aplicar sanitización de seguridad a los parámetros
        return SecurityUtils::sanitizeInputParams($this->params);
    }

    /**
     * Obtiene los parámetros sin sanitizar (para casos especiales).
     *
     * @return array Los parámetros originales
     */
    public function getRawParams(): array
    {
        return $this->params;
    }

    /**
     * Valida el token CSRF para la petición actual.
     *
     * @return bool True si es válido, false en caso contrario
     */
    public function validateCsrf(): bool
    {
        return SecurityUtils::validateCsrfForRequest($this->params);
    }

    /**
     * Obtiene todos los archivos adjuntos de la solicitud.
     *
     * @throws Exception
     *
     * @return array un array de objetos File que representan los archivos adjuntos
     */
    public function getAllFiles($fileName): array
    {
        $files = [];

        if (!isset($this->files[$fileName]) || !isset($this->files[$fileName]['name'])) {
            return []; // No hay archivos para este nombre de campo
        }

        // Si 'name' no es un array, es un solo archivo.
        if (!is_array($this->files[$fileName]['name'])) {
            // Verificar si es un archivo válido antes de procesarlo
            if (isset($this->files[$fileName]['tmp_name']) && $this->files[$fileName]['tmp_name'] !== '') {
                try {
                    return [new File($this->files[$fileName])];
                } catch (Exception) {
                    // Registrar el error o manejarlo según sea necesario
                    return [];
                }
            }

            return [];
        }

        $fileCount = count($this->files[$fileName]['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $currentFile = [];
            foreach ($this->files[$fileName] as $key => $value) {
                $currentFile[$key] = $value[$i] ?? null;
            }

            // Solo procesar si tmp_name está presente y no está vacío
            if (isset($currentFile['tmp_name']) && $currentFile['tmp_name'] !== '') {
                try {
                    $files[] = new File($currentFile);
                } catch (Exception) {
                    // Registrar el error o manejarlo, opcionalmente continuar con otros archivos
                }
            }
        }

        return $files;
    }

    public function getContentType(): mixed
    {
        return $this->contentType;
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getClientIp(): string
    {
        return $this->ip;
    }

    public function getAccept(): string
    {
        return $this->accept;
    }

    public function getRawBody(): mixed
    {
        return $this->rawBody;
    }

    public function getRawBodyLength(): mixed
    {
        return $this->rawBodyLength;
    }

    /**
     * Returns the base URL of the current request.
     *
     * @return string the base URL of the current request
     */
    public function getBaseUrl(): string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $_SERVER['HTTPS'] = 'on';
            // Opcional: ajustar puerto y host si es necesario
            if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
                $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
            }
        }

        $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . $host;
    }

    /**
     * Obtiene el valor de una cabecera HTTP específica.
     *
     * @param string $headerName el nombre de la cabecera
     *
     * @return string|null el valor de la cabecera o null si no se encuentra
     */
    public function getHeader(string $headerName): ?string
    {
        $headers = getallheaders();
        $headerName = strtolower($headerName);

        foreach ($headers as $key => $headerValue) {
            if (strtolower($key) !== $headerName) {
                continue;
            }

            return $headerValue;
        }

        return null;
    }

    /**
     * Determina si la petición actual es una navegación SPA (Single Page Application).
     * Este es el método centralizado para detectar peticiones SPA en todo el framework.
     * Debe usarse en lugar de verificaciones inline en Router, Response, versaTwig, etc.
     */
    public function isSPARequest(): bool
    {
        return (
            isset($_SERVER['HTTP_X_SPA'])
            || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'
        );
    }

    /**
     * Versión estática de isSPARequest() para contextos donde no hay instancia de Request.
     * Útil en ErrorHandler, Response y otros contextos estáticos.
     */
    public static function isSPARequestStatic(): bool
    {
        return (
            isset($_SERVER['HTTP_X_SPA'])
            || isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest'
        );
    }

    /**
     * Determina en base a los headers recibidos si es una llamada por API REST o navegador.
     */
    public function isApiCall(): bool
    {
        $url = strtolower($this->getUrl());
        $contentType = $this->getHeader('Content-Type'); // Puede ser null
        $acceptHeader = strtolower($this->getHeader('Accept') ?? '');

        if ($this->getHeader('X-Spa')) {
            return false;
        }

        // Comprobación prioritaria por URL
        if (str_contains($url, '/api/')) {
            return true;
        }

        // Comprobación por encabezado Accept, común para APIs RESTful
        if (str_contains($acceptHeader, 'application/json')) {
            return true;
        }

        // Comprobación por Content-Type para solicitudes con cuerpo (POST, PUT, PATCH)
        if ($contentType !== null) {
            $apiContentTypes = [
                'application/json',
                'application/xml',
                'multipart/form-data', // A menudo usado por APIs para subida de archivos
                'application/x-www-form-urlencoded', // A veces usado por APIs
                // 'text/plain' es muy genérico, podría eliminarse si causa falsos positivos
            ];

            foreach ($apiContentTypes as $type) {
                if (!str_starts_with(strtolower($contentType), $type)) {
                    continue;
                }

                return true;
            }
        }

        // Si un cliente XHR (como fetch o XMLHttpRequest) establece X-Requested-With
        return strtolower($this->getHeader('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    protected function prepareParams(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // Para GET, aplicar filtrado básico pero no agresivo
            $this->params = $_GET ?? [];
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->contentType !== '' && str_starts_with(strtolower($this->contentType), 'application/json')) {
                $this->params = json_decode(file_get_contents('php://input'), true) ?? [];
            } else {
                // Para POST multipart/form-data, no aplicar filtros agresivos
                $this->params = $_POST;
                foreach (array_keys($this->params) as $key) {
                    $this->processJsonValue($key);
                }
            }
        } else {
            $this->processNonPostParams();
        }
    }

    protected function processNonPostParams(): void
    {
        $params = file_get_contents('php://input');

        if ($this->contentType !== '' && strtolower($this->contentType) === 'application/json') {
            $this->params = $params === '' || $params === null || !Functions::validateJson($params)
                ? []
                : json_decode($params, true);
        } elseif (str_contains($this->contentType, 'multipart/form-data')) {
            $this->params = $this->procesaFormData($params);
        } else {
            parse_str($params, $this->params);
        }

        foreach (array_keys($this->params) as $key) {
            $this->processJsonValue($key);
        }
    }

    protected function processJsonValue($key): void
    {
        $value = $this->params[$key];

        // Solo procesar como JSON si necesita decodificación de entidades HTML
        // Para campos como 'data' que ya son JSON válido, mantenerlos como string
        if (
            is_string($value)
            && str_contains($value, '{')
            && (str_contains($value, '&lt;') || str_contains($value, '&gt;') || str_contains($value, '&quot;'))
        ) {
            // Solo decodificar entidades HTML, mantener como JSON string
            $decodedValue = html_entity_decode($value, ENT_QUOTES, 'UTF-8');

            // Verificar que sigue siendo JSON válido después de decodificar
            if (json_decode($decodedValue, true) !== null && json_last_error() === JSON_ERROR_NONE) {
                $this->params[$key] = $decodedValue; // Mantener como string JSON
            }
        }
    }

    protected function prepareFiles(): void
    {
        $this->files = filter_var_array($_FILES, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    private function extractFilesFormData(string $part): array
    {
        $fileName = explode('filename=', $part)[1];
        $fileName = explode("\r\n", $fileName)[0];
        $fileName = str_replace('"', '', $fileName);

        $fileType = explode("\r\n", explode('Content-Type: ', $part)[1])[0];

        $fileTmpContent = explode("\r\n\r\n", $part)[1];
        $fileTmpName = tempnam(sys_get_temp_dir(), 'uploaded_file_');

        file_put_contents($fileTmpName, $fileTmpContent);
        chmod($fileTmpName, 0o600);

        return [
            'name' => $fileName,
            'type' => $fileType,
            'tmp_name' => $fileTmpName,
            'error' => 0, // Puedes ajustar según necesidad
            'size' => strlen($fileTmpContent), // Puedes ajustar según necesidad
            'from' => 'formData',
        ];
    }

    /**
     * Método estático para determinar si es una llamada API sin necesidad de instancia completa.
     * Útil para usar en el constructor cuando la instancia no está completamente inicializada.
     */
    private function isStaticApiCall(): bool
    {
        $url = strtolower($_SERVER['REQUEST_URI'] ?? '');
        $acceptHeader = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // Comprobación prioritaria por URL
        if (str_contains($url, '/api/')) {
            return true;
        }

        // Comprobación por encabezado Accept, común para APIs RESTful
        if (str_contains($acceptHeader, 'application/json')) {
            return true;
        }

        // Comprobación por Content-Type para solicitudes con cuerpo (POST, PUT, PATCH)
        if ($contentType !== '') {
            $apiContentTypes = [
                'application/json',
                'application/xml',
                'multipart/form-data',
                'application/x-www-form-urlencoded',
            ];

            foreach ($apiContentTypes as $type) {
                if (!str_starts_with(strtolower($contentType), $type)) {
                    continue;
                }

                return true;
            }
        }

        // Si un cliente XHR (como fetch o XMLHttpRequest) establece X-Requested-With
        $headers = getallheaders();

        return isset($headers['X-Requested-With']) && strtolower($headers['X-Requested-With']) === 'xmlhttprequest';
    }
}
