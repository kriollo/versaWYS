<?php

declare(strict_types=1);

namespace versaWYS\kernel\helpers;

class FilesFunction
{
    public static function destroyAllDir($dirPath): void
    {
        if (!is_dir($dirPath)) {
            return;
        }

        if (!str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }

        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                self::destroyAllDir($file);
            } else {
                unlink($file);
            }
        }
        if (!file_exists($dirPath)) {
            rmdir($dirPath);
        }
    }
}
