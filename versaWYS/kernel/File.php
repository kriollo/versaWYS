<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use Exception;
use SplFileInfo;
use versaWYS\kernel\helpers\Functions;

class File extends SplFileInfo
{
    protected array $file;

    protected $splFile = null;

    /**
     * Constructor for the File class.
     *
     * @param array $file The path to the file.
     * @throws Exception
     */
    public function __construct(array $file)
    {
        $result = $this->isValidUpload($file);

        $valid = $result['valid'];
        $message = $result['message'];
        if (!$valid) {
            throw new Exception($message);
        }

        $this->file = $file;
    }

    /**
     * Checks if the uploaded file is valid.
     *
     * @param array $file The file information.
     * @return array Returns [valid => bool, message => string]
     */
    private function isValidUpload(array $file): array
    {
        $erros = [
            0 => 'No hay error, el fichero se subió con éxito.',
            1 => 'El tamaño del fichero supera la directiva upload_max_filesize de php.ini.',
            2 => 'El tamaño del fichero supera la directiva MAX_FILE_SIZE especificada en el formulario HTML.',
            3 => 'El fichero fue parcialmente subido.',
            4 => 'No se ha subido un fichero.',
            6 => 'No existe un directorio temporal.',
            7 => 'Fallo al escribir el fichero en el disco.',
            8 => 'La subida del fichero fue detenida por la extensión.',
        ];

        if ($file['error'] == UPLOAD_ERR_OK) {
            return [
                'valid' => true,
                'message' => 'no error',
            ];
        } else {
            return [
                'valid' => false,
                'message' => $erros[$file['error']],
            ];
        }
    }

    /**
     * Returns the name of the file.
     *
     * @return string The name of the file.
     */
    public function getName(): string
    {
        return $this->sanitizeFileName($this->file['name']);
    }

    /**
     * Returns the extension of the file.
     *
     * @return string The extension of the file.
     */
    public function getExtension(): string
    {
        return pathinfo($this->file['name'], PATHINFO_EXTENSION);
    }

    /**
     * Returns the size of the file.
     *
     * @return int The size of the file.
     */
    public function getSize(): int
    {
        return (int) $this->file['size'];
    }

    /**
     * Obtiene el tamaño del archivo en formato legible para los humanos.
     *
     * @return string El tamaño del archivo en formato legible.
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
     * @return string The file type.
     */
    public function getType(): string
    {
        return $this->file['type'];
    }

    public function getTmpName(): string
    {
        return $this->file['tmp_name'];
    }

    private function getFrom(): string
    {
        return $this->file['from'] ?? 'other';
    }

    /**
     * Retrieves the metadata of the file.
     *
     * @return array The metadata of the file, including name, extension, size, and type.
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
     * Sanitizes a file name by removing special or potentially dangerous characters.
     *
     * @param string $filename The file name to sanitize.
     * @return string The sanitized file name.
     */
    private function sanitizeFileName(string $filename): string
    {
        return preg_replace('/[^a-zA-Z0-9\-_.]/', '', $filename);
    }

    /**
     * Moves the uploaded file to the specified path mantaining the original name.
     *
     * @param string $path The destination path where the file should be moved to.
     * @return bool Returns true if the file was moved successfully, false otherwise.
     * @throws Exception
     */
    public function moveToOriginalName(string $path): bool
    {
        $destination = rtrim($path, '/') . '/' . $this->sanitizeFileName($this->file['name']);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        if (!is_writable($path)) {
            throw new Exception('El directorio no tiene permisos de escritura.');
        }

        if ($this->getFrom() == 'formData') {
            $filetmp = file_get_contents($this->getTmpName());
            if (!file_put_contents($destination, $filetmp)) {
                throw new Exception('No se pudo mover el archivo a la ruta especificada.');
            }
        } else {
            if (!move_uploaded_file($this->getTmpName(), $destination)) {
                throw new Exception('No se pudo mover el archivo a la ruta especificada.');
            }
        }

        return true;
    }
    public function moveToNewName(string $finalPath): bool
    {
        $path = $this->sanitizeFileName($finalPath);
        if (!file_exists(dirname($path))) {
            mkdir($finalPath, 0777, true);
        }

        if (!is_writable(dirname($path))) {
            throw new Exception('El directorio no tiene permisos de escritura.');
        }

        if ($this->getFrom() == 'formData') {
            $filetmp = file_get_contents($this->getTmpName());
            if (!file_put_contents($finalPath, $filetmp)) {
                throw new Exception('No se pudo mover el archivo a la ruta especificada.');
            }
        } else {
            if (!move_uploaded_file($this->getTmpName(), $finalPath)) {
                throw new Exception('No se pudo mover el archivo a la ruta especificada.');
            }
        }
        return true;
    }
}
