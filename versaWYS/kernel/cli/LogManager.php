<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;

class LogManager
{
    public static function handleLogs(array $params, bool $verbose = false): void
    {
        if ($params === []) {
            self::showError('Se requiere especificar una acción para logs.');
            echo CLIErrorHandler::colorize('Ejemplo: ', 'cyan') . "php versaCLI logs:clear\n";
            HelpView::printHelp();

            return;
        }

        $allowedActions = ['clear', 'list', 'view', 'tail'];
        $action = self::validateAction($params[0], $allowedActions, 'logs');

        if ($verbose) {
            CLIErrorHandler::info('Iniciando operación de logs: ' . $action);
        }

        try {
            match ($action) {
                'clear' => self::clearLogs($verbose),
                'list' => self::listLogs(),
                'view' => self::viewLog($params[1] ?? null),
                'tail' => self::tailLog($params[1] ?? null, self::linesOption($params)),
                default => self::showError('Acción de logs no reconocida: ' . $action),
            };
        } catch (Exception $exception) {
            CLIErrorHandler::handleGeneralError($exception, 'Gestionando logs: ' . $action);
        }
    }

    private static function clearLogs(bool $verbose): void
    {
        $logsDir = 'versaWYS/logs';

        if (!is_dir($logsDir)) {
            CLIErrorHandler::warning('Directorio de logs no encontrado: ' . $logsDir);

            return;
        }

        if ($verbose) {
            CLIErrorHandler::info('Limpiando logs en directorio: ' . $logsDir);
        }

        $files = glob($logsDir . '/*.log');
        $cleared = 0;

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            if (unlink($file)) {
                $cleared++;
                if ($verbose) {
                    CLIErrorHandler::info('Log eliminado: ' . basename($file));
                }
            } else {
                CLIErrorHandler::warning('No se pudo eliminar: ' . basename($file));
            }
        }

        if ($cleared > 0) {
            CLIErrorHandler::success($cleared . ' archivo(s) de log eliminados exitosamente.');
        } else {
            CLIErrorHandler::info('No se encontraron archivos de log para eliminar.');
        }
    }

    private static function listLogs(): void
    {
        $logsDir = 'versaWYS/logs';

        if (!is_dir($logsDir)) {
            CLIErrorHandler::warning('Directorio de logs no encontrado: ' . $logsDir);

            return;
        }

        echo CLIErrorHandler::colorize('📋 Archivos de Log', 'cyan') . "\n";
        echo str_repeat('═', 40) . "\n\n";

        $files = glob($logsDir . '/*.log');

        if ($files === [] || $files === false) {
            CLIErrorHandler::info('No se encontraron archivos de log.');

            return;
        }

        foreach ($files as $file) {
            $filename = basename($file);
            $size = self::formatBytes(filesize($file));
            $modified = date('Y-m-d H:i:s', filemtime($file));

            echo CLIErrorHandler::colorize('📄 ' . $filename, 'yellow') . "\n";
            echo sprintf('   Tamaño: %s%s', $size, PHP_EOL);
            echo "   Modificado: {$modified}\n\n";
        }
    }

    private static function viewLog(?string $logName): void
    {
        if (!$logName) {
            self::showError('Se requiere especificar el nombre del log.');
            echo CLIErrorHandler::colorize('Ejemplo: ', 'cyan') . "php versaCLI logs:view:error\n";

            return;
        }

        $logFile = sprintf('versaWYS/logs/%s.log', $logName);

        if (!file_exists($logFile)) {
            CLIErrorHandler::warning('Archivo de log no encontrado: ' . $logFile);

            return;
        }

        echo CLIErrorHandler::colorize(sprintf('📖 Contenido de: %s.log', $logName), 'cyan') . "\n";
        echo str_repeat('═', 50) . "\n\n";

        $content = file_get_contents($logFile);
        if (!in_array($content, ['', '0', false], true)) {
            echo $content;
        } else {
            CLIErrorHandler::info('El archivo de log está vacío.');
        }
    }

    /**
     * Lee `--lines=N` de los parámetros; 20 por defecto.
     *
     * @param array<int, string> $params
     */
    private static function linesOption(array $params): int
    {
        foreach ($params as $param) {
            if (preg_match('/^--lines=(\d+)$/', $param, $m) === 1) {
                return max(1, (int) $m[1]);
            }
        }

        return 20;
    }

    private static function tailLog(?string $logName, int $lines = 20): void
    {
        if (!$logName) {
            self::showError('Se requiere especificar el nombre del log.');
            echo CLIErrorHandler::colorize('Ejemplo: ', 'cyan') . "php versaCLI logs:tail:error\n";

            return;
        }

        $logFile = sprintf('versaWYS/logs/%s.log', $logName);

        if (!file_exists($logFile)) {
            CLIErrorHandler::warning('Archivo de log no encontrado: ' . $logFile);

            return;
        }

        echo CLIErrorHandler::colorize(sprintf('📖 Últimas %d líneas de: %s.log', $lines, $logName), 'cyan') . "\n";
        echo str_repeat('═', 50) . "\n\n";

        $fileLines = file($logFile, FILE_IGNORE_NEW_LINES);
        if ($fileLines === false) {
            CLIErrorHandler::warning('No se pudo leer el archivo de log.');

            return;
        }

        $totalLines = count($fileLines);
        $startLine = max(0, $totalLines - $lines);

        $lastLines = array_slice($fileLines, $startLine);

        foreach ($lastLines as $line) {
            echo $line . "\n";
        }

        if ($totalLines > $lines) {
            echo
                "\n"
                    . CLIErrorHandler::colorize(
                        sprintf('... mostrando las últimas %d de %d líneas totales', $lines, $totalLines),
                        'blue',
                    )
                    . "\n"
            ;
        }
    }

    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < (count($units) - 1); $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private static function validateAction(string $action, array $allowedActions, string $command): string
    {
        if (!in_array($action, $allowedActions, strict: true)) {
            self::showError(sprintf("Acción '%s' no válida para %s.", $action, $command));
            echo CLIErrorHandler::colorize('Acciones permitidas: ', 'cyan') . implode(', ', $allowedActions) . "\n";
            exit(1);
        }

        return $action;
    }

    private static function showError(string $message): void
    {
        // No termina el proceso: quien llama puede mostrar a continuación el uso correcto
        CLIErrorHandler::warning($message);
        CLIErrorHandler::$failed = true;
    }
}
