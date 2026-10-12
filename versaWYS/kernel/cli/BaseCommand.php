<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

use Exception;
use VersaORM\VersaModel;
use versaWYS\kernel\Loggin;
use versaWYS\kernel\model\OrmFactory;

/**
 * Clase base abstracta para comandos CLI de aplicación.
 *
 * Proporciona acceso a utilidades comunes, helpers y dependencias globales.
 */
abstract class BaseCommand implements CommandInterface
{
    protected array $config;

    protected bool $debug;

    protected mixed $versaOrm;

    public function __construct()
    {
        global $config, $debug, $versaOrm;

        $this->config = $config ?? [];
        $this->debug = $debug ?? $config['build']['debug'] ?? false;

        // Inicializar ORM si no está disponible
        if (!isset($versaOrm) || $versaOrm === null) {
            // Sin base de datos el comando igual debe poder listarse, mostrar su ayuda y ejecutarse
            // si no la necesita; los que sí la usan fallarán al consultar, con el error del ORM.
            try {
                $versaOrm = OrmFactory::make(
                    $this->config
                    + [
                        'versaorm' => [
                            'debug' => $this->debug,
                        ],
                    ],
                );
                VersaModel::setORM($versaOrm);
            } catch (\Throwable) {
                $versaOrm = null;
            }
        }

        $this->versaOrm = $versaOrm;
    }

    /**
     * Valida que un parámetro requerido exista.
     *
     * @param array $params Lista de parámetros
     * @param int $index Índice del parámetro requerido
     * @param string $name Nombre descriptivo del parámetro (para mensajes de error)
     * @return string El parámetro validado
     *
     * @throws Exception Si el parámetro no existe o está vacío
     */
    protected function requireParam(array $params, int $index, string $name = 'parámetro'): string
    {
        if (!isset($params[$index]) || trim($params[$index]) === '') {
            throw new Exception("El {$name} es requerido (parámetro {$index})");
        }

        return trim($params[$index]);
    }

    /**
     * Obtiene un parámetro opcional con valor por defecto.
     *
     * @param array $params Lista de parámetros
     * @param int $index Índice del parámetro
     * @param string $default Valor por defecto si no existe
     * @return string El parámetro o el valor por defecto
     */
    protected function getParam(array $params, int $index, string $default = ''): string
    {
        return isset($params[$index]) ? trim($params[$index]) : $default;
    }

    /**
     * Muestra un mensaje de éxito.
     */
    protected function success(string $message): void
    {
        CLIErrorHandler::success($message);
    }

    /**
     * Muestra un mensaje informativo.
     */
    protected function info(string $message): void
    {
        CLIErrorHandler::info($message);
    }

    /**
     * Muestra un mensaje de advertencia.
     */
    protected function warning(string $message): void
    {
        CLIErrorHandler::warning($message);
    }

    /**
     * Registra un mensaje de error y lo muestra en consola.
     */
    protected function error(string $message, array $context = []): void
    {
        Loggin::error($message, $this->getLogModule(), $context);
        echo CLIErrorHandler::colorize('✗ Error: ', 'red') . $message . "\n";
    }

    /**
     * Solicita input al usuario.
     */
    protected function ask(string $question, string $default = ''): string
    {
        return CLIHelper::ask($question, $default);
    }

    /**
     * Solicita confirmación al usuario.
     */
    protected function confirm(string $question, bool $default = false): bool
    {
        return CLIHelper::confirm($question, $default);
    }

    /**
     * Muestra un separador visual.
     */
    protected function separator(): void
    {
        CLIErrorHandler::printSeparator();
    }

    /**
     * Registra un log de información.
     */
    protected function log(string $message, array $context = []): void
    {
        Loggin::info($message, $this->getLogModule(), $context);
    }

    /**
     * Registra un log de debug.
     */
    protected function logDebug(string $message, array $context = []): void
    {
        if ($this->debug) {
            Loggin::debug($message, $this->getLogModule(), $context);
        }
    }

    /**
     * Retorna el nombre del módulo para logging.
     * Por defecto usa el nombre corto de la clase.
     */
    protected function getLogModule(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();

        return 'cli:' . strtolower(preg_replace('/Command$/', '', $className));
    }

    /**
     * Asegura que un directorio exista.
     */
    protected function ensureDirectory(string $path, int $permissions = 0o775): void
    {
        CLIHelper::ensureDirectory($path, $permissions);
    }

    /**
     * Implementación por defecto de getHelp.
     * Los comandos pueden sobreescribir para proporcionar ayuda detallada.
     */
    #[\Override]
    public function getHelp(): string
    {
        return $this->getDescription();
    }
}
