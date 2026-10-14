<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use ReflectionClass;
use versaWYS\kernel\Loggin;

/**
 * Registro y descubrimiento automático de comandos de aplicación.
 *
 * Escanea el directorio app/commands/ y registra comandos que implementen CommandInterface.
 */
class CommandRegistry
{
    private const APP_COMMANDS_DIR = 'app/commands';

    private const CACHE_FILE = 'versaWYS/cache/commands.php';

    /**
     * Descubre todos los comandos en app/commands/.
     *
     * @return array Array asociativo [nombre_comando => clase_completa]
     */
    public static function discoverCommands(): array
    {
        $commands = [];

        $dirs = [
            ['path' => self::APP_COMMANDS_DIR, 'namespace' => 'app\\commands\\', 'kernel' => false],
            // Junto a esta clase: el kernel puede vivir en vendor
            ['path' => __DIR__ . '/commands', 'namespace' => 'versaWYS\\kernel\\cli\\commands\\', 'kernel' => true],
        ];

        foreach ($dirs as $dirInfo) {
            $commandsPath = $dirInfo['path'] . '/*.php';
            $files = glob($commandsPath);
            if ($files === false) {
                continue;
            }

            foreach ($files as $file) {
                $className = basename($file, '.php');
                $fullClassName = $dirInfo['namespace'] . $className;

                if (!class_exists($fullClassName)) {
                    continue;
                }

                try {
                    $reflection = new ReflectionClass($fullClassName);

                    if (!$reflection->implementsInterface(CommandInterface::class)) {
                        continue;
                    }

                    if ($reflection->isAbstract()) {
                        continue;
                    }

                    $commandName = self::classNameToCommandName($className);
                    // If command name collides, prefer app command over kernel
                    if (isset($commands[$commandName]) && $dirInfo['kernel']) {
                        continue;
                    }

                    $commands[$commandName] = $fullClassName;
                } catch (\Throwable $e) {
                    Loggin::warning("No se pudo registrar comando: {$className}", 'CommandRegistry', [
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }
        }

        return $commands;
    }

    /**
     * Obtiene los comandos desde cache o los descubre si es necesario.
     *
     * @param bool $forceRefresh Forzar redescubrimiento ignorando cache
     * @return array Array asociativo [nombre_comando => clase_completa]
     */
    public static function getCachedCommands(bool $forceRefresh = false): array
    {
        $cacheFile = self::CACHE_FILE;

        // Si existe cache válido y no se fuerza refresh, usarlo
        if (!$forceRefresh && file_exists($cacheFile)) {
            $cached = include $cacheFile;
            if (is_array($cached) && isset($cached['commands'])) {
                return $cached['commands'];
            }
        }

        // Descubrir comandos y cachear
        $commands = self::discoverCommands();
        self::cacheCommands($commands);

        return $commands;
    }

    /**
     * Limpia el cache de comandos.
     */
    public static function clearCache(): void
    {
        $cacheFile = self::CACHE_FILE;

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            CLIErrorHandler::success('Cache de comandos limpiado');
        } else {
            CLIErrorHandler::info('No hay cache de comandos para limpiar');
        }
    }

    /**
     * Convierte nombre de clase a nombre de comando CLI.
     *
     * Ejemplos:
     * - ProcessVideoCommand -> video:process
     * - EmailSendCommand -> email:send
     * - ReportGenerateMonthlyCommand -> report:generate:monthly
     *
     * @param string $className Nombre de la clase (sin namespace)
     * @return string Nombre del comando en formato modulo:accion[:subaccion]
     */
    private static function classNameToCommandName(string $className): string
    {
        // Remover sufijo "Command"
        $name = preg_replace('/Command$/', '', $className);

        // Separar palabras en CamelCase
        $parts = preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 0) {
            return strtolower($className);
        }

        // Convertir a snake_case y unir con ":"
        $parts = array_map('strtolower', $parts);

        return implode(':', $parts);
    }

    /**
     * Guarda los comandos en cache.
     *
     * @param array $commands Array de comandos descubiertos
     */
    private static function cacheCommands(array $commands): void
    {
        $cacheDir = dirname(self::CACHE_FILE);
        CLIHelper::ensureDirectory($cacheDir);

        $content = "<?php\n\n";
        $content .= "declare(strict_types=1);\n\n";
        $content .= "// Cache de comandos CLI de aplicación\n";
        $content .= '// Generado automáticamente el ' . date('Y-m-d H:i:s') . "\n\n";
        $content .= "return [\n";
        $content .= "    'generated_at' => '" . date('Y-m-d H:i:s') . "',\n";
        $content .= "    'commands' => [\n";

        foreach ($commands as $name => $class) {
            $content .= "        '{$name}' => '{$class}',\n";
        }

        $content .= "    ],\n";
        $content .= "];\n";

        file_put_contents(self::CACHE_FILE, $content);
        chmod(self::CACHE_FILE, 0o775);
    }

    /**
     * Obtiene información de un comando específico.
     *
     * @param string $commandName Nombre del comando
     * @return array|null Array con 'class', 'description', 'help' o null si no existe
     */
    public static function getCommandInfo(string $commandName): ?array
    {
        $commands = self::getCachedCommands();

        if (!isset($commands[$commandName])) {
            return null;
        }

        $className = $commands[$commandName];

        if (!class_exists($className)) {
            return null;
        }

        try {
            $instance = new $className();
            if (!$instance instanceof CommandInterface) {
                return null;
            }

            return [
                'class' => $className,
                'description' => $instance->getDescription(),
                'help' => $instance->getHelp(),
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }
}
