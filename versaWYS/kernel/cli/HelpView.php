<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class HelpView
{
    public static function printHelp(): void
    {
        // Colores y estilos ANSI
        $reset = "\033[0m";
        $bold = "\033[1m";
        $cyan = "\033[36m";
        $yellow = "\033[33m";
        $green = "\033[32m";
        $blue = "\033[34m";

        echo "\n";
        echo
            sprintf(
                '%s%s╔══════════════════════════════════════════════════════════════╗%s%s',
                $bold,
                $cyan,
                $reset,
                PHP_EOL,
            )
        ;
        echo
            sprintf(
                '%s%s║                        versaWYS CLI                          ║%s%s',
                $bold,
                $cyan,
                $reset,
                PHP_EOL,
            )
        ;
        echo
            sprintf(
                '%s%s╚══════════════════════════════════════════════════════════════╝%s%s',
                $bold,
                $cyan,
                $reset,
                PHP_EOL,
            )
        ;
        echo "\n";
        echo "{$bold}{$blue}Uso:{$reset} php versaCLI [comando] [opciones]\n";
        echo "\n";
        echo sprintf('%s%sOpciones globales:%s%s', $bold, $blue, $reset, PHP_EOL);
        echo "  {$yellow}--verbose, -v{$reset}  Mostrar información detallada del comando\n";
        echo "  {$yellow}--force{$reset}        Omitir la confirmación de comandos destructivos\n";
        echo "\n";
        echo sprintf('%s%sAtajos rápidos:%s%s', $bold, $blue, $reset, PHP_EOL);
        echo "  {$yellow}RCMD:[nombre]{$reset}              Crear ruta, controlador, modelo y middleware\n";
        echo "  {$yellow}versaMODULE:twig:[nombre]{$reset}  Crear módulo completo con template Twig\n";
        echo "  {$yellow}versaMODULE:vue:[nombre]{$reset}   Crear módulo completo con componente Vue\n";
        echo "\n";
        echo sprintf('%s%sComandos disponibles:%s%s', $bold, $blue, $reset, PHP_EOL);
        echo "\n";
        echo sprintf('%s%s🚀 Servidor%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}serve{$reset}                     Servidor de desarrollo en localhost:8000 (--port=N --host=H)\n";
        echo "\n";
        echo sprintf('%s%s⏱️ Cron%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}cron:make:[nombre]{$reset}        Crear nueva tarea cron\n";
        echo "  {$yellow}cron:list{$reset}                 Listar tareas cron disponibles\n";
        echo "  {$yellow}cron:run:[nombre]{$reset}         Ejecutar tarea cron específica\n";
        echo "\n";
        echo sprintf('%s%s⚙️  Configuración%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}config:init{$reset}               Crear archivo config.json desde plantilla\n";
        echo "  {$yellow}config:debug:[true/false]{$reset} Activar/desactivar modo debug\n";
        echo "  {$yellow}config:templateCache:[true/false]{$reset} Activar/desactivar cache de Twig\n";
        echo "  {$yellow}config:ClearCache{$reset}         Limpiar cache de templates\n";
        echo "\n";
        echo sprintf('%s%s📊 Base de datos%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}migrate:make:[nombre]{$reset}     Crear nueva migración\n";
        echo "  {$yellow}migrate:up{$reset}                Ejecutar migraciones pendientes\n";
        echo "  {$yellow}migrate:down:[nombre]{$reset}     Ejecutar migración específica hacia abajo\n";
        echo "  {$yellow}migrate:rollback{$reset}          Revertir última migración\n";
        echo "  {$yellow}migrate:refresh{$reset}           Revertir y re-ejecutar todas las migraciones\n";
        echo "  {$yellow}migrate:refresh:seeder{$reset}    Refresh + ejecutar todos los seeders\n";
        echo "  {$yellow}migrate:dropAll{$reset}           Eliminar todas las tablas\n";
        echo "  {$yellow}migrate:status{$reset}            Ver migraciones ejecutadas y pendientes\n";
        echo "\n";
        echo "  {$yellow}seeder:make:[nombre]{$reset}      Crear nuevo seeder\n";
        echo "  {$yellow}seeder:runAll{$reset}             Ejecutar todos los seeders\n";
        echo "  {$yellow}seeder:run:[nombre]{$reset}       Ejecutar seeder específico\n";
        echo "\n";
        echo sprintf('%s%s🏗️  Generadores%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}controller:make:[nombre]{$reset}  Crear controlador (sin sufijo 'Controller')\n";
        echo "  {$yellow}controller:delete:[nombre]{$reset} Eliminar controlador\n";
        echo "\n";
        echo "  {$yellow}route:make:[nombre]{$reset}       Crear archivo de rutas\n";
        echo "  {$yellow}route:delete:[nombre]{$reset}     Eliminar archivo de rutas\n";
        echo "  {$yellow}route:list{$reset}               Mostrar todas las rutas registradas\n";
        echo "  {$yellow}route:compile{$reset}             Compilar rutas\n";
        echo "  {$yellow}route:cache{$reset}               Estado del cache de rutas\n";
        echo "  {$yellow}route:clear-cache{$reset}         Limpiar cache de rutas\n";
        echo "\n";
        echo "  {$yellow}middleware:make:[nombre]{$reset}  Crear middleware (sin sufijo 'Middleware')\n";
        echo "  {$yellow}middleware:delete:[nombre]{$reset} Eliminar middleware\n";
        echo "\n";
        echo "  {$yellow}model:make:[nombre]{$reset}       Crear modelo\n";
        echo "  {$yellow}model:delete:[nombre]{$reset}     Eliminar modelo\n";
        echo "\n";
        echo sprintf('%s%s🎨 Frontend%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}front:makeTwig:[nombre]{$reset}   Crear template Twig\n";
        echo "  {$yellow}front:makeVue:[nombre]{$reset}    Crear componente Vue\n";
        echo "\n";
        echo sprintf('%s%sℹ️  Información%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}status{$reset}                    Mostrar estado del sistema\n";
        echo "  {$yellow}version{$reset}                   Mostrar información de versión\n";
        echo "\n";
        echo sprintf('%s%s📋 Logs%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}logs:clear{$reset}                Limpiar todos los archivos de log\n";
        echo "  {$yellow}logs:list{$reset}                 Listar archivos de log disponibles\n";
        echo "  {$yellow}logs:view:[nombre]{$reset}        Ver contenido de un log específico\n";
        echo "  {$yellow}logs:tail:[nombre]{$reset}        Ver últimas líneas de un log (--lines=N)\n";
        echo "\n";
        echo sprintf('%s%s🗂️  Cache%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}cache:clear{$reset}               Limpiar todo el cache del sistema\n";
        echo "  {$yellow}cache:status{$reset}              Mostrar estado del cache\n";
        echo "  {$yellow}cache:routes{$reset}              Limpiar solo el cache de rutas\n";
        echo "  {$yellow}cache:twig{$reset}                Limpiar solo el cache de Twig\n";
        echo "\n";
        echo sprintf('%s%s🔧 Comandos de Aplicación%s%s', $bold, $green, $reset, PHP_EOL);
        echo "  {$yellow}app:<comando>{$reset}             Ejecutar comando de aplicación\n";
        echo "  {$yellow}command:make:<nombre>{$reset}     Generar nuevo comando desde plantilla\n";
        echo "  {$yellow}command:list{$reset}              Listar comandos de aplicación disponibles\n";
        echo "  {$yellow}command:refresh{$reset}           Refrescar cache de comandos\n";
        echo "  {$yellow}command:clear-cache{$reset}       Limpiar cache de comandos\n";
        echo "\n";
        self::printApplicationCommands($yellow, $cyan, $reset);
        echo "\n";
        echo sprintf('%s%sEjemplos:%s%s', $bold, $blue, $reset, PHP_EOL);
        echo "  {$cyan}php versaCLI migrate:up{$reset}              Ejecutar migraciones\n";
        echo "  {$cyan}php versaCLI command:make:email:send{$reset} Generar nuevo comando\n";
        echo "  {$cyan}php versaCLI RCMD:usuarios{$reset}          Crear módulo usuarios completo\n";
        echo "  {$cyan}php versaCLI versaMODULE:vue:dashboard{$reset} Crear módulo Vue dashboard\n";
        echo "  {$cyan}php versaCLI serve{$reset}                   Iniciar servidor de desarrollo\n";
        echo "  {$cyan}php versaCLI status{$reset}                  Ver estado del sistema\n";
        echo "  {$cyan}php versaCLI cache:clear{$reset}             Limpiar todo el cache\n";
        echo "  {$cyan}php versaCLI logs:tail:error{$reset}         Ver últimas líneas del log de errores\n";
        echo "\n";
    }

    public static function printModuleHelp(string $module): void
    {
        $reset = "\033[0m";
        $bold = "\033[1m";
        $cyan = "\033[36m";
        $yellow = "\033[33m";
        $green = "\033[32m";

        echo "\n";
        echo sprintf('%s%sAyuda del módulo: %s%s%s%s', $bold, $green, $cyan, $module, $reset, PHP_EOL);
        echo "\n";

        match ($module) {
            'route' => self::printRouteHelp($yellow, $reset),
            'migrate' => self::printMigrateHelp($yellow, $reset),
            'seeder' => self::printSeederHelp($yellow, $reset),
            'config' => self::printConfigHelp($yellow, $reset),
            'cron' => self::printCronHelp($yellow, $reset),
            'controller' => self::printControllerHelp($yellow, $reset),
            'model' => self::printModelHelp($yellow, $reset),
            'middleware' => self::printMiddlewareHelp($yellow, $reset),
            'front' => self::printFrontHelp($yellow, $reset),
            'versaMODULE' => self::printModuleGeneratorHelp($yellow, $reset),
            'RCMD' => self::printRCMDHelp($yellow, $reset),
            'logs' => self::printLogsHelp($yellow, $reset),
            'cache' => self::printCacheHelp($yellow, $reset),
            default => self::printHelp(),
        };
        echo "\n";
    }

    private static function printRouteHelp(string $color, string $reset): void
    {
        echo "  {$color}route:make:[nombre]{$reset}       Crear archivo de rutas\n";
        echo "  {$color}route:delete:[nombre]{$reset}     Eliminar archivo de rutas\n";
        echo "  {$color}route:list{$reset}               Mostrar todas las rutas registradas\n";
        echo "  {$color}route:compile{$reset}             Compilar rutas\n";
        echo "  {$color}route:cache{$reset}               Estado del cache de rutas\n";
        echo "  {$color}route:clear-cache{$reset}         Limpiar cache de rutas\n";
    }

    private static function printMigrateHelp(string $color, string $reset): void
    {
        echo "  {$color}migrate:make:[nombre]{$reset}     Crear nueva migración\n";
        echo "  {$color}migrate:up{$reset}               Ejecutar migraciones pendientes\n";
        echo "  {$color}migrate:down:[nombre]{$reset}     Ejecutar migración específica hacia abajo\n";
        echo "  {$color}migrate:rollback{$reset}          Revertir última migración\n";
        echo "  {$color}migrate:refresh{$reset}           Revertir y re-ejecutar todas las migraciones\n";
        echo "  {$color}migrate:refresh:seeder{$reset}    Refresh + ejecutar todos los seeders\n";
    }

    private static function printSeederHelp(string $color, string $reset): void
    {
        echo "  {$color}seeder:make:[nombre]{$reset}      Crear nuevo seeder\n";
        echo "  {$color}seeder:runAll{$reset}             Ejecutar todos los seeders\n";
        echo "  {$color}seeder:run:[nombre]{$reset}       Ejecutar seeder específico\n";
    }

    private static function printConfigHelp(string $color, string $reset): void
    {
        echo "  {$color}config:init{$reset}               Crear archivo config.json desde plantilla\n";
        echo "  {$color}config:debug:[true/false]{$reset} Activar/desactivar modo debug\n";
        echo "  {$color}config:templateCache:[true/false]{$reset} Activar/desactivar cache de Twig\n";
        echo "  {$color}config:ClearCache{$reset}         Limpiar cache de templates\n";
    }

    private static function printCronHelp(string $color, string $reset): void
    {
        echo "  {$color}cron:make:[nombre]{$reset}        Crear nueva tarea cron\n";
        echo "  {$color}cron:list{$reset}                 Listar tareas cron disponibles\n";
        echo "  {$color}cron:run:[nombre]{$reset}         Ejecutar tarea cron específica\n";
    }

    private static function printControllerHelp(string $color, string $reset): void
    {
        echo "  {$color}controller:make:[nombre]{$reset}  Crear controlador (sin sufijo 'Controller')\n";
        echo "  {$color}controller:delete:[nombre]{$reset} Eliminar controlador\n";
    }

    private static function printModelHelp(string $color, string $reset): void
    {
        echo "  {$color}model:make:[nombre]{$reset}       Crear modelo\n";
        echo "  {$color}model:delete:[nombre]{$reset}     Eliminar modelo\n";
    }

    private static function printMiddlewareHelp(string $color, string $reset): void
    {
        echo "  {$color}middleware:make:[nombre]{$reset}  Crear middleware (sin sufijo 'Middleware')\n";
        echo "  {$color}middleware:delete:[nombre]{$reset} Eliminar middleware\n";
    }

    private static function printFrontHelp(string $color, string $reset): void
    {
        echo "  {$color}front:makeTwig:[nombre]{$reset}   Crear template Twig\n";
        echo "  {$color}front:makeVue:[nombre]{$reset}    Crear componente Vue\n";
    }

    private static function printModuleGeneratorHelp(string $color, string $reset): void
    {
        echo "  {$color}versaMODULE:twig:[nombre]{$reset}  Crear módulo completo con template Twig\n";
        echo "  {$color}versaMODULE:vue:[nombre]{$reset}   Crear módulo completo con componente Vue\n";
    }

    private static function printRCMDHelp(string $color, string $reset): void
    {
        echo "  {$color}RCMD:[nombre]{$reset}              Crear ruta, controlador, modelo y middleware\n";
    }

    private static function printLogsHelp(string $color, string $reset): void
    {
        echo "  {$color}logs:clear{$reset}                Limpiar todos los archivos de log\n";
        echo "  {$color}logs:list{$reset}                 Listar archivos de log disponibles\n";
        echo "  {$color}logs:view:[nombre]{$reset}        Ver contenido de un log específico\n";
        echo "  {$color}logs:tail:[nombre]{$reset}        Ver últimas líneas de un log\n";
    }

    private static function printCacheHelp(string $color, string $reset): void
    {
        echo "  {$color}cache:clear{$reset}               Limpiar todo el cache del sistema\n";
        echo "  {$color}cache:status{$reset}              Mostrar estado del cache\n";
        echo "  {$color}cache:routes{$reset}              Limpiar solo el cache de rutas\n";
        echo "  {$color}cache:twig{$reset}                Limpiar solo el cache de Twig\n";
    }

    /**
     * Muestra los comandos de aplicación descubiertos dinámicamente.
     */
    private static function printApplicationCommands(string $yellow, string $cyan, string $reset): void
    {
        try {
            $commands = CommandRegistry::getCachedCommands();

            if (count($commands) === 0) {
                echo "  {$cyan}(No hay comandos de aplicación disponibles){$reset}\n";

                return;
            }

            // Mostrar hasta 5 comandos en la ayuda principal
            $count = 0;
            foreach ($commands as $name => $class) {
                if ($count >= 5) {
                    $remaining = count($commands) - 5;
                    echo "  {$cyan}... y {$remaining} comando(s) más. Use 'command:list' para ver todos{$reset}\n";
                    break;
                }

                $info = CommandRegistry::getCommandInfo($name);
                if ($info !== null) {
                    $description = $info['description'];
                    // Truncar descripción si es muy larga
                    if (strlen($description) > 50) {
                        $description = substr($description, 0, 47) . '...';
                    }

                    echo "  {$yellow}app:{$name}{$reset}";
                    $padding = str_repeat(' ', max(1, 28 - strlen($name)));
                    echo $padding . $description . "\n";
                }

                ++$count;
            }
        } catch (\Throwable $e) {
            // Si hay error al descubrir comandos, no mostrar nada
            echo "  {$cyan}(Error al cargar comandos de aplicación){$reset}\n";
        }
    }
}
