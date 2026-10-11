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
     * @param string $fecha la fecha a formatear
     *
     * @throws Exception
     *
     * @return string la fecha formateada en formato completo en español
     */
    public static function formaFechaFullES(string $fecha): string
    {
        global $config;
        $fecha = new DateTime($fecha);
        $formatter = new IntlDateFormatter(
            'es_ES',
            IntlDateFormatter::FULL,
            IntlDateFormatter::FULL,
            $config['build']['timezone'],
        );

        return $formatter->format($fecha);
    }

    /**
     * Formatea una fecha en un formato específico.
     *
     * @param mixed $fecha la fecha a formatear, puede ser un string o DateTime
     * @param string $formato el formato deseado para la fecha
     *
     * @throws DateException
     * @throws RandomException
     *
     * @return string la fecha formateada
     */
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
            $formato,
        );

        return $formatter->format($fecha);
    }

    /**
     * Formatea una fecha en espanol con el estilo: "lunes, 24 de febrero de 2026".
     */
    public static function formatFechaLargaES(mixed $fecha): string
    {
        return self::formatFecha($fecha, "EEEE, d 'de' MMMM 'de' y");
    }

    /**
     * Returns an array of Twig filters.
     *
     * @return array the array of Twig filters
     */
    public function setTwigFilters(): array
    {
        return [['formaFechaFullES', Functions::class . '::formaFechaFullES']];
    }

    /**
     * Returns an array of Twig functions.
     *
     * @return array the array of Twig functions
     */
    public function setTwigFunctions(): array
    {
        return [['formaFechaFullES', Functions::class . '::formaFechaFullES']];
    }
}
