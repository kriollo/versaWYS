<?php

declare(strict_types=1);

namespace versaWYS\kernel\helpers;

use DateException;
use DateTime;
use Exception;
use IntlDateFormatter;
use Random\RandomException;

class FechasFunction
{
    /**
     * Formatea una fecha en formato completo en español.
     *
     * @param string $fecha La fecha a formatear.
     * @return string La fecha formateada en formato completo en español.
     * @throws Exception
     */
    public static function formaFechaFullES(string $fecha): string
    {
        global $config;
        $fecha = new DateTime($fecha);
        $formatter = new IntlDateFormatter(
            'es_ES',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $config['build']['timezone']
        );
        return $formatter->format($fecha);
    }

    public static function formatFecha(mixed $fecha, string $formato): string
    {
        global $config;
        $fecha = new DateTime($fecha);
        $formatter = new IntlDateFormatter(
            'es_ES',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $config['build']['timezone'],
            IntlDateFormatter::GREGORIAN,
            $formato
        );
        return $formatter->format($fecha);
    }

    /**
     * Returns an array of Twig filters.
     *
     * @return array The array of Twig filters.
     */
    public function setTwigFilters(): array
    {
        return [['formaFechaFullES', 'versaWYS\kernel\helpers\Functions::formaFechaFullES']];
    }

    /**
     * Returns an array of Twig functions.
     *
     * @return array The array of Twig functions.
     */
    public function setTwigFunctions(): array
    {
        return [['formaFechaFullES', 'versaWYS\kernel\helpers\Functions::formaFechaFullES']];
    }
}
