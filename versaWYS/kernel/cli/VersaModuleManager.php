<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class VersaModuleManager
{
    public static function createModule(string $moduleName): void
    {
        //$assets = self::$assets . ucfirst($moduleName);
        echo "Creando modulo $moduleName...\n";
    }
}
