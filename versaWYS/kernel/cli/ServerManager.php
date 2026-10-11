<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class ServerManager
{
    public static function handleServe(bool $verbose = false): void
    {
        // ✅ VERIFICAR SI EL PUERTO 8000 ESTÁ EN USO
        if (!self::isPortAvailable(8000)) {
            CLIErrorHandler::error('❌ ERROR: El puerto 8000 ya está siendo utilizado.');
            echo "\n";

            // Intentar obtener información del proceso que usa el puerto
            $processInfo = self::getProcessUsingPort(8000);
            if ($processInfo !== null) {
                echo "📌 Proceso usando el puerto:\n";
                echo '   PID: ' . CLIErrorHandler::colorize((string) $processInfo['pid'], 'yellow') . "\n";
                if (isset($processInfo['name']) && $processInfo['name'] !== null) {
                    echo '   Proceso: ' . CLIErrorHandler::colorize($processInfo['name'], 'cyan') . "\n";
                }
                echo "\n";
                echo "💡 Para liberar el puerto, puedes:\n";

                if (PHP_OS_FAMILY === 'Windows') {
                    echo "   1. Detener el proceso desde el Administrador de Tareas\n";
                    echo
                        '   2. Ejecutar: '
                            . CLIErrorHandler::colorize("taskkill /PID {$processInfo['pid']} /F", 'yellow')
                            . "\n"
                    ;
                } else {
                    echo '   1. Ejecutar: ' . CLIErrorHandler::colorize("kill {$processInfo['pid']}", 'yellow') . "\n";
                    echo
                        '   2. O con fuerza: '
                            . CLIErrorHandler::colorize("kill -9 {$processInfo['pid']}", 'yellow')
                            . "\n"
                    ;
                }

                echo "   3. Usar otro puerto modificando el comando en ServerManager.php\n";
            } else {
                echo "💡 Verifica que no haya otro servidor corriendo en el puerto 8000.\n";
                if (PHP_OS_FAMILY === 'Windows') {
                    echo
                        '   Puedes verificar con: '
                            . CLIErrorHandler::colorize('netstat -ano | findstr :8000', 'yellow')
                            . "\n"
                    ;
                } else {
                    echo '   Puedes verificar con: ' . CLIErrorHandler::colorize('lsof -i :8000', 'yellow') . "\n";
                }
            }

            exit(1);
        }

        echo "🚀 Iniciando servidor de desarrollo en localhost:8000...\n";
        echo '🌐 URL: ' . CLIErrorHandler::colorize('http://localhost:8000', 'cyan') . "\n";
        echo "⏹️  Presiona Ctrl+C para detener el servidor\n\n";

        // Cambiar al directorio raíz del proyecto para el servidor
        $rootDir = getcwd();
        $publicDir = './';

        // Verificar si existe index.php
        if (!file_exists($publicDir . '/index.php')) {
            CLIErrorHandler::warning('No se encontró index.php en el directorio actual.');
            echo "Asegúrate de ejecutar el comando desde el directorio raíz del proyecto.\n";
            exit(1);
        }

        // Verificar si existe routerServe.php
        $routerScript = __DIR__ . '/routerServe.php';
        if (!file_exists($routerScript)) {
            CLIErrorHandler::warning('No se encontró routerServe.php en ' . __DIR__);
            echo "Este archivo es necesario para el servidor de desarrollo.\n";
            exit(1);
        }

        // Comando para iniciar el servidor con router script
        $command = sprintf('php -S localhost:8000 -t %s %s', escapeshellarg($publicDir), escapeshellarg($routerScript));

        if ($verbose) {
            CLIErrorHandler::info('Ejecutando: ' . $command);
            CLIErrorHandler::info('Directorio raíz: ' . $publicDir);
            CLIErrorHandler::info('Router script: ' . $routerScript);
        }

        // Ejecutar el servidor
        passthru($command);
    }

    /**
     * Detecta qué ejecutable de PowerShell está disponible
     */
    private static function getPowerShellExecutable(): ?string
    {
        // Probar PowerShell 7 (pwsh)
        $testPwsh = @shell_exec('pwsh -NoProfile -Command "Write-Output test" 2>nul');
        if ($testPwsh !== null && str_contains(trim($testPwsh), 'test')) {
            return 'pwsh';
        }

        // Probar Windows PowerShell (powershell)
        $testPs = @shell_exec('powershell -NoProfile -Command "Write-Output test" 2>nul');
        if ($testPs !== null && str_contains(trim($testPs), 'test')) {
            return 'powershell';
        }

        return null;
    }

    /**
     * Verifica si un puerto está disponible
     */
    private static function isPortAvailable(int $port): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Usar netstat con ruta completa para evitar dependencias de PATH o shell
            $command = "C:\\Windows\\System32\\netstat.exe -ano";
            $output = @shell_exec($command);

            if ($output === null || trim($output) === '') {
                // Fallback: usar socket
                return self::checkPortWithSocket($port);
            }

            // Buscar el puerto específico en estado LISTENING
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                // El patrón busca ":8000 " o similar para evitar matchear ":80001"
                // Y verifica que la línea contenga LISTENING o ESCUCHANDO
                if (
                    !(
                        (str_contains($line, ":{$port} ") || str_contains($line, ":{$port}\t"))
                        && (stripos($line, 'LISTENING') !== false || stripos($line, 'ESCUCHANDO') !== false)
                    )
                ) {
                    continue;
                }

                return false;
            }

            return true; // Puerto libre
        }

        // Para Linux/Mac
        $command = "netstat -tuln 2>/dev/null | grep :{$port} || ss -tuln 2>/dev/null | grep :{$port}";
        $output = @shell_exec($command);

        if ($output === null) {
            return self::checkPortWithSocket($port);
        }

        return trim($output) === '';
    }

    /**
     * Método alternativo usando sockets (funciona en todos los sistemas)
     */
    private static function checkPortWithSocket(int $port): bool
    {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            return true; // Si no podemos crear socket, asumimos disponible
        }

        $result = @socket_bind($socket, '127.0.0.1', $port);
        socket_close($socket);

        return $result !== false;
    }

    /**
     * Obtiene información del proceso usando un puerto
     */
    private static function getProcessUsingPort(int $port): ?array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            // En Linux/Mac
            $commands = [
                "lsof -i :{$port} -t 2>/dev/null",
                "ss -lptn 2>/dev/null | grep :{$port}",
                "netstat -tulpn 2>/dev/null | grep :{$port}",
            ];

            foreach ($commands as $command) {
                $output = shell_exec($command);
                if ($output !== null && trim($output) !== '') {
                    $pid = (int) trim(explode("\n", $output)[0]);
                    if ($pid > 0) {
                        return ['pid' => $pid, 'name' => null];
                    }
                }
            }

            return null;
        }

        // En Windows - usar netstat primero (siempre disponible)
        $command = "C:\\Windows\\System32\\netstat.exe -ano";
        $output = @shell_exec($command);

        if ($output === null || trim($output) === '') {
            return null;
        }

        // Buscar la línea con el puerto en LISTENING
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (
                !(
                    (stripos($line, ":{$port}") !== false || stripos($line, ":{$port} ") !== false)
                    && (stripos($line, 'LISTENING') !== false || stripos($line, 'ESCUCHANDO') !== false)
                )
            ) {
                continue;
            }

            $parts = preg_split('/\s+/', trim($line));
            $pid = (int) end($parts);

            if ($pid > 0) {
                // Intentar obtener el nombre del proceso
                $processName = null;

                // Primero intentar con PowerShell si está disponible
                $psExe = self::getPowerShellExecutable();
                if ($psExe !== null) {
                    $psCommand = "{$psExe} -NoProfile -Command \"(Get-Process -Id {$pid} -ErrorAction SilentlyContinue).ProcessName\"";
                    $psOutput = @shell_exec($psCommand);
                    if ($psOutput !== null && trim($psOutput) !== '') {
                        $processName = trim($psOutput);
                    }
                }

                // Fallback a tasklist si PowerShell no funcionó
                if ($processName === null) {
                    $tasklistCommand = "C:\\Windows\\System32\\tasklist.exe /FI \"PID eq {$pid}\" /NH /FO CSV";
                    $tasklistOutput = @shell_exec($tasklistCommand);

                    if ($tasklistOutput !== null && trim($tasklistOutput) !== '') {
                        if (preg_match('/"([^"]+)"/', $tasklistOutput, $matches)) {
                            $processName = $matches[1];
                        }
                    }
                }

                return [
                    'pid' => $pid,
                    'name' => $processName,
                ];
            }
        }

        return null;
    }
}
