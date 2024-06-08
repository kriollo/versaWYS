<?php

declare(strict_types=1);

spl_autoload_register('register');

function register(string $class): void
{

    $class = str_replace('\\', '/', $class);
    if (file_exists($class . '.php')) {
        require_once $class . '.php';
    }
}

function registerRoutes(string $path): void
{
    $files = scandir($path);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            if (is_dir($path . '/' . $file)) {
                registerRoutes($path . '/' . $file);
            } else {
                require_once $path . '/' . $file;
            }
        }
    }
}
