<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use RuntimeException;

class CLIHelper
{
    /**
     * Ask a question and return the user's input.
     */
    public static function ask(string $question, string $default = ''): string
    {
        echo CLIErrorHandler::colorize($question, 'green');
        if ($default !== '') {
            echo CLIErrorHandler::colorize(" [{$default}]", 'yellow');
        }
        echo ': ';

        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        fclose($handle);

        $input = trim((string) $line);

        if ($input === '' && $default !== '') {
            return $default;
        }

        return $input;
    }

    /**
     * Ask for confirmation (yes/no).
     */
    public static function confirm(string $question, bool $default = false): bool
    {
        $defaultStr = $default ? 'y' : 'n';
        $prompt = "{$question} (y/n)";

        $input = strtolower(self::ask($prompt, $defaultStr));

        return in_array($input, ['y', 'yes', 'si', 's'], true);
    }

    /**
     * Ensure a directory exists.
     */
    public static function ensureDirectory(string $path, int $permissions = 0o775): void
    {
        if (!is_dir($path)) {
            if (!mkdir($path, $permissions, true) && !is_dir($path)) {
                throw new RuntimeException("No se pudo crear el directorio: {$path}");
            }
        }
    }
}
