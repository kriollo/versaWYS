<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

/**
 * Interfaz para comandos CLI de aplicación.
 *
 * Todos los comandos en app/commands/ deben implementar esta interfaz
 * para ser descubiertos y ejecutados automáticamente por el sistema CLI.
 */
interface CommandInterface
{
    /**
     * Ejecuta el comando con los parámetros proporcionados.
     *
     * @param array $params Parámetros del comando (extraídos después de modulo:accion)
     * @return int Código de salida (0 = éxito, 1+ = error)
     */
    public function execute(array $params): int;

    /**
     * Retorna una descripción corta del comando.
     *
     * @return string Descripción de una línea del propósito del comando
     */
    public function getDescription(): string;

    /**
     * Retorna la ayuda detallada del comando.
     *
     * @return string Texto de ayuda con uso, parámetros y ejemplos
     */
    public function getHelp(): string;
}
