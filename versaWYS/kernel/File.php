<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Exception;
use SplFileInfo;

class File extends SplFileInfo implements \ArrayAccess
{
    protected array $file;

    /**
     * @param string $offset
     */
    #[\Override]
    public function offsetExists($offset): bool
    {
        return isset($this->file[$offset]);
    }

    /**
     * @param string $offset
     */
    #[\Override]
    public function offsetGet($offset): mixed
    {
        return $this->file[$offset] ?? null;
    }

    /**
     * @param string $offset
     */
    #[\Override]
    public function offsetSet($offset, $value): void
    {
        $this->file[$offset] = $value;
    }

    /**
     * @param string $offset
     */
    #[\Override]
    public function offsetUnset($offset): void
    {
        unset($this->file[$offset]);
    }

    protected $splFile = null;

    /**
     * Constructor for the File class.
     *
     * @param array $file the path to the file
     *
     * @throws Exception
     */
    public function __construct(array $file)
    {
        $result = $this->isValidUpload($file);

        $valid = $result['valid'];
        $message = $result['message'];
        if (!$valid) {
            $this->handleError($message);
        }

        $this->file = $file;
    }

    /**
     * Checks if the uploaded file is valid.
     *
     * @param array $file the file information
     *
     * @return array Returns [valid => bool, message => string]
     */
    public function isValidUpload(array $file): array
    {
        $code = (int) $file['error'];

        return match ($code) {
            UPLOAD_ERR_OK => [
                'valid' => true,
                'message' => 'no error',
            ],
            default => [
                'valid' => false,
                'message' => $this->getMessageErrorFile($code),
            ],
        };
    }

    /**
     * Returns the name of the file.
     *
     * @return string the name of the file
     */
    public function getName(): string
    {
        return $this->file['name'] ? $this->sanitizeFileName($this->file['name']) : '';
    }

    /**
     * Returns the extension of the file.
     *
     * @return string the extension of the file
     */
    #[\Override]
    public function getExtension(): string
    {
        return pathinfo($this->file['name'], PATHINFO_EXTENSION);
    }

    /**
     * Returns the size of the file.
     *
     * @return int the size of the file
     */
    #[\Override]
    public function getSize(): int
    {
        return (int) $this->file['size'];
    }

    /**
     * Obtiene el tamaño del archivo en formato legible para los humanos.
     *
     * @return string el tamaño del archivo en formato legible
     */
    public function getSizeForHumans(): string
    {
        $tamanoEsperado = $this->getSize();
        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $tamanoEsperado > 1024; $i++) {
            $tamanoEsperado /= 1024;
        }

