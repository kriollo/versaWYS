<?php

declare(strict_types=1);

/*
 * La documentación contra el código: si una guía cita un comando, una clase o un enlace que ya no
 * existe, esta prueba falla. Así no vuelve a pasar que una guía prometa algo inexistente.
 */

/** @return array<string, string> ruta => contenido de cada guía */
function docs_files(): array
{
    $root = dirname(__DIR__, 2);
    $files = [];
    foreach ([...glob($root . '/documentacion/*.md'), $root . '/README.md'] as $file) {
        $files[substr($file, strlen($root) + 1)] = (string) file_get_contents($file);
    }

    return $files;
}

/** El ancla que GitHub le da a un título */
function docs_anchor(string $heading): string
{
    $slug = mb_strtolower(trim($heading));
    $slug = (string) preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $slug);

    return str_replace(' ', '-', $slug);
}

/** Las guías sin sus bloques de código (lo citado en el texto) */
function docs_prose(string $markdown): string
{
    return (string) preg_replace('/^```.*?^```/ms', '', $markdown);
}

it('los enlaces entre guías llevan a un archivo y a un título que existen', function (): void {
    $root = dirname(__DIR__, 2);
    $broken = [];
    foreach (docs_files() as $path => $markdown) {
        preg_match_all('/\]\((?!https?:|mailto:)([^)\s]+)\)/', docs_prose($markdown), $links);
        foreach ($links[1] as $link) {
            [$file, $anchor] = array_pad(explode('#', $link, 2), 2, null);
            $target = $file === '' ? $root . '/' . $path : dirname($root . '/' . $path) . '/' . $file;
            if (!file_exists($target)) {
                $broken[] = "{$path}: {$link}";

                continue;
            }

            if ($anchor === null || $anchor === '' || !str_ends_with($target, '.md')) {
                continue;
            }

            preg_match_all('/^#{1,6}\s+(.+)$/m', docs_prose((string) file_get_contents($target)), $headings);
            if (!in_array($anchor, array_map(docs_anchor(...), $headings[1]), true)) {
                $broken[] = "{$path}: {$link} (no existe el título)";
            }
        }
    }

    expect($broken)->toBe([]);
});

it('cada comando de versaCLI citado existe', function (): void {
    $strip = static fn(?string $s): string => (string) preg_replace('/\x1b\[[0-9;]*m/', '', (string) $s);
    $cli = escapeshellarg(PHP_BINARY) . ' versaCLI ';
    $help = $strip(shell_exec($cli . 'help 2>&1')) . $strip(shell_exec($cli . 'command:list 2>&1'));

    // Cada línea de la ayuda empieza con un comando; `[nombre]` o `<nombre>` al final es un argumento
    $known = ['help' => false];
    foreach (explode("\n", $help) as $line) {
        // `app:<comando>` es cualquier comando de la aplicación: esos valen solo si command:list los muestra
        if (preg_match('/^\s+([a-zA-Z][\w:\-]*)(\[[^\]]*\]|<[^>]*>)?/', $line, $m) === 1 && $m[1] !== 'app:') {
            $known[rtrim($m[1], ':')] = isset($m[2]);
        }
    }

    $unknown = [];
    foreach (docs_files() as $path => $markdown) {
        preg_match_all('/versaCLI\s+([a-zA-Z][\w:\-]*)/', $markdown, $commands);
        // Los que la propia guía crea con command:make:<nombre> se usan como app:<nombre>
        preg_match_all('/command:make:([\w:]+)/', $markdown, $created);
        foreach (array_unique($commands[1]) as $command) {
            $command = rtrim($command, ':');
            $ok = isset($known[$command]) || in_array($command, array_map(static fn(string $c): string => 'app:' . $c, $created[1]), true);
            foreach ($known as $name => $takesArgument) {
                // `migrate:make:Nombre`, o `migrate` solo (la ayuda de ese grupo)
                $ok = $ok || $takesArgument && str_starts_with($command, $name . ':') || str_starts_with($name, $command . ':');
            }

            if (!$ok) {
                $unknown[] = "{$path}: versaCLI {$command}";
            }
        }
    }

    expect($unknown)->toBe([]);
});

it('cada Clase::método() citado existe en el kernel o en app/', function (): void {
    $root = dirname(__DIR__, 2);
    $classes = [];
    $sources = [
        ...glob($root . '/versaWYS/vendor/versawys/kernel/src/{,*/,*/*/}*.php', GLOB_BRACE),
        ...glob($root . '/app/{,*/,*/*/}*.php', GLOB_BRACE),
    ];
    foreach ($sources as $file) {
        $code = (string) file_get_contents($file);
        if (
            preg_match('/^namespace\s+([^;]+);/m', $code, $ns) === 1
            && preg_match('/^(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|enum|trait)\s+(\w+)/m', $code, $c) === 1
        ) {
            $classes[$c[1]][] = $ns[1] . '\\' . $c[1];
        }
    }

    $missing = [];
    foreach (docs_files() as $path => $markdown) {
        // Las clases de ejemplo que la propia guía declara (class Productos …) no son del framework
        preg_match_all('/\bclass\s+(\w+)/', $markdown, $declared);
        preg_match_all('/\b([A-Z]\w+)::([a-zA-Z_]\w*)\s*\(/', $markdown, $calls, PREG_SET_ORDER);
        foreach ($calls as [, $class, $method]) {
            if (in_array($class, $declared[1], true) || in_array($class, ['PDO', 'DateTime', 'DateTimeImmutable'], true)) {
                continue;
            }

            $exists = false;
            foreach ($classes[$class] ?? [] as $fqcn) {
                $exists = $exists || method_exists($fqcn, $method);
            }

            if (!$exists) {
                $missing[] = "{$path}: {$class}::{$method}()";
            }
        }
    }

    expect(array_values(array_unique($missing)))->toBe([]);
});

it('los ejemplos PHP completos son código válido', function (): void {
    $invalid = [];
    $dir = sys_get_temp_dir() . '/versawys_docs_' . getmypid();
    @mkdir($dir);
    foreach (docs_files() as $path => $markdown) {
        preg_match_all('/^```php\n(<\?php.*?)^```/ms', $markdown, $blocks);
        foreach ($blocks[1] as $i => $code) {
            $file = "{$dir}/ejemplo_{$i}.php";
            file_put_contents($file, $code);
            exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $exit);
            if ($exit !== 0) {
                $invalid[] = "{$path}, ejemplo " . ($i + 1) . ': ' . implode(' ', $output);
            }

            $output = [];
        }
    }

    exec('rm -rf ' . escapeshellarg($dir));

    expect($invalid)->toBe([]);
});
