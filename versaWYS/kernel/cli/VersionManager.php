<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class VersionManager
{
    public static function handleVersion(): void
    {
        echo CLIErrorHandler::colorize('🚀 versaWYS Framework', 'cyan') . "\n";
        echo str_repeat('═', 30) . "\n\n";

        // Versión del proyecto desde package.json
        $packageJsonPath = getcwd() . '/package.json';
        if (file_exists($packageJsonPath)) {
            $packageData = json_decode(file_get_contents($packageJsonPath), true);
            if (isset($packageData['version'])) {
                echo CLIErrorHandler::colorize('Versión del proyecto: ', 'yellow') . $packageData['version'] . "\n";
            }

            if (isset($packageData['name'])) {
                echo CLIErrorHandler::colorize('Nombre: ', 'yellow') . $packageData['name'] . "\n";
            }

            if (isset($packageData['description'])) {
                echo CLIErrorHandler::colorize('Descripción: ', 'yellow') . $packageData['description'] . "\n";
            }

            echo "\n";
        }

        // Información del framework
        echo CLIErrorHandler::colorize('Framework versaWYS', 'green') . "\n";
        echo "- Arquitectura: MVC + SPA\n";
        echo '- Backend: PHP ' . PHP_VERSION . "\n";
        echo "- Frontend: TypeScript + Vue.js + TailwindCSS\n";
        echo "- ORM: VersaORM PHP\n";
        echo "- Templates: Twig\n";
        echo "- Compilador: versacompiler\n\n";

        // Información del sistema
        echo CLIErrorHandler::colorize('Sistema:', 'blue') . "\n";
        echo '- OS: ' . PHP_OS . ' (' . php_uname('s') . ' ' . php_uname('r') . ")\n";
        echo '- Arquitectura: ' . php_uname('m') . "\n";
        echo '- SAPI: ' . PHP_SAPI . "\n";
        echo '- Memoria límite: ' . ini_get('memory_limit') . "\n";
        echo '- Tiempo límite: ' . ini_get('max_execution_time') . "s\n\n";

        // Extensiones PHP importantes
        $extensions = [
            'pdo' => 'PDO (Base de datos)',
            'pdo_mysql' => 'MySQL PDO',
            'json' => 'JSON',
            'curl' => 'cURL',
            'mbstring' => 'Multibyte String',
            'openssl' => 'OpenSSL',
        ];

        echo CLIErrorHandler::colorize('Extensiones PHP:', 'blue') . "\n";
        foreach ($extensions as $ext => $name) {
            $status = extension_loaded($ext) ? '🟢' : '🔴';
            echo sprintf('- %s %s%s', $status, $name, PHP_EOL);
        }

        echo "\n" . CLIErrorHandler::colorize('© 2024 versaWYS Framework', 'cyan') . "\n";
    }
}
