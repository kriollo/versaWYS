<?php

declare(strict_types=1);

namespace versaWYS\kernel\Helpers;

class FilesFunction
{
    public static function DestroyAllDir($dirPath)
    {
        if (!is_dir($dirPath)) {
            echo "Error: No existe el directorio: " . $dirPath . "\n";
            exit;
        }

        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::DestroyAllDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}
