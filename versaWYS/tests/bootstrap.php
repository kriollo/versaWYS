<?php

declare(strict_types=1);

// Los tests corren desde la raíz del proyecto, igual que index.php y versaCLI
chdir(dirname(__DIR__, 2));

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../kernel/autoload.php';

// versaCLI exige un config.json: en un checkout limpio (CI) se genera el de por defecto
if (!file_exists('versaWYS/kernel/config/config.json')) {
    exec('php versaCLI config:init');
}
