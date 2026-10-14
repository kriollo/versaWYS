<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use versaWYS\kernel\Loggin;

/**
 * Ejecutor de comandos CLI.
 *
 * Facilita la ejecución de comandos desde código PHP, tanto sincrónicos como asíncronos.
 */
class CommandExecutor
{
    /**
     * Ejecuta un comando de aplicación en background (asíncrono).
     *
     * @param string $command Nombre del comando (ej: 'video:process', 'email:send')
     * @param array $params Parámetros del comando
     * @param bool $logOutput Si es true, captura y registra la salida del comando
     * @param string|null $logModule Módulo para logging (por defecto: 'background-cli')
     *
     * @return void
     */
    public static function runInBackground(
        string $command,
        array $params = [],
        bool $logOutput = false,
        ?string $logModule = 'background-cli',
    ): void {
        $cliPath = self::getCliPath();
        $paramsString = self::buildParamsString($params);
        $fullCommand = sprintf(
            '%s %s %s %s',
            'php',
            escapeshellarg($cliPath),
            escapeshellarg('app:' . $command),
            $paramsString,
        );

        self::executeBackgroundCommand($fullCommand, $logOutput, $logModule);
    }

    /**
     * Ejecuta un comando de aplicación de forma síncrona.
     *
     * @param string $command Nombre del comando (ej: 'video:process', 'email:send')
     * @param array $params Parámetros del comando
     *
     * @return array Array con 'exitCode', 'output', 'success'
     */
    public static function run(string $command, array $params = []): array
    {
        $cliPath = self::getCliPath();
        $paramsString = self::buildParamsString($params);
        $fullCommand = sprintf(
            '%s %s %s %s',
            'php',
            escapeshellarg($cliPath),
            escapeshellarg('app:' . $command),
            $paramsString,
        );

        $output = [];
        $exitCode = 0;

        exec($fullCommand . ' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'exitCode' => $exitCode,
            'output' => implode("\n", $output),
        ];
    }

    /**
     * Construye la cadena de parámetros para el comando CLI.
     * Serializa arrays y objetos a JSON automáticamente.
     *
     * @param array $params Parámetros del comando
     *
     * @return string Parámetros escapados y listos para CLI
     */
    private static function buildParamsString(array $params): string
    {
        $serializedParams = array_map(
            static function ($param) {
                // Si es un array u objeto, serializar a JSON
                if (is_array($param) || is_object($param)) {
                    return json_encode($param, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                // Si es null, convertir a string vacío
                if ($param === null) {
                    return '';
                }

                // Si es bool, convertir a '1' o '0'
                if (is_bool($param)) {
                    return $param ? '1' : '0';
                }

                // Para escalares (string, int, float), convertir a string
                return (string) $param;
            },
            $params,
        );

        // Escapar cada parámetro para shell
        return implode(' ', array_map('escapeshellarg', $serializedParams));
    }

    /**
     * Obtiene la ruta absoluta al script versaCLI.
     *
     * @return string Ruta absoluta al archivo versaCLI
     */
    private static function getCliPath(): string
    {
        // El directorio de trabajo es la raíz del proyecto (web y CLI)
        $cliPath = realpath(getcwd() . '/versaCLI');

        if ($cliPath !== false && file_exists($cliPath)) {
            return $cliPath;
        }

        // Fallback: intentar desde DOCUMENT_ROOT si está disponible
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $cliPath = realpath($_SERVER['DOCUMENT_ROOT'] . '/../versaCLI');

            if ($cliPath !== false && file_exists($cliPath)) {
                return $cliPath;
            }
        }

        // Si no se encuentra, lanzar excepción
        throw new \RuntimeException('No se pudo localizar el archivo versaCLI desde ' . getcwd());
    }

    /**
     * Ejecuta un comando shell en background.
     *
     * @param string $command Comando completo a ejecutar
     * @param bool $logOutput Si es true, captura y registra la salida del comando
     * @param string|null $logModule Módulo para logging
     */
    private static function executeBackgroundCommand(
        string $command,
        bool $logOutput = false,
        ?string $logModule = 'background-cli',
    ): void {
        // Registrar la ejecución del comando
        if ($logOutput) {
            Loggin::info("Ejecutando comando en background: {$command}", $logModule);
        }

        if (PHP_OS_FAMILY === 'Windows') {
            if ($logOutput) {
                // En Windows, redirigir a un archivo temporal
                $logFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cli_' . uniqid() . '.log';
                // < NUL: desconecta stdin del proceso hijo para evitar bloqueos
                $fullCommand = sprintf('start "" /B %s < NUL > "%s" 2>&1', $command, $logFile);

                pclose(popen($fullCommand, 'r'));

                // Registrar que se creó el log file
                Loggin::debug("Output redirigido a: {$logFile}", $logModule);
            } else {
                // < NUL: desconecta stdin del proceso hijo para evitar bloqueos
                pclose(popen('start "" /B ' . $command . ' < NUL > NUL 2>&1', 'r'));
            }
        } else {
            // Linux/Mac
            if ($logOutput) {
                $logFile = (string) tempnam(sys_get_temp_dir(), 'cli_');
                $fullCommand = sprintf('%s > %s 2>&1 &', $command, escapeshellarg($logFile));

                exec($fullCommand);

                Loggin::debug("Output redirigido a: {$logFile}", $logModule);
            } else {
                exec($command . ' > /dev/null 2>&1 &');
            }
        }
    }

    /**
     * Ejecuta cualquier comando del sistema (no solo comandos app:).
     *
     * @param string $module Módulo del comando (ej: 'migrate', 'cache', 'cron')
     * @param string $action Acción a ejecutar (ej: 'up', 'clear', 'run')
     * @param array $params Parámetros adicionales
     * @param bool $async Si es true, ejecuta en background
     *
     * @return array|void Array con resultado si es síncrono, void si es asíncrono
     */
    public static function executeSystemCommand(
        string $module,
        string $action = '',
        array $params = [],
        bool $async = false,
    ): mixed {
        $cliPath = self::getCliPath();
        $commandParts = array_filter([$module, $action, ...$params]);
        $commandString = implode(':', $commandParts);
        $fullCommand = sprintf(
            '%s %s %s',
            'php',
            escapeshellarg($cliPath),
            escapeshellarg($commandString),
        );

        if ($async) {
            self::executeBackgroundCommand($fullCommand);

            return null;
        }

        $output = [];
        $exitCode = 0;

        exec($fullCommand . ' 2>&1', $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'exitCode' => $exitCode,
            'output' => implode("\n", $output),
        ];
    }
}
