<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;
use Throwable;

/**
 * Clase para manejar errores específicos del CLI de manera clara y legible.
 */
class CLIErrorHandler
{
    // Colores ANSI para formatear mensajes
    private const COLORS = [
        'reset' => "\033[0m",
        'bold' => "\033[1m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'green' => "\033[32m",
        'cyan' => "\033[36m",
        'white' => "\033[37m",
        'bg_red' => "\033[41m",
        'bg_yellow' => "\033[43m",
    ];

    /**
     * Maneja errores de base de datos de manera amigable para CLI.
     */
    public static function handleDatabaseError(Exception $e, array $context = []): void
    {
        self::printSeparator();
        self::printError('ERROR DE BASE DE DATOS');

        // Extraer información útil del error
        $message = $e->getMessage();
        $sqlError = self::extractSQLError($message);

        echo self::colorize('Archivo: ', 'cyan') . ($context['file'] ?? 'Desconocido') . "\n";
        echo self::colorize('Línea: ', 'cyan') . ($context['line'] ?? 'Desconocida') . "\n";
        echo self::colorize('Operación: ', 'cyan') . ($context['operation'] ?? 'Desconocida') . "\n";
        echo "\n";

        // Mostrar el error SQL de manera clara
        if ($sqlError['code']) {
            echo self::colorize('Código SQL: ', 'yellow') . $sqlError['code'] . "\n";
        }

        echo self::colorize('Mensaje: ', 'yellow') . $sqlError['message'] . "\n";

        // Sugerencias específicas basadas en el tipo de error
        self::showSQLSuggestions($sqlError);

        // Mostrar la consulta SQL si está disponible
        if (isset($context['query'])) {
            echo "\n" . self::colorize('Consulta SQL:', 'cyan') . "\n";
            echo self::formatSQL($context['query']) . "\n";
        }

        self::printSeparator();
        exit(1);
    }

    /**
     * Maneja errores de resultado de migración (cuando el método up/down retorna success=false).
     * A diferencia de handleMigrationError, recibe un string en lugar de una excepción.
     */
    public static function handleMigrationResultError(string $message, string $migrationFile, string $method): void
    {
        self::printSeparator();
        self::printError('ERROR EN MIGRACIÓN');

        echo self::colorize('Migración: ', 'cyan') . $migrationFile . "\n";
        echo self::colorize('Método:    ', 'cyan') . $method . "()\n";
        echo "\n";

        $sqlError = self::extractSQLError($message);

        if ($sqlError['code']) {
            echo self::colorize('Código SQLSTATE: ', 'yellow') . $sqlError['code'] . "\n";
        }

        echo self::colorize('Error: ', 'yellow') . $sqlError['message'] . "\n";

        // Extraer y mostrar el SQL que falló si está disponible en el mensaje
        if (preg_match('/SQL failed \(raw\): (.+?)(?:\s*\|\s*Bindings|\n|$)/s', $message, $matches)) {
            echo "\n" . self::colorize('SQL fallido:', 'cyan') . "\n";
            echo self::formatSQL(trim($matches[1])) . "\n";

            if (preg_match('/Bindings: \[(.+?)\]/', $message, $bindMatches)) {
                echo self::colorize('Bindings: ', 'cyan') . '[' . $bindMatches[1] . "]\n";
            }
        }

        self::showSQLSuggestions($sqlError);

        self::printSeparator();
        exit(1);
    }

    /**
     * Maneja errores de migración.
     */
    public static function handleMigrationError(Exception $e, string $migrationFile): void
    {
        self::printSeparator();
        self::printError('ERROR EN MIGRACIÓN');

        echo self::colorize('Archivo de migración: ', 'cyan') . $migrationFile . "\n";
        echo self::colorize('Error: ', 'yellow') . $e->getMessage() . "\n";
        echo self::colorize('Archivo: ', 'cyan') . $e->getFile() . "\n";
        echo self::colorize('Línea: ', 'cyan') . $e->getLine() . "\n";
        echo "\n";

        echo self::colorize('Sugerencias:', 'green') . "\n";
        echo "• Verifica la sintaxis SQL en el archivo de migración\n";
        echo "• Asegúrate de que las tablas/columnas referenciadas existan\n";
        echo "• Revisa los tipos de datos y longitudes de campos\n";
        echo "• Considera ejecutar 'migrate:rollback' si necesitas revertir\n";
        echo "\n";

        echo self::colorize('Rastreo: ', 'cyan') . "\n";
        echo $e->getTraceAsString() . "\n";

        self::printSeparator();
        exit(1);
    }

    /**
     * Maneja errores generales del CLI.
     */
    public static function handleGeneralError(Throwable $e, string $context = ''): void
    {
        self::printSeparator();
        self::printError('ERROR');

        if ($context !== '' && $context !== '0') {
            echo self::colorize('Contexto: ', 'cyan') . $context . "\n";
        }

        echo self::colorize('Mensaje: ', 'yellow') . $e->getMessage() . "\n";
        echo self::colorize('Archivo: ', 'cyan') . $e->getFile() . "\n";
        echo self::colorize('Línea: ', 'cyan') . $e->getLine() . "\n";
        echo " \n";
        echo self::colorize('Rastreo: ', 'cyan') . "\n";
        echo $e->getTraceAsString() . "\n";

        self::printSeparator();
        exit(1);
    }

    /**
     * Muestra un mensaje de éxito.
     */
    public static function success(string $message): void
    {
        echo self::colorize('✓ ', 'green') . $message . "\n";
    }

    /**
     * Muestra un mensaje de información.
     */
    public static function info(string $message): void
    {
        echo self::colorize('ℹ ', 'cyan') . $message . "\n";
    }

    /**
     * Muestra un mensaje de advertencia.
     */
    /** Algún comando reportó un error: versaCLI termina con código de salida 1 */
    public static bool $failed = false;

    public static function warning(string $message): void
    {
        echo self::colorize('⚠ ', 'yellow') . $message . "\n";
    }

    public static function error(string $message): void
    {
        echo self::colorize('✗ ', 'red') . $message . "\n";
    }

    /**
     * Aplica colores a un texto.
     */
    public static function colorize(string $text, string $color): string
    {
        $colorCode = self::COLORS[$color] ?? '';
        $reset = self::COLORS['reset'];

        return $colorCode . $text . $reset;
    }

    /**
     * Imprime un separador visual.
     */
    public static function printSeparator(): void
    {
        $red = self::COLORS['red'];
        $reset = self::COLORS['reset'];
        echo PHP_EOL . $red . str_repeat('═', 60) . ($reset . PHP_EOL);
    }

    /**
     * Extrae información útil del error SQL.
     */
    private static function extractSQLError(string $message): array
    {
        $result = ['code' => '', 'message' => $message];

        // Extraer código SQLSTATE
        if (preg_match('/SQLSTATE\[(\w+)\]/', $message, $matches)) {
            $result['code'] = $matches[1];
        }

        // Extraer mensaje limpio
        if (preg_match('/SQLSTATE\[\w+\]:\s*(.+)/', $message, $matches)) {
            $result['message'] = trim($matches[1]);
        }

        return $result;
    }

    /**
     * Muestra sugerencias específicas basadas en errores SQL comunes.
     */
    private static function showSQLSuggestions(array $sqlError): void
    {
        $suggestions = [];
        $message = strtolower($sqlError['message']);

        if (str_contains($message, 'key was too long')) {
            $suggestions[] = 'Reduce la longitud de los campos indexados (especialmente VARCHAR)';
            $suggestions[] = 'Considera usar VARCHAR(191) en lugar de VARCHAR(255) para UTF8MB4';
            $suggestions[] = 'Revisa la configuración de charset de tu base de datos';
        }

        if (str_contains($message, 'syntax error')) {
            $suggestions[] = 'Verifica la sintaxis SQL de tu migración';
            $suggestions[] = 'Asegúrate de cerrar correctamente paréntesis y comillas';
            $suggestions[] = 'Revisa que los nombres de tablas/columnas sean válidos';
        }

        if (str_contains($message, 'table') && str_contains($message, 'exists')) {
            $suggestions[] = 'La tabla ya existe o no existe según la operación';
            $suggestions[] = 'Usa IF EXISTS o IF NOT EXISTS según corresponda';
            $suggestions[] = 'Verifica el estado actual de tu base de datos';
        }

        if (str_contains($message, 'foreign key')) {
            $suggestions[] = 'Verifica que la tabla referenciada exista';
            $suggestions[] = 'Asegúrate de que el campo referenciado sea una clave primaria o tenga un índice único';
            $suggestions[] = 'Revisa los tipos de datos de las columnas relacionadas';
        }

        if ($suggestions !== []) {
            echo "\n" . self::colorize('Sugerencias:', 'green') . "\n";
            foreach ($suggestions as $suggestion) {
                echo sprintf('• %s%s', $suggestion, PHP_EOL);
            }
        }
    }

    /**
     * Formatea una consulta SQL para mejor legibilidad.
     */
    private static function formatSQL(string $sql): string
    {
        // Reemplazar saltos de línea y espacios múltiples
        $sql = preg_replace('/\s+/', ' ', $sql);
        $sql = str_replace(['\r\n', '\r', '\n'], ' ', $sql);

        // Agregar saltos de línea después de palabras clave principales
        $keywords = ['SELECT', 'FROM', 'WHERE', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'ALTER', 'DROP'];
        foreach ($keywords as $keyword) {
            $sql = preg_replace('/\b' . $keyword . '\b/i', PHP_EOL . $keyword, $sql);
        }

        return trim($sql);
    }

    /**
     * Imprime un mensaje de error con formato.
     */
    private static function printError(string $title): void
    {
        $bg = self::COLORS['bg_red'];
        $white = self::COLORS['white'];
        $bold = self::COLORS['bold'];
        $reset = self::COLORS['reset'];

        echo "\n{$bg}{$white}{$bold} ✗ {$title} {$reset}\n\n";
    }
}
