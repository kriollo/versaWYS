<?php

declare(strict_types=1);

namespace versaWYS\kernel;



class Request
{
    protected $contentType;
    protected $Method;
    protected $url;
    protected $params;
    protected $files;
    protected $ip;
    protected $accept;

    public function __construct()
    {
        $this->contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : null;
        $this->Method = strtoupper($_SERVER['REQUEST_METHOD']) ?? 'GET';
        $this->url = $_SERVER['REQUEST_URI'] ?? '/';
        $this->params = $_REQUEST;
        $this->files = $_FILES;
        $this->ip = $_SERVER['REMOTE_ADDR'];
        $this->accept = $_SERVER['HTTP_ACCEPT'] ?? '*/*';
        $this->prepareFiles();
        $this->prepareParams();

        if (isset($this->params['_method'])) {
            $this->Method = strtoupper($this->params['_method']);
        }
    }

    protected function prepareParams()
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

    protected function processNonPostParams()
    {
        $params = file_get_contents('php://input');

        if (strtolower($this->contentType) === 'application/json') {
            $this->params = json_decode($params, true);
        } else {
            if (strpos($this->contentType, 'multipart/form-data') !== false) {
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

    protected function processJsonValue($key)
    {
        $value = $this->params[$key];
        if (is_string($value) && is_array(json_decode($value, true)) && (json_last_error() == JSON_ERROR_NONE) && strpos($value, '{') !== false) {
            $this->params[$key] = json_decode(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), true);
        }
    }

    protected function prepareFiles()
    {
        $this->files = filter_var_array($_FILES, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    public function procesaFormData($params)
    {
        $boundary = "--" . explode("boundary=", $_SERVER["CONTENT_TYPE"])[1];
        $parts = explode($boundary, $params);
        $data = [];
        $files = [];

        foreach ($parts as $part) {
            if (!empty($part) && strpos($part, 'name=') !== false) {
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

    private function extractFilesFormData($part)
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
    public function get($fieldName)
    {
        return $this->params[$fieldName] ?? null;
    }

    /**
     * Returns the file with the given file name from the request.
     *
     * @param string $fileName The name of the file to retrieve.
     * @return File|null The file object if it exists, or null if it doesn't.
     */
    public function file($fileName)
    {
        if (isset($this->files[$fileName])) {
            return new File($this->files[$fileName]);
        }
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
     */
    public function getAllFiles(): array
    {

        // recorrer los archivos y crear un objeto File
        $files = [];
        foreach ($this->files as $key => $file) {
            $files[] = new File($file);
        }
        return $files;
    }

    public function getContentType()
    {
        return $this->contentType;
    }
    public function getMethod()
    {
        return strtoupper($this->Method);
    }
    public function getUrl()
    {
        return $this->url;
    }
    public function getParams()
    {
        return $this->params;
    }
    public function getFiles()
    {
        return $this->files;
    }
    public function getIp()
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
    public function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) === true  && !empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS']) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $url = $protocol . $host;
        return $url;
    }

    /**
     * Obtiene el valor de una cabecera HTTP específica.
     *
     * @param string $headerName El nombre de la cabecera.
     * @return string|null El valor de la cabecera o null si no se encuentra.
     */
    public function getHeader($headerName)
    {
        $headers = getallheaders();
        return $headers[$headerName] ?? null;
    }

    /**
     * Determina en base a los header recividos si es una llama por api rest o navegador.
     *
     * @return boolean
     */
    public function isApiCall()
    {
        if ($this->getHeader('Content-Type') === null) {
            return false;
        }
        if (
            strpos($this->getHeader('Content-Type'), 'application/json') === 0 ||
            strpos($this->getHeader('Content-Type'), 'multipart/form-data') === 0 ||
            strpos($this->getHeader('Content-Type'), 'application/x-www-form-urlencoded') === 0 ||
            strpos($this->getHeader('Content-Type'), 'text/plain') === 0 ||
            strpos($this->getAccept(), 'text/css') === 0
        ) {
            return true;
        }
        return false;
    }
}