        return round($tamanoEsperado, 1) . ' ' . $unidades[$i];
    }

    /**
     * Returns the type of the file.
     *
     * @return string the file type
     */
    #[\Override]
    public function getType(): string
    {
        // Tipo detectado desde el contenido: el que declara el cliente no es confiable
        $tmp = (string) ($this->file['tmp_name'] ?? '');
        if ($tmp !== '' && is_file($tmp)) {
            $real = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (is_string($real) && $real !== '') {
                return $real;
            }
        }

        return (string) $this->file['type'];
    }

    public function getTmpName(): string
    {
        return $this->file['tmp_name'];
    }

    /**
     * Retrieves the metadata of the file.
     *
     * @return array the metadata of the file, including name, extension, size, and type
     */
    public function getMetadata(): array
    {
        return [
            'name' => $this->getName(),
            'extension' => $this->getExtension(),
            'size' => $this->getSize(),
            'type' => $this->getType(),
            'tmp_name' => $this->getTmpName(),
            'from' => $this->getFrom(),
        ];
    }

    /**
     * Moves the uploaded file to the specified path mantaining the original name.
     *
     * @param string $path the destination path where the file should be moved to
     *
     * @throws Exception
     *
     * @return bool returns true if the file was moved successfully, false otherwise
     */
    public function moveToOriginalName(string $path): bool
    {
        $destination = rtrim($path, '/') . '/' . $this->sanitizeFileName($this->file['name']);
        $this->assertSafeDestination($destination);
        if (!file_exists($path)) {
            mkdir($path, 0o775, true);
        }

        if (!is_writable($path)) {
            $this->handleError('El directorio no tiene permisos de escritura.');
        }

        if ($this->getFrom() === 'formData') {
            $filetmp = file_get_contents($this->getTmpName());
            if (!file_put_contents($destination, $filetmp)) {
                $this->handleError('No se pudo mover el archivo a la ruta especificada.');
            }
            chmod($destination, 0o664);
        } elseif (!move_uploaded_file($this->getTmpName(), $destination)) {
            $this->handleError('No se pudo mover el archivo a la ruta especificada.');
        } else {
            chmod($destination, 0o664);
        }

        return true;
    }

    public function moveToNewName(string $finalPath): bool
    {
        global $debug;
        // Normalizar la ruta
        $finalPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $finalPath);
        $this->assertSafeDestination($finalPath);
        $directory = dirname($finalPath);

        // Log para debugging
        if ($debug) {
            Loggin::info('moveToNewName: Intentando mover archivo', 'File', [
                'tmp_name' => $this->getTmpName(),
                'final_path' => $finalPath,
                'directory' => $directory,
                'from' => $this->getFrom(),
            ]);
        }

        // Crear directorio si no existe
        if (!file_exists($directory)) {
            if (!@mkdir($directory, 0o775, true) && !is_dir($directory)) {
                $error = error_get_last();
                $this->handleError(sprintf(
                    'No se pudo crear el directorio: %s. Error: %s',
                    $directory,
                    $error['message'] ?? 'Permisos insuficientes',
                ));
            }
            @chmod($directory, 0o775);
        }

        // Verificar que el directorio sea escribible
        if (!is_writable($directory)) {
            $perms = substr(sprintf('%o', fileperms($directory)), -4);
            $stat = @stat($directory);
            $owner = 'unknown';
            if ($stat !== false && function_exists('posix_getpwuid')) {
                $pwuid = @posix_getpwuid($stat['uid']);
                $owner = $pwuid['name'] ?? 'uid:' . $stat['uid'];
            }

            $this->handleError(sprintf(
                'El directorio no tiene permisos de escritura: %s (permisos: %s, owner: %s)',
                $directory,
                $perms,
                $owner,
            ));
        }

        // Verificar que el archivo temporal exista y sea legible
        $tmpName = $this->getTmpName();
        if (!file_exists($tmpName)) {
            $this->handleError('El archivo temporal no existe: ' . $tmpName);
        }

        if (!is_readable($tmpName)) {
            $this->handleError('El archivo temporal no es legible: ' . $tmpName);
        }

        // Mover el archivo según su origen
        $success = false;
        if ($this->getFrom() === 'formData') {
            $filetmp = file_get_contents($tmpName);
            if ($filetmp === false) {
                $this->handleError('No se pudo leer el contenido del archivo temporal.');
            }

            $success = file_put_contents($finalPath, $filetmp) !== false;
            if ($success) {
                chmod($finalPath, 0o664);
            }
            if (!$success) {
                $error = error_get_last();
                $this->handleError(sprintf(
                    'No se pudo escribir el archivo en: %s. Error: %s',
                    $finalPath,
                    $error['message'] ?? 'Error desconocido',
                ));
            }
        } else {
            $success = move_uploaded_file($tmpName, $finalPath);
            if (!$success) {
                $error = error_get_last();
                $this->handleError(sprintf(
                    'No se pudo mover el archivo a: %s. Error: %s',
                    $finalPath,
                    $error['message'] ?? 'Error desconocido',
                ));
            }
        }

        // Verificar que el archivo se haya creado correctamente
        if (!file_exists($finalPath)) {
            $this->handleError('El archivo no se creó en la ruta destino: ' . $finalPath);
        }

        // Establecer permisos del archivo
        @chmod($finalPath, 0o664);

        if ($debug) {
            Loggin::info('moveToNewName: Archivo movido exitosamente', 'File', [
                'final_path' => $finalPath,
                'size' => filesize($finalPath),
            ]);
        }

        return true;
    }

    /**
     * Returns the error message based on the error code.
     *
     * @param int $errorCode the error code from the file upload
     *
     * @return string the error message corresponding to the error code
     */
    private function getMessageErrorFile(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Subida de archivo detenida por extensión',
            default => 'Error desconocido en la subida del archivo',
        };
    }

    private function getFrom(): string
    {
        return $this->file['from'] ?? 'other';
    }

    /**
     * Sanitizes a file name by removing special or potentially dangerous characters.
     *
     * @param string $filename the file name to sanitize
     *
     * @return string the sanitized file name
     */
    private function sanitizeFileName(string $filename): string
    {
        // Sin puntos iniciales: nunca generar archivos ocultos (.htaccess, .env)
        return ltrim((string) preg_replace('/[^a-zA-Z0-9\-_.]/', '', $filename), '.');
    }

    /**
     * Ningún archivo subido puede quedar con un nombre que el servidor web ejecute o interprete.
     */
    private function assertSafeDestination(string $destination): void
    {
        $name = strtolower(basename($destination));

        if (
            $name === ''
            || str_starts_with($name, '.')
            || preg_match('/\.(php\d?|phtml|pht|phar|cgi|pl|sh|htaccess|htpasswd)(\.|$)/', $name) === 1
        ) {
            Loggin::warning('Subida bloqueada por nombre de archivo no permitido: ' . $name, 'File');

            throw new \RuntimeException('Tipo de archivo no permitido.');
        }
    }

    /**
     * Handles errors consistently based on debug mode.
     */
    private function handleError(string $message): void
    {
        Loggin::error('File Error: ' . $message, 'File');

        // Handle error according to debug mode
        global $config;
        $isDebug = isset($config['build']['debug']) && $config['build']['debug'];

        // Check if this is an API request using our improved detection
        $isApi = $this->isStaticApiCall();

        if ($isDebug) {
            // In debug mode, show detailed error
            if ($isApi) {
                Response::jsonError([
                    'success' => 0,
                    'message' => $message,
                    'error_type' => 'File Upload Error',
                ], 400);
            } else {
                throw new Exception($message);
            }
        } else {
            // In production mode, show clean error
            Loggin::error('File operation error in production: ' . $message, 'File');

            if ($isApi) {
                Response::jsonError([
                    'message' => 'Error en operación de archivo',
                    'code' => 500,
                ], 500);
                exit();
            }

            // For non-API requests, throw a clean exception that can be caught by controllers
            throw new Exception('Error en operación de archivo');
        }
    }

    /**
     * Método estático para determinar si es una llamada API sin necesidad de instancia de Request.
     * Útil para usar en contextos donde Request no está disponible.
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
