<?php

declare(strict_types=1);

namespace versaWYS\kernel;



use Exception;

class Request
{
    protected mixed $contentType;
    protected string $method;
    protected mixed $url;
    protected array $params;
    protected array $files;
    protected mixed $ip;
    protected mixed $accept;

    public function __construct()
    {
        $this->contentType = $_SERVER['CONTENT_TYPE'] ?? null;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']) ?? 'GET';
        $this->url = $_SERVER['REQUEST_URI'] ?? '/';
        $this->params = $_REQUEST;
        $this->files = $_FILES;
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->accept = $_SERVER['HTTP_ACCEPT'] ?? '*/*';
        $this->prepareFiles();
        $this->prepareParams();

        if (isset($this->params['_method'])) {
            $this->method = strtoupper($this->params['_method']);
        }
    }

    protected function prepareParams(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->params = filter_var_array($_GET ?? [], FILTER_SANITIZE_SPECIAL_CHARS);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($this->contentType != "" && strtolower($this->contentType) === 'application/json') {
                $this->params = json_decode(file_get_contents('php://input'), true);
            } else {
                $this->params = $_POST;
                foreach ($this->params as $key => $value) {
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

        if (strtolower($this->contentType) === 'application/json') {
            $this->params = json_decode($params, true);
        } else {
            if (str_contains($this->contentType, 'multipart/form-data')) {
                $this->params = $this->procesaFormData($params);
            } else {
                parse_str($params, $this->params);
            }
        }

        foreach ($this->params as $key => $value) {
            $this->processJsonValue($key);
        }

        $this->params = filter_var_array($this->params, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    protected function processJsonValue($key): void
    {
        $value = $this->params[$key];
        if (is_string($value) && is_array(json_decode($value, true)) && (json_last_error() == JSON_ERROR_NONE) && str_contains($value, '{')) {
            $this->params[$key] = json_decode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), true);
        }
    }

    protected function prepareFiles(): void
    {
        $this->files = filter_var_array($_FILES, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    public function procesaFormData($params): array
    {
        $boundary = "--" . explode("boundary=", $_SERVER["CONTENT_TYPE"])[1];
        $parts = explode($boundary, $params);
        $data = [];
        $files = [];

        foreach ($parts as $part) {
            if (!empty($part) && str_contains($part, 'name=')) {
                preg_match('/name="(.*?)"/', $part, $matches);
                $fieldName = $matches[1];

                if (strpos($part, 'filename=') && empty($_FILES)) {
                    $file = $this->extractFilesFormData($part);
                    $files[$fieldName] = $file;
                    continue;
                }

                preg_match('/\r\n\r\n(.*?)\r\n/', $part, $matches);

                $fieldValue = $matches[1];
                $data[$fieldName] = $fieldValue;
            }
        }
        if (!empty($files) && empty($_FILES)) {
            $this->files = filter_var_array($files, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $data;
    }

    private function extractFilesFormData($part): array
    {

        $fileName = explode('filename=', $part)[1];
        $fileName = explode("\r\n", $fileName)[0];
        $fileName = str_replace('"', '', $fileName);

        $fileType = explode("\r\n", explode('Content-Type: ', $part)[1])[0];

        $fileTmpContent = explode("\r\n\r\n", $part)[1];
        $fileTmpName = tempnam(sys_get_temp_dir(), 'uploaded_file_');

        file_put_contents($fileTmpName, $fileTmpContent);

        return [
            'name' => $fileName,
            'type' => $fileType,
            'tmp_name' => $fileTmpName,
            'error' => 0, // Puedes ajustar según necesidad
            'size' => strlen($fileTmpContent), // Puedes ajustar según necesidad
            'from' => 'formData'
        ];
    }


    /**
     * Obtiene el valor de un campo de entrada de la solicitud.
     *
     * @param string $fieldName El nombre del campo de entrada.
     * @return mixed El valor del campo de entrada o null si no está presente.
     */
    public function get(string $fieldName): mixed
    {
        return $this->params[$fieldName] ?? null;
    }

    /**
     * Returns the file with the given file name from the request.
     *
     * @param string $fileName The name of the file to retrieve.
     * @return File|null The file object if it exists, or null if it doesn't.
     * @throws Exception
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
     *
     * @return array Los parámetros de la solicitud.
     */
    public function getAllParams(): array
    {
        return $this->params;
    }

    /**
     * Obtiene todos los archivos adjuntos de la solicitud.
     *
     * @return array Un array de objetos File que representan los archivos adjuntos.
     * @throws Exception
     */
    public function getAllFiles($fileName): array
    {
        $files = [];

        $fileCount = count($this->files[$fileName]['name']);

        for ($i = 0; $i < $fileCount; $i++) {
            $file = [];
            foreach ($this->files[$fileName] as $key => $value) {
                $file[$key] =  $this->files[$fileName][$key][$i];
            }

            $files[] = new File($file);
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
    public function getAccept()
    {
        return $this->accept;
    }

    /**
     * Returns the base URL of the current request.
     *
     * @return string The base URL of the current request.
     */
    public function getBaseUrl(): string
    {
        $protocol =  !empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS']) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        return $protocol . $host;
    }

    /**
     * Obtiene el valor de una cabecera HTTP específica.
     *
     * @param string $headerName El nombre de la cabecera.
     * @return string|null El valor de la cabecera o null si no se encuentra.
     */
    public function getHeader(string $headerName): ?string
    {
        $headers = getallheaders();
        return $headers[$headerName] ?? null;
    }

    /**
     * Determina en base a los header recividos si es una llama por api rest o navegador.
     *
     * @return boolean
     */
    public function isApiCall(): bool
    {
        if ($this->getHeader('Content-Type') === null) {
            return false;
        }
        if (
            str_starts_with($this->getHeader('Content-Type'), 'application/json') ||
            str_starts_with($this->getHeader('Content-Type'), 'multipart/form-data') ||
            str_starts_with($this->getHeader('Content-Type'), 'application/x-www-form-urlencoded') ||
            str_starts_with($this->getHeader('Content-Type'), 'text/plain') ||
            str_starts_with($this->getAccept(), 'text/css')
        ) {
            return true;
        }
        return false;
    }
}
