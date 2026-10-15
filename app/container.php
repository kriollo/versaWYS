<?php

declare(strict_types=1);

use versaWYS\kernel\Container;

/*
 * Servicios de la aplicación en el contenedor (ver documentacion/Contenedor.md). Lo que no aparece aquí se
 * crea solo: una clase con constructor tipado no necesita registrarse. Registrar sirve para:
 * - interfaces: decir qué implementación usar;
 * - un solo objeto por proceso (singleton), p. ej. un cliente HTTP;
 * - valores que no son clases (una clave de API).
 */
return static function (Container $container): void {
    // $container->singleton(Pagos::class, static fn(): Pagos => new PagosWebpay(getenv('WEBPAY_KEY') ?: ''));
};
