<?php

declare(strict_types=1);

namespace versaWYS\kernel\model;

use VersaORM\VersaORM;

/**
 * OrmFactory: construye VersaORM por petición a partir de config + request.
 */
class OrmFactory
{
    public static function make(array $appConfig): VersaORM
    {
        // Puedes aplicar multi-tenant, selección de DB por header/host, etc.

        $dbConfig = $appConfig['database'] ?? [];
        $voConfig = $appConfig['versaorm'] ?? [];

        // Normalizar claves para VersaORM
        $config = [
            'engine' => $dbConfig['engine'],
            'host' => $dbConfig['host'],
            'port' => (int) $dbConfig['port'],
            'charset' => $dbConfig['charset'],
            'driver' => $dbConfig['driver'],
            'collation' => $dbConfig['collation'],
            'database' => $dbConfig['database'],
            'username' => $dbConfig['username'],
            'password' => $dbConfig['password'],
            'sslmode' => $dbConfig['sslmode'] ?? null,
            // Extras con valores por defecto
            'debug' => $voConfig['debug'] ?? false,
            'cache' => $voConfig['cache'] ?? false,
            'logging' => $voConfig['logging'] ?? false,
            'log_path' => $dbConfig['log_path'] ?? $voConfig['log_path'] ?? './versaWYS/logs/orm/',
        ];
        try {
            $orm = new VersaORM($config);
            $orm->setTimezone($appConfig['build']['timezone']);

            // Alinear la zona horaria de la sesión de BD para que NOW() y similares coincidan con PHP.
            // MySQL recibe el offset (+HH:MM) porque los nombres de zona requieren tablas tz cargadas.
            if (str_contains(strtolower((string) $config['driver']), 'pg') || str_contains(strtolower((string) $config['driver']), 'postgres')) {
                $orm->exec("SET TIME ZONE '{$appConfig['build']['timezone']}'");
            } elseif (str_contains(strtolower((string) $config['driver']), 'mysql') || str_contains(strtolower((string) $config['driver']), 'maria')) {
                $orm->exec("SET time_zone = '" . date('P') . "'");
            }

            return $orm;
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $dbInfo = [
                'driver' => $config['driver'] ?? 'unknown',
                'host' => $config['host'] ?? 'unknown',
                'port' => $config['port'] ?? 'unknown',
                'database' => $config['database'] ?? 'unknown',
            ];

            // Detectar tipos específicos de errores
            if (
                str_contains($errorMsg, 'no existe la base de datos')
                || str_contains($errorMsg, 'database') && str_contains($errorMsg, 'does not exist')
            ) {
                throw new \RuntimeException(
                    sprintf(
                        "❌ ERROR DE BASE DE DATOS: La base de datos '%s' no existe.\n\n"
                        . "📊 Configuración:\n"
                        . "   • Driver: %s\n"
                        . "   • Host: %s\n"
                        . "   • Puerto: %s\n"
                        . "   • Base de datos: %s\n\n"
                        . "💡 Soluciones:\n"
                        . "   1. Crear la base de datos: CREATE DATABASE %s;\n"
                        . "   2. Verificar el nombre en versaWYS/kernel/config/config.json\n"
                        . '   3. Ejecutar: php versaCLI migrate:up (después de crear la BD)',
                        $dbInfo['database'],
                        $dbInfo['driver'],
                        $dbInfo['host'],
                        $dbInfo['port'],
                        $dbInfo['database'],
                        $dbInfo['database'],
                    ),
                    0,
                    $e,
                );
            }

            if (str_contains($errorMsg, 'Connection refused') || str_contains($errorMsg, 'could not connect')) {
                throw new \RuntimeException(
                    sprintf(
                        "❌ ERROR DE CONEXIÓN: No se pudo conectar al servidor de base de datos.\n\n"
                        . "📊 Configuración:\n"
                        . "   • Driver: %s\n"
                        . "   • Host: %s\n"
                        . "   • Puerto: %s\n\n"
                        . "💡 Soluciones:\n"
                        . "   1. Verificar que el servidor %s esté ejecutándose\n"
                        . "   2. Revisar host y puerto en versaWYS/kernel/config/config.json\n"
                        . '   3. Verificar reglas de firewall',
                        $dbInfo['driver'],
                        $dbInfo['host'],
                        $dbInfo['port'],
                        strtoupper($dbInfo['driver']),
                    ),
                    0,
                    $e,
                );
            }

            if (str_contains($errorMsg, 'Access denied') || str_contains($errorMsg, 'authentication failed')) {
                throw new \RuntimeException(
                    sprintf(
                        "❌ ERROR DE AUTENTICACIÓN: Credenciales de base de datos incorrectas.\n\n"
                        . "📊 Configuración:\n"
                        . "   • Usuario: %s\n"
                        . "   • Base de datos: %s\n\n"
                        . "💡 Soluciones:\n"
                        . "   1. Verificar usuario y contraseña en versaWYS/kernel/config/config.json\n"
                        . '   2. Verificar permisos del usuario en la base de datos',
                        $config['username'] ?? 'unknown',
                        $dbInfo['database'],
                    ),
                    0,
                    $e,
                );
            }

            // Error genérico con información útil
            throw new \RuntimeException(
                sprintf(
                    "❌ ERROR DE BASE DE DATOS: %s\n\n"
                    . "📊 Configuración:\n"
                    . "   • Driver: %s\n"
                    . "   • Host: %s\n"
                    . "   • Puerto: %s\n"
                    . "   • Base de datos: %s\n\n"
                    . '💡 Revisar configuración en: versaWYS/kernel/config/config.json',
                    $errorMsg,
                    $dbInfo['driver'],
                    $dbInfo['host'],
                    $dbInfo['port'],
                    $dbInfo['database'],
                ),
                0,
                $e,
            );
        }
    }
}
