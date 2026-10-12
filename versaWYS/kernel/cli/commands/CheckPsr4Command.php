<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli\commands;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use versaWYS\kernel\cli\BaseCommand;
use versaWYS\kernel\cli\CLIErrorHandler;

class CheckPsr4Command extends BaseCommand
{
    private array $availableClasses = [];
    private array $classToFile = [];
    private array $classMethods = [];
    private array $classInheritance = [];

    /** @var array<string,true>  method call sites: 'fqcn::method' => true */
    private array $calledMethods = [];

    /** @var array<string,true> Nombres de métodos llamados sin tipo resuelto ($obj->method()) */
    private array $calledMethodNames = [];

    /** @var array<string,array<mixed>> Cache de tokens por filepath para evitar re-tokenización */
    private array $tokenCache = [];

    /** @var string[] Prefijos de namespace cuyas clases se excluyen del reporte de métodos muertos */
    private array $alwaysAliveNamespacePrefixes = ['app\\cron\\', 'app\\commands\\'];

    #[\Override]
    public function execute(array $params): int
    {
        $projectRoot = getcwd();
        $composerDir = $projectRoot . DIRECTORY_SEPARATOR . 'versaWYS';
        $composerFile = $composerDir . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($composerFile)) {
            CLIErrorHandler::error("No se encontró composer.json en: {$composerFile}");

            return 1;
        }

        $composer = json_decode(file_get_contents($composerFile), true);
        if (!isset($composer['autoload']['psr-4'])) {
            CLIErrorHandler::info('No se detectaron mapeos PSR-4 en composer.json');

            return 0;
        }

        $deadcodeMode = in_array('--deadcode', $params, true);

        CLIErrorHandler::printSeparator();
        echo CLIErrorHandler::colorize('★ Análisis Completo de Cumplimiento PSR-4', 'cyan') . "\n";
        echo CLIErrorHandler::colorize('  (Estructura, namespaces, case-sensitivity y referencias)', 'yellow') . "\n\n";

        $issues = [];
        $psr4Mappings = [];

        echo CLIErrorHandler::colorize('FASE 1: Construyendo índice de clases...', 'cyan') . "\n";

        foreach ($composer['autoload']['psr-4'] as $prefix => $path) {
            $absolutePath = realpath($composerDir . DIRECTORY_SEPARATOR . $path);

            if ($absolutePath === false || !is_dir($absolutePath)) {
                $issues[] = [
                    'type' => 'missing_dir',
                    'message' => "Mapping '{$prefix}' -> '{$path}' no existe",
                ];
                continue;
            }

            $dir = rtrim($absolutePath, DIRECTORY_SEPARATOR);
            $psr4Mappings[$prefix] = $dir;
            $relPath = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $dir);
            echo "  • {$prefix} => {$relPath}\n";
            $this->buildClassIndex($dir, $prefix);
        }

        echo CLIErrorHandler::colorize("\n  ✓ Clases indexadas: " . count($this->availableClasses), 'green') . "\n\n";

        if ($deadcodeMode) {
            // Parsear --path=<value> si se proporciona
            $scopePath = null;
            foreach ($params as $param) {
                if (!str_starts_with($param, '--path=')) {
                    continue;
                }

                $rawPath = substr($param, 7);
                // Normalizar separadores y construir ruta absoluta
                $normalized = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rawPath), DIRECTORY_SEPARATOR);
                $scopePath = $projectRoot . DIRECTORY_SEPARATOR . $normalized;
                break;
            }

            // Parsear --exclude-namespace=<ns> (pueden ser múltiples)
            foreach ($params as $param) {
                if (!str_starts_with($param, '--exclude-namespace=')) {
                    continue;
                }

                $ns = strtolower(trim(substr($param, 20), '\\')) . '\\';
                $this->alwaysAliveNamespacePrefixes[] = $ns;
            }

            return $this->scanDeadCode($psr4Mappings, $projectRoot, $scopePath);
        }

        echo CLIErrorHandler::colorize('FASE 2: Verificando estructura PSR-4...', 'cyan') . "\n";

        foreach ($psr4Mappings as $prefix => $dir) {
            echo "  • Validando: {$prefix}\n";
            $dirIssues = $this->scanDirectory($dir, $prefix, $projectRoot);
            $issues = array_merge($issues, $dirIssues);
        }

        echo "\n";

        echo CLIErrorHandler::colorize('FASE 3: Verificando use statements...', 'cyan') . "\n";

        foreach ($psr4Mappings as $prefix => $dir) {
            echo "  • Analizando referencias en: {$prefix}\n";
            $useIssues = $this->scanUseStatements($dir, $projectRoot);
            $issues = array_merge($issues, $useIssues);
        }

        echo "\n";

        echo CLIErrorHandler::colorize('FASE 3.5: Verificando llamadas estáticas (Clase::)...', 'cyan') . "\n";

        foreach ($psr4Mappings as $prefix => $dir) {
            echo "  • Analizando llamadas estáticas en: {$prefix}\n";
            $staticIssues = $this->scanStaticClassReferences($dir, $projectRoot);
            $issues = array_merge($issues, $staticIssues);
        }

        echo "\n";

        echo CLIErrorHandler::colorize('FASE 3.6: Verificando instanciaciones (new Clase)...', 'cyan') . "\n";

        foreach ($psr4Mappings as $prefix => $dir) {
            echo "  • Analizando instanciaciones en: {$prefix}\n";
            $newIssues = $this->scanNewClassReferences($dir, $projectRoot);
            $issues = array_merge($issues, $newIssues);
        }

        echo "\n";

        echo CLIErrorHandler::colorize('FASE 4: Verificando rutas en require/include...', 'cyan') . "\n";

        foreach ($psr4Mappings as $prefix => $dir) {
            echo "  • Analizando rutas en: {$prefix}\n";
            $pathIssues = $this->scanFilePaths($dir, $projectRoot);
            $issues = array_merge($issues, $pathIssues);
        }

        echo "\n";

        echo CLIErrorHandler::colorize('FASE 5: Verificando existencia de métodos...', 'cyan') . "\n";

        foreach ($psr4Mappings as $prefix => $dir) {
            echo "  • Analizando llamadas a métodos en: {$prefix}\n";
            $methodIssues = $this->scanMethodReferences($dir, $projectRoot);
            $issues = array_merge($issues, $methodIssues);
        }

        echo "\n";

        CLIErrorHandler::printSeparator();

        if (count($issues) === 0) {
            CLIErrorHandler::success('✓ ¡Excelente! No se encontraron inconsistencias PSR-4');
            echo "\n  Todas las clases, namespaces y referencias están correctamente configuradas.\n";

            return 0;
        }

        $grouped = $this->groupIssues($issues);

        echo CLIErrorHandler::colorize('⚠ Se encontraron ' . count($issues) . ' inconsistencia(s):', 'red') . "\n\n";

        foreach ($grouped as $type => $typeIssues) {
            $this->printIssueCategory($type, $typeIssues);
        }

        return 1;
    }

    // =========================================================
    // DEAD CODE DETECTION
    // =========================================================

    /**
     * Orchestrates dead code analysis: unused methods + unused variables.
     *
     * @param  array<string,string>  $psr4Mappings
     * @param  string|null           $scopePath  Absolute path prefix to restrict reporting (optional).
     *                                            Call-site collection always uses the full project.
     */
    private function scanDeadCode(array $psr4Mappings, string $projectRoot, ?string $scopePath = null): int
    {
        CLIErrorHandler::printSeparator();
        echo CLIErrorHandler::colorize('⚰  Análisis de Dead Code', 'cyan') . "\n";

        if ($scopePath !== null) {
            $relScope = str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $scopePath);
            echo CLIErrorHandler::colorize("   Ámbito de reporte: {$relScope}", 'yellow') . "\n";
            echo CLIErrorHandler::colorize('   (Call-sites recolectadas de todo el proyecto)', 'yellow') . "\n";
        }

        echo "\n";

        // ---- Paso A: recolectar TODAS las call-sites del proyecto (siempre global) ----
        echo CLIErrorHandler::colorize('A: Recolectando llamadas a métodos...', 'cyan') . "\n";
        foreach ($psr4Mappings as $dir) {
            $this->collectAllMethodCalls($dir);
        }
        // También incluir archivos de routes / comandos fuera del namespace principal
        $extraDirs = ['app/routes', 'app/commands', 'versaWYS/kernel/cli/commands'];
        foreach ($extraDirs as $extra) {
            $fullExtra = getcwd() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $extra);
            if (is_dir($fullExtra)) {
                $this->collectAllMethodCalls($fullExtra);
            }
        }
        echo
            CLIErrorHandler::colorize('   ✓ Call-sites recolectadas: ' . count($this->calledMethods), 'green') . "\n\n"
        ;

        // ---- Paso B: métodos muertos (filtrado por $scopePath si se especificó) ----
        echo CLIErrorHandler::colorize('B: Detectando métodos no utilizados...', 'cyan') . "\n";
        $deadMethods = $this->reportDeadMethods($projectRoot, $scopePath);

        // ---- Paso C: variables no usadas (filtrado por $scopePath si se especificó) ----
        echo CLIErrorHandler::colorize('\nC: Detectando variables no utilizadas...', 'cyan') . "\n";
        $unusedVars = [];
        foreach ($psr4Mappings as $dir) {
            $unusedVars = array_merge($unusedVars, $this->scanUnusedVariables($dir, $projectRoot, $scopePath));
        }

        CLIErrorHandler::printSeparator();

        $totalDead = count($deadMethods);
        $totalUnused = count($unusedVars);
        $hasIssues = $totalDead > 0 || $totalUnused > 0;

        if (!$hasIssues) {
            CLIErrorHandler::success('✓ No se detectó código muerto ni variables sin uso');
            return 0;
        }

        echo CLIErrorHandler::colorize('⚠  Resumen de Dead Code', 'red') . "\n";
        echo CLIErrorHandler::colorize("  Métodos sin uso: {$totalDead}", 'yellow') . "\n";
        echo CLIErrorHandler::colorize("  Variables sin uso: {$totalUnused}", 'yellow') . "\n\n";

        if ($totalDead > 0) {
            echo CLIErrorHandler::colorize('▸ Métodos detectados como no utilizados:', 'yellow') . "\n\n";
            foreach ($deadMethods as $entry) {
                $rel = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $entry['file']);
                $flag = $entry['private']
                    ? CLIErrorHandler::colorize('[private]', 'cyan')
                    : CLIErrorHandler::colorize('[public]', 'green');
                echo
                    '  '
                        . CLIErrorHandler::colorize('◉', 'red')
                        . " {$flag} "
                        . CLIErrorHandler::colorize($entry['class'] . '::' . $entry['method'], 'white')
                        . "  {$rel}:{$entry['line']}\n"
                ;
            }
            echo "\n";
        }

        if ($totalUnused > 0) {
            echo CLIErrorHandler::colorize('▸ Variables asignadas pero nunca leídas:', 'yellow') . "\n\n";
            foreach ($unusedVars as $entry) {
                $rel = str_replace(getcwd() . DIRECTORY_SEPARATOR, '', $entry['file']);
                echo
                    '  '
                        . CLIErrorHandler::colorize('◉', 'red')
                        . ' '
                        . CLIErrorHandler::colorize('$' . $entry['var'], 'cyan')
                        . "  {$rel}:{$entry['line']}\n"
                ;
            }
            echo "\n";
        }

        return 1;
    }

    /**
     * Walk all PHP files in $dir and populate $this->calledMethods with
     * every method invocation site we can statically resolve.
     * Key format: strtolower('fqcn::methodName')
     */
    private function collectAllMethodCalls(string $dir): void
    {
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $tokens = $this->getTokens($filePath);
                $context = $this->extractNamespaceAndUseMap($tokens);
                $count = count($tokens);

                // details of current file to know the current FQCN for $this->method() calls
                $details = $this->parsePhpFileDetails($filePath);
                $currentClass = $details['class'] !== ''
                    ? ($details['namespace'] ? $details['namespace'] . '\\' : '') . $details['class']
                    : null;

                for ($i = 0; $i < $count; ++$i) {
                    $token = $tokens[$i];

                    // ---- $this->method() ----
                    if (
                        is_array($token)
                        && T_VARIABLE === $token[0]
                        && '$this' === $token[1]
                        && $currentClass !== null
                    ) {
                        $ni = $this->getNextSignificantTokenIndex($tokens, $i);
                        if ($ni !== null && is_array($tokens[$ni]) && T_OBJECT_OPERATOR === $tokens[$ni][0]) {
                            $ni2 = $this->getNextSignificantTokenIndex($tokens, $ni);
                            if ($ni2 !== null && is_array($tokens[$ni2]) && T_STRING === $tokens[$ni2][0]) {
                                $ni3 = $this->getNextSignificantTokenIndex($tokens, $ni2);
                                if ($ni3 !== null && '(' === $tokens[$ni3]) {
                                    $key = strtolower($currentClass . '::' . $tokens[$ni2][1]);
                                    $this->calledMethods[$key] = true;
                                }
                            }
                        }
                        continue;
                    }

                    // ---- ClassName::method() or self::method() / static::method() ----
                    if (is_array($token) && T_DOUBLE_COLON === $token[0]) {
                        $prevIdx = $this->getPrevSignificantTokenIndex($tokens, $i);
                        if ($prevIdx === null) {
                            continue;
                        }
                        $prev = $tokens[$prevIdx];
                        $className = '';
                        if (
                            is_array($prev)
                            && in_array($prev[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], strict: true)
                        ) {
                            $className = $prev[1];
                        }
                        if ($className === '') {
                            continue;
                        }

                        $lc = strtolower($className);
                        if ($lc === 'self' || $lc === 'static') {
                            $resolved = $currentClass;
                        } elseif ($lc === 'parent') {
                            $resolved = null; // conservatively skip parent calls
                        } else {
                            $resolved = $this->resolveClassNameFromContext(
                                $className,
                                $context['namespace'],
                                $context['uses'],
                            );
                        }

                        if ($resolved === null) {
                            continue;
                        }

                        $ni = $this->getNextSignificantTokenIndex($tokens, $i);
                        if ($ni !== null && is_array($tokens[$ni]) && T_STRING === $tokens[$ni][0]) {
                            $ni2 = $this->getNextSignificantTokenIndex($tokens, $ni);
                            if ($ni2 !== null && '(' === $tokens[$ni2]) {
                                $key = strtolower($resolved . '::' . $tokens[$ni][1]);
                                $this->calledMethods[$key] = true;
                                // Also mark on parent classes so override detection works
                                $lowClass = strtolower($resolved);
                                $this->markMethodOnParents($lowClass, strtolower($tokens[$ni][1]));
                            }
                        }
                        continue;
                    }

                    // ---- $variable->method() ---- (sin tipo resuelto: registrar solo el nombre del método)
                    if (is_array($token) && T_OBJECT_OPERATOR === $token[0]) {
                        $ni = $this->getNextSignificantTokenIndex($tokens, $i);
                        if ($ni !== null && is_array($tokens[$ni]) && T_STRING === $tokens[$ni][0]) {
                            $ni2 = $this->getNextSignificantTokenIndex($tokens, $ni);
                            if ($ni2 !== null && '(' === $tokens[$ni2]) {
                                $methodName = strtolower($tokens[$ni][1]);
                                // No se puede resolver el tipo: guardar solo el nombre para comprobación conservadora
                                $this->calledMethodNames[$methodName] = true;
                            }
                        }
                    }

                    // ---- [ClassName::class, 'methodName'] callable-array pattern ----
                    // Covers: Router::get(..., [Controller::class, 'action'])
                    //         ->middleware([[AuthMiddleware::class, 'check']])
                    //         call_user_func([Foo::class, 'bar'])
                    if ('[' === $token) {
                        $this->resolveCallableArray($tokens, $i, $context, $currentClass);
                    }
                }
            }
        } catch (\Exception) {
        }
    }

    /**
     * Given the index of an opening '[', resolve any [ClassName::class, 'method'] or
     * [[ClassName::class, 'method'], ...] callable-array patterns and mark their methods.
     *
     * @param  array<mixed>  $tokens
     * @param  array{namespace:string,uses:array<string,string>}  $context
     */
    private function resolveCallableArray(
        array $tokens,
        int $openBracketIdx,
        array $context,
        ?string $currentClass,
    ): void {
        $idx = $this->getNextSignificantTokenIndex($tokens, $openBracketIdx);
        if ($idx === null) {
            return;
        }

        // Pattern: [[ClassName::class, 'method'], ...] — outer '[' followed immediately by inner '['
        if ('[' === $tokens[$idx]) {
            // Recurse into each inner '[' element
            $this->resolveCallableArray($tokens, $idx, $context, $currentClass);
            return;
        }

        // Pattern: [ClassName::class, 'method']
        if (
            is_array($tokens[$idx])
            && in_array($tokens[$idx][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], strict: true)
        ) {
            $classNameRaw = $tokens[$idx][1];
            $idx2 = $this->getNextSignificantTokenIndex($tokens, $idx);
            if ($idx2 === null || !is_array($tokens[$idx2]) || T_DOUBLE_COLON !== $tokens[$idx2][0]) {
                return;
            }
            $idx3 = $this->getNextSignificantTokenIndex($tokens, $idx2);
            if ($idx3 === null || !is_array($tokens[$idx3]) || T_CLASS !== $tokens[$idx3][0]) {
                return;
            }
            $idx4 = $this->getNextSignificantTokenIndex($tokens, $idx3);
            if ($idx4 === null || ',' !== $tokens[$idx4]) {
                return;
            }
            $idx5 = $this->getNextSignificantTokenIndex($tokens, $idx4);
            if ($idx5 === null || !is_array($tokens[$idx5]) || T_CONSTANT_ENCAPSED_STRING !== $tokens[$idx5][0]) {
                return;
            }

            $methodName = trim($tokens[$idx5][1], "'\"");
            $resolvedClass = $this->resolveClassNameFromContext($classNameRaw, $context['namespace'], $context['uses']);

            if ($resolvedClass !== null && $methodName !== '') {
                $key = strtolower($resolvedClass . '::' . $methodName);
                $this->calledMethods[$key] = true;
                $this->markMethodOnParents(strtolower($resolvedClass), strtolower($methodName));
            }
        }
    }

    /**
     * When a method is called on a class, also mark it as called on all ancestor
     * classes that define the same method (handles overrides).
     */
    private function markMethodOnParents(string $lowercaseClass, string $lowerMethod): void
    {
        if (!isset($this->classInheritance[$lowercaseClass])) {
            return;
        }
        $inh = $this->classInheritance[$lowercaseClass];
        $parents = array_filter(array_merge(
            $inh['extends'] ? [$inh['extends']] : [],
            $inh['implements'],
            $inh['traits'],
        ));
        foreach ($parents as $parent) {
            $key = $parent . '::' . $lowerMethod;
            if (!isset($this->calledMethods[$key]) && isset($this->classMethods[$parent])) {
                foreach ($this->classMethods[$parent] as $m) {
                    if (strtolower($m['name']) !== $lowerMethod) {
                        continue;
                    }

                    $this->calledMethods[$key] = true;
                    $this->markMethodOnParents($parent, $lowerMethod);
                    break;
                }
            }
        }
    }

    /**
     * Cross-reference defined methods against collected call-sites and return dead ones.
     * If $scopePath is given, only methods defined in files under that path are reported.
     *
     * @return array<int,array{class:string,method:string,line:int,file:string,private:bool}>
     */
    private function reportDeadMethods(string $projectRoot, ?string $scopePath = null): array
    {
        // Method names that are entry points / framework-called / magic: never flag these
        $alwaysAlive = [
            '__construct',
            '__destruct',
            '__get',
            '__set',
            '__isset',
            '__unset',
            '__call',
            '__callstatic',
            '__tostring',
            '__clone',
            '__invoke',
            '__serialize',
            '__unserialize',
            '__sleep',
            '__wakeup',
            // Framework entry points
            'execute',
            'getdescription',
            'gethelp',
            'run',
            'index',
            'handle',
            'register',
            'boot',
            'up',
            'down',
            // Interface-required
            'getmessage',
            'getcode',
            'getfile',
            'getline',
            'gettrace',
            'gettraceasstring',
            'getprevious',
            // ORM hooks (sobrescritos en modelos pero invocados internamente por el ORM o los tests)
            'definepropertytypes',
            'tablename',
            'validate',
            'beforestore',
            'afterstore',
            'beforedelete',
            'afterdelete',
            // Métodos de contrato adicionales del framework
            'countbystatus',
        ];

        $dead = [];

        foreach ($this->classMethods as $lcFqcn => $methods) {
            if (!isset($this->availableClasses[$lcFqcn])) {
                continue;
            }
            $fqcn = $this->availableClasses[$lcFqcn];
            $file = $this->classToFile[$fqcn] ?? null;

            // Excluir clases en namespaces de framework cargadas dinámicamente (cron, commands, etc.)
            if ($this->isAlwaysAliveClass($lcFqcn)) {
                continue;
            }

            // Si se especificó --path, solo reportar métodos de archivos dentro de ese ámbito
            if ($scopePath !== null && $file !== null) {
                if (!str_starts_with(realpath($file) ?: $file, realpath($scopePath) ?: $scopePath)) {
                    continue;
                }
            }
            foreach ($methods as $method) {
                $name = $method['name'];
                $lcName = strtolower($name);
                $isPrivate = $method['private'] ?? false;

                // Always-alive patterns
                if (in_array($lcName, $alwaysAlive, true)) {
                    continue;
                }
                // Abstract / interface stubs have no body — skip
                if ($method['abstract'] ?? false) {
                    continue;
                }

                $callKey = $lcFqcn . '::' . $lcName;

                // Un método es "vivo" si se llamó con tipo resuelto O si su nombre aparece
                // en alguna llamada sin tipo ($obj->method() sin resolver la clase)
                if (!isset($this->calledMethods[$callKey]) && !isset($this->calledMethodNames[$lcName])) {
                    $dead[] = [
                        'class' => $fqcn,
                        'method' => $name,
                        'line' => $method['line'] ?? 0,
                        'file' => $file ?? '',
                        'private' => $isPrivate,
                    ];
                }
            }
        }

        // Sort by file then line
        usort($dead, static fn ($a, $b) => [$a['file'], $a['line']] <=> [$b['file'], $b['line']]);

        if ($dead !== []) {
            $cnt = count($dead);
            echo CLIErrorHandler::colorize("   ⚠ {$cnt} método(s) sin uso detectado(s)", 'yellow') . "\n";
        } else {
            echo CLIErrorHandler::colorize('   ✓ Ningún método muerto detectado', 'green') . "\n";
        }

        return $dead;
    }

    /**
     * Scan PHP files for variables that are assigned but never read within the same scope.
     * If $scopePath is given, only files under that path are scanned.
     *
     * @return array<int,array{file:string,var:string,line:int}>
     */
    private function scanUnusedVariables(string $dir, string $projectRoot, ?string $scopePath = null): array
    {
        $results = [];

        // Si el directorio completo está fuera del scope especificado, saltar
        if ($scopePath !== null) {
            $realDir = realpath($dir) ?: $dir;
            $realScope = realpath($scopePath) ?: $scopePath;
            // Solo escanear si el dir está dentro del scope O el scope está dentro del dir
            if (!str_starts_with($realDir, $realScope) && !str_starts_with($realScope, $realDir)) {
                return [];
            }
        }
        // Variables that are universally "consumed" by PHP itself or used as out-params
        $alwaysUsed = [
            'this',
            '_',
            'GLOBALS',
            '_POST',
            '_GET',
            '_SESSION',
            '_COOKIE',
            '_SERVER',
            '_ENV',
            '_FILES',
            '_REQUEST',
            'argc',
            'argv',
        ];

        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $tokens = $this->getTokens($filePath);
                $count = count($tokens);

                // Extract scopes (function/method bodies) as token ranges
                $scopes = $this->extractFunctionScopes($tokens, $count);

                foreach ($scopes as $scope) {
                    $scopeTokens = array_slice($tokens, $scope['start'], $scope['end'] - $scope['start']);
                    $unused = $this->findUnusedVarsInScope($scopeTokens, $alwaysUsed);
                    foreach ($unused as $entry) {
                        $results[] = [
                            'file' => $filePath,
                            'var' => $entry['var'],
                            'line' => $entry['line'],
                        ];
                    }
                }
            }
        } catch (\Exception) {
        }

        $cnt = count($results);
        if ($cnt > 0) {
            echo CLIErrorHandler::colorize("   ⚠ {$cnt} variable(s) sin uso detectada(s)", 'yellow') . "\n";
        } else {
            echo CLIErrorHandler::colorize('   ✓ Ninguna variable sin uso detectada', 'green') . "\n";
        }

        return $results;
    }

    /**
     * Return token ranges [start, end) for each function/method body.
     *
     * @param  array<mixed>  $tokens
     * @return array<int,array{start:int,end:int}>
     */
    private function extractFunctionScopes(array $tokens, int $count): array
    {
        $scopes = [];

        for ($i = 0; $i < $count; ++$i) {
            $t = $tokens[$i];
            if (!is_array($t) || $t[0] !== T_FUNCTION) {
                continue;
            }

            // Find the opening '{' of the function body
            $depth = 0;
            $bodyStart = null;

            for ($j = $i + 1; $j < $count; ++$j) {
                $tt = $tokens[$j];
                if (
                    $tt === '{'
                    || is_array($tt) && in_array($tt[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], strict: true)
                ) {
                    if ($bodyStart === null) {
                        $bodyStart = $j;
                        $depth = 1;
                    } else {
                        ++$depth;
                    }
                } elseif ($tt === '}') {
                    if ($bodyStart !== null) {
                        --$depth;
                        if ($depth === 0) {
                            $scopes[] = ['start' => $bodyStart + 1, 'end' => $j];
                            $i = $j; // skip past this function
                            break;
                        }
                    }
                } elseif ($tt === ';' && $bodyStart === null) {
                    // Abstract / interface method — no body
                    $i = $j;
                    break;
                }
            }
        }

        return $scopes;
    }

    /**
     * Within a flat token slice (one function body), find variables that are assigned
     * (as lvalue assignment, foreach variable, function parameter) but never read.
     *
     * This is a simple heuristic: we track:
     *   - assigns: $var = ...
     *   - reads: any other occurrence of $var
     *
     * @param  array<mixed>  $scopeTokens
     * @param  string[]      $alwaysUsed
     * @return array<int,array{var:string,line:int}>
     */
    private function findUnusedVarsInScope(array $scopeTokens, array $alwaysUsed): array
    {
        /** @var array<string,array{line:int,readCount:int}> $vars */
        $vars = [];
        $count = count($scopeTokens);

        for ($i = 0; $i < $count; ++$i) {
            $t = $scopeTokens[$i];

            // Detectar compact('var1', 'var2') para marcar variables referenciadas por nombre como leídas
            if (is_array($t) && $t[0] === T_STRING && strtolower($t[1]) === 'compact') {
                $ni = $i + 1;
                while ($ni < $count && is_array($scopeTokens[$ni]) && $scopeTokens[$ni][0] === T_WHITESPACE) {
                    ++$ni;
                }

                if ($ni < $count && $scopeTokens[$ni] === '(') {
                    ++$ni;
                    while ($ni < $count && $scopeTokens[$ni] !== ')') {
                        if (is_array($scopeTokens[$ni]) && $scopeTokens[$ni][0] === T_CONSTANT_ENCAPSED_STRING) {
                            $compactVar = trim($scopeTokens[$ni][1], "'\"");
                            if (isset($vars[$compactVar])) {
                                ++$vars[$compactVar]['readCount'];
                            }
                        }

                        ++$ni;
                    }
                }

                continue;
            }

            if (!is_array($t) || $t[0] !== T_VARIABLE) {
                continue;
            }

            $varName = ltrim($t[1], '$');
            $line = $t[2];

            if (in_array($varName, $alwaysUsed, true)) {
                continue;
            }

            // Look at the next significant token to determine if this is an assignment
            $ni = $i + 1;
            while ($ni < $count && is_array($scopeTokens[$ni]) && $scopeTokens[$ni][0] === T_WHITESPACE) {
                ++$ni;
            }

            $isAssignment = false;
            if ($ni < $count) {
                $next = $scopeTokens[$ni];
                if ($next === '=') {
                    // Check it's not == or ===
                    $ni2 = $ni + 1;
                    if ($ni2 < $count && $scopeTokens[$ni2] !== '=') {
                        $isAssignment = true;
                    }
                } elseif (
                    is_array($next)
                    && in_array(
                        $next[0],
                        [
                            T_AND_EQUAL,
                            T_OR_EQUAL,
                            T_XOR_EQUAL,
                            T_PLUS_EQUAL,
                            T_MINUS_EQUAL,
                            T_MUL_EQUAL,
                            T_DIV_EQUAL,
                            T_MOD_EQUAL,
                            T_POW_EQUAL,
                            T_SL_EQUAL,
                            T_SR_EQUAL,
                            T_CONCAT_EQUAL,
                            T_COALESCE_EQUAL,
                        ],
                        true,
                    )
                ) {
                    $isAssignment = true;
                }
            }

            if ($isAssignment) {
                // Register as an assignment (first occurrence wins for line tracking)
                if (!isset($vars[$varName])) {
                    $vars[$varName] = ['line' => $line, 'readCount' => 0];
                } else {
                    // Re-assigned: reset read count for this assignment
                    $vars[$varName]['line'] = $line;
                    $vars[$varName]['readCount'] = 0;
                }
            } else {
                // This is a read
                if (isset($vars[$varName])) {
                    ++$vars[$varName]['readCount'];
                }
            }
        }

        $unused = [];
        foreach ($vars as $name => $info) {
            if ($info['readCount'] !== 0) {
                continue;
            }

            $unused[] = ['var' => $name, 'line' => $info['line']];
        }

        return $unused;
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Análisis completo PSR-4: estructura, namespaces, case-sensitivity y referencias';
    }

    #[\Override]
    public function getHelp(): string
    {
        return <<<'HELP'
            Uso: php versaCLI app:check:psr4 [--deadcode] [--path=<path>] [--exclude-namespace=<ns>]

            Realiza un análisis exhaustivo de cumplimiento PSR-4:

              ✓ Verifica que los namespaces coincidan con la estructura de directorios
              ✓ Valida case-sensitivity en rutas y namespaces (crítico para Linux)
              ✓ Analiza todos los 'use' statements buscando referencias incorrectas
              ✓ Detecta uso de clases con case incorrecto que funcionarían en Windows pero fallarían en Linux
              ✓ Valida llamadas estáticas del tipo Clase::método con case exacto
              ✓ Valida rutas en require/include y funciones filesystem (file_exists, fopen, etc.)
              ✓ Verifica que todas las rutas físicas usen el case correcto

            Este comando es esencial para proyectos desarrollados en Windows que se desplegarán en Linux/Unix.

            FLAGS:
              --deadcode    Activa análisis de código muerto (métodos no utilizados y variables sin uso)
                            En lugar del análisis PSR-4 estándar, busca:
                              - Métodos públicos/privados que no son invocados en ningún punto del código
                              - Variables asignadas dentro de funciones que nunca son leídas
                            El análisis es class-aware: distingue Foo::metodo de Bar::metodo aunque tengan el mismo nombre.
                            Por defecto, las clases en app\cron\ y app\commands\ se excluyen del reporte
                            de métodos muertos (se cargan dinámicamente), pero su contenido SÍ se analiza
                            en busca de variables sin uso.
              --path=<path> Restringe el reporte de dead code al path especificado (ej: --path=app/controllers).
                            Las llamadas (call-sites) se siguen recolectando de todo el proyecto para evitar falsos positivos.
              --exclude-namespace=<ns>
                            Excluye del reporte de dead code las clases del namespace dado.
                            Útil para cron jobs, commands y otras clases de entrada dinámica.
                            Se puede usar múltiples veces.
                            Ej: --exclude-namespace=app\\cron --exclude-namespace=app\\commands

            Tipos de problemas detectados (modo estándar):
              - Namespace declarado no coincide con la ruta del archivo
              - Carpetas con case incorrecto (ej: 'services' vs 'Services')
              - Referencias 'use' con mayúsculas/minúsculas incorrectas
              - Clases referenciadas que no existen con ese case exacto
              - Rutas en require/include con case incorrecto
              - Archivos referenciados que no existen

            Código de salida:
              0 - Sin problemas encontrados
              1 - Se encontraron inconsistencias

            Ejemplos:
              php versaCLI app:check:psr4                                           # Análisis PSR-4 completo del proyecto
              php versaCLI app:check:psr4 --deadcode                               # Análisis de código muerto (todo el proyecto)
              php versaCLI app:check:psr4 --deadcode --path=app                    # Dead code solo detectado en la carpeta /app
              php versaCLI app:check:psr4 --deadcode --exclude-namespace=app\\cron  # Excluir cron del reporte
            HELP;
    }

    private function buildClassIndex(string $dir, string $prefix): void
    {
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $declared = $this->parsePhpFileDetails($filePath);

                if ('' === $declared['class']) {
                    continue;
                }

                $fqcn = ($declared['namespace'] ? $declared['namespace'] . '\\' : '') . $declared['class'];
                $lowercaseFqcn = strtolower($fqcn);

                $this->availableClasses[$lowercaseFqcn] = $fqcn;
                $this->classToFile[$fqcn] = $filePath;
                $this->classMethods[$lowercaseFqcn] = $declared['methods'];
                $this->classInheritance[$lowercaseFqcn] = [
                    'extends' => $declared['extends'] ? strtolower($declared['extends']) : null,
                    'implements' => array_map('strtolower', $declared['implements']),
                    'traits' => array_map('strtolower', $declared['traits']),
                ];
            }
        } catch (\Exception $e) {
        }
    }

    private function scanDirectory(string $dir, string $prefix, string $projectRoot): array
    {
        $issues = [];

        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $relPath = substr($filePath, strlen($dir) + 1);
                $relPathNs = str_replace(DIRECTORY_SEPARATOR, '\\', substr($relPath, 0, -4));
                $expectedFQCN = rtrim($prefix, '\\') . ($relPathNs === '' ? '' : '\\' . $relPathNs);

                $declared = $this->parsePhpFileDetails($filePath);
                if ('' === $declared['class']) {
                    continue;
                }

                $declaredFQCN = ($declared['namespace'] ? $declared['namespace'] . '\\' : '') . $declared['class'];

                if ($declaredFQCN !== $expectedFQCN) {
                    $issues[] = [
                        'type' => 'fqcn_mismatch',
                        'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                        'declared' => $declaredFQCN,
                        'expected' => $expectedFQCN,
                    ];
                    continue;
                }

                $caseIssue = $this->verifyCaseSensitivity($filePath, $dir, $declaredFQCN, $prefix, $projectRoot);
                if ($caseIssue !== null) {
                    $issues[] = $caseIssue;
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'scan_error',
                'message' => "Error escaneando {$dir}: " . $e->getMessage(),
            ];
        }

        return $issues;
    }

    private function scanUseStatements(string $dir, string $projectRoot): array
    {
        $issues = [];

        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $useStatements = $this->extractUseStatements($filePath);

                foreach ($useStatements as $use) {
                    $fqcn = $use['fqcn'];
                    $line = $use['line'];
                    $lowercaseFqcn = strtolower($fqcn);

                    if (!isset($this->availableClasses[$lowercaseFqcn])) {
                        continue;
                    }

                    $correctFqcn = $this->availableClasses[$lowercaseFqcn];

                    if ($fqcn !== $correctFqcn) {
                        $issues[] = [
                            'type' => 'use_case_mismatch',
                            'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                            'line' => $line,
                            'used' => $fqcn,
                            'correct' => $correctFqcn,
                            'definition' => str_replace(
                                $projectRoot . DIRECTORY_SEPARATOR,
                                '',
                                $this->classToFile[$correctFqcn],
                            ),
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'scan_error',
                'message' => "Error analizando use statements en {$dir}: " . $e->getMessage(),
            ];
        }

        return $issues;
    }

    private function extractUseStatements(string $file): array
    {
        $uses = [];
        $tokens = $this->getTokens($file);
        $count = count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i];
            if (!is_array($token) || $token[0] !== T_USE) {
                continue;
            }

            ++$i;
            while (isset($tokens[$i]) && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                ++$i;
            }

            $fqcn = '';
            $line = $token[2];
            while (isset($tokens[$i])) {
                $t = $tokens[$i];

                if (is_array($t)) {
                    if (in_array(
                        $t[0],
                        [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED],
                        strict: true,
                    )) {
                        $fqcn .= $t[1];
                        ++$i;
                        continue;
                    }
                    if ($t[0] === T_WHITESPACE) {
                        ++$i;
                        continue;
                    }
                    if ($t[0] === T_AS) {
                        break;
                    }
                } else {
                    if ($t === ';' || $t === ',') {
                        break;
                    }
                }
                ++$i;
            }

            $fqcn = ltrim($fqcn, '\\');
            if ($fqcn !== '') {
                $uses[] = [
                    'fqcn' => $fqcn,
                    'line' => $line,
                ];
            }
        }

        return $uses;
    }

    private function scanFilePaths(string $dir, string $projectRoot): array
    {
        $issues = [];

        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $pathReferences = $this->extractFilePaths($filePath);

                foreach ($pathReferences as $ref) {
                    $path = $ref['path'];
                    $line = $ref['line'];
                    $type = $ref['type'];

                    $resolvedPath = $this->resolvePath($path, dirname($filePath), $projectRoot);

                    if ($resolvedPath === null) {
                        continue;
                    }

                    if (!file_exists($resolvedPath)) {
                        $issues[] = [
                            'type' => 'path_not_found',
                            'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                            'line' => $line,
                            'path' => $path,
                            'context' => $type,
                        ];
                        continue;
                    }

                    $caseIssue = $this->verifyPathCaseSensitivity(
                        $resolvedPath,
                        $path,
                        $filePath,
                        $line,
                        $type,
                        $projectRoot,
                    );
                    if ($caseIssue !== null) {
                        $issues[] = $caseIssue;
                    }
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'scan_error',
                'message' => "Error analizando rutas en {$dir}: " . $e->getMessage(),
            ];
        }

        return $issues;
    }

    private function scanStaticClassReferences(string $dir, string $projectRoot): array
    {
        $issues = [];

        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $references = $this->extractStaticClassReferences($filePath);

                foreach ($references as $ref) {
                    $lowercaseFqcn = strtolower($ref['resolved_fqcn']);

                    if (!isset($this->availableClasses[$lowercaseFqcn])) {
                        continue;
                    }

                    $correctFqcn = $this->availableClasses[$lowercaseFqcn];
                    if ($ref['resolved_fqcn'] === $correctFqcn) {
                        continue;
                    }

                    $issues[] = [
                        'type' => 'static_case_mismatch',
                        'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                        'line' => $ref['line'],
                        'used' => $ref['original'],
                        'resolved' => $ref['resolved_fqcn'],
                        'correct' => $correctFqcn,
                        'definition' => str_replace(
                            $projectRoot . DIRECTORY_SEPARATOR,
                            '',
                            $this->classToFile[$correctFqcn],
                        ),
                    ];
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'scan_error',
                'message' => "Error analizando llamadas estáticas en {$dir}: " . $e->getMessage(),
            ];
        }

        return $issues;
    }

    private function scanNewClassReferences(string $dir, string $projectRoot): array
    {
        $issues = [];

        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $references = $this->extractNewClassReferences($filePath);

                foreach ($references as $ref) {
                    $lowercaseFqcn = strtolower($ref['resolved_fqcn']);

                    if (!isset($this->availableClasses[$lowercaseFqcn])) {
                        continue;
                    }

                    $correctFqcn = $this->availableClasses[$lowercaseFqcn];
                    if ($ref['resolved_fqcn'] === $correctFqcn) {
                        continue;
                    }

                    $issues[] = [
                        'type' => 'new_case_mismatch',
                        'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                        'line' => $ref['line'],
                        'used' => $ref['original'],
                        'resolved' => $ref['resolved_fqcn'],
                        'correct' => $correctFqcn,
                        'definition' => str_replace(
                            $projectRoot . DIRECTORY_SEPARATOR,
                            '',
                            $this->classToFile[$correctFqcn],
                        ),
                    ];
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'scan_error',
                'message' => "Error analizando instanciaciones new en {$dir}: " . $e->getMessage(),
            ];
        }

        return $issues;
    }

    private function extractStaticClassReferences(string $file): array
    {
        $references = [];
        $tokens = $this->getTokens($file);

        $context = $this->extractNamespaceAndUseMap($tokens);
        $count = count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i] ?? null;

            if (!(is_array($token) && $token[0] === T_DOUBLE_COLON)) {
                continue;
            }

            $j = $i - 1;
            while ($j >= 0 && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                --$j;
            }

            $nameParts = [];
            $line = 0;
            while ($j >= 0) {
                $prev = $tokens[$j];

                if (is_array($prev)) {
                    if (in_array(
                        $prev[0],
                        [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED],
                        strict: true,
                    )) {
                        array_unshift($nameParts, $prev[1]);
                        if ($line === 0) {
                            $line = $prev[2] ?? 0;
                        }
                        --$j;
                        continue;
                    }

                    if ($prev[0] === T_WHITESPACE) {
                        --$j;
                        continue;
                    }
                }

                break;
            }

            if ($nameParts === []) {
                continue;
            }

            $originalName = implode('', $nameParts);
            $normalizedOriginal = ltrim($originalName, '\\');
            $lowerOriginal = strtolower($normalizedOriginal);

            if (in_array($lowerOriginal, ['self', 'static', 'parent'], strict: true)) {
                continue;
            }

            $resolved = $this->resolveClassNameFromContext(
                $normalizedOriginal,
                $context['namespace'],
                $context['uses'],
            );
            if ($resolved === null) {
                continue;
            }

            $references[] = [
                'original' => $originalName,
                'resolved_fqcn' => $resolved,
                'line' => $line,
            ];
        }

        return $references;
    }

    private function extractNewClassReferences(string $file): array
    {
        $references = [];
        $tokens = $this->getTokens($file);

        $context = $this->extractNamespaceAndUseMap($tokens);
        $count = count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i] ?? null;

            if (!(is_array($token) && $token[0] === T_NEW)) {
                continue;
            }

            $j = $i + 1;
            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                ++$j;
            }

            if ($j >= $count) {
                continue;
            }

            // Ignorar clases anónimas: new class (...) { ... }
            if (is_array($tokens[$j]) && $tokens[$j][0] === T_CLASS) {
                continue;
            }

            $nameParts = [];
            $line = is_array($tokens[$j]) ? $tokens[$j][2] ?? 0 : 0;

            while ($j < $count) {
                $current = $tokens[$j];

                if (is_array($current)) {
                    if (in_array(
                        $current[0],
                        [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED],
                        true,
                    )) {
                        $nameParts[] = $current[1];
                        ++$j;
                        continue;
                    }

                    if ($current[0] === T_WHITESPACE) {
                        ++$j;
                        continue;
                    }
                }

                break;
            }

            if ($nameParts === []) {
                continue;
            }

            $originalName = implode('', $nameParts);
            $normalizedOriginal = ltrim($originalName, '\\');

            $resolved = $this->resolveClassNameFromContext(
                $normalizedOriginal,
                $context['namespace'],
                $context['uses'],
            );
            if ($resolved === null) {
                continue;
            }

            $references[] = [
                'original' => $originalName,
                'resolved_fqcn' => $resolved,
                'line' => $line,
            ];
        }

        return $references;
    }

    private function extractNamespaceAndUseMap(array $tokens): array
    {
        $namespace = '';
        $uses = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i] ?? null;
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                ++$i;
                $ns = '';
                while ($i < $count) {
                    $current = $tokens[$i];
                    if ($current === ';' || $current === '{') {
                        break;
                    }
                    if (
                        is_array($current)
                        && in_array($current[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED], strict: true)
                    ) {
                        $ns .= $current[1];
                    }
                    ++$i;
                }
                $namespace = trim($ns, '\\');
                continue;
            }

            if ($token[0] !== T_USE) {
                continue;
            }

            // Ignorar closures: function (...) use (...)
            $prevSignificant = null;
            for ($k = $i - 1; $k >= 0; --$k) {
                $back = $tokens[$k];
                if (is_array($back) && $back[0] === T_WHITESPACE) {
                    continue;
                }
                $prevSignificant = $back;
                break;
            }
            if (is_array($prevSignificant) && $prevSignificant[0] === T_FUNCTION) {
                continue;
            }

            ++$i;
            $fqcn = '';
            $alias = '';
            $inAlias = false;

            while ($i < $count) {
                $current = $tokens[$i];

                if ($current === ';' || $current === ',') {
                    if ($fqcn !== '') {
                        $fqcnNormalized = ltrim(trim($fqcn), '\\');
                        $segments = explode('\\', $fqcnNormalized);
                        $defaultAlias = end($segments) ?: '';
                        $finalAlias = $alias !== '' ? $alias : $defaultAlias;
                        if ($finalAlias !== '') {
                            $uses[$finalAlias] = $fqcnNormalized;
                        }
                    }

                    if ($current === ';') {
                        break;
                    }

                    $fqcn = '';
                    $alias = '';
                    $inAlias = false;
                    ++$i;
                    continue;
                }

                if (is_array($current)) {
                    if ($current[0] === T_AS) {
                        $inAlias = true;
                        ++$i;
                        continue;
                    }

                    if (in_array(
                        $current[0],
                        [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED],
                        strict: true,
                    )) {
                        if ($inAlias) {
                            $alias .= $current[1];
                        } else {
                            $fqcn .= $current[1];
                        }
                    }
                }

                ++$i;
            }
        }

        return [
            'namespace' => $namespace,
            'uses' => $uses,
        ];
    }

    private function resolveClassNameFromContext(string $name, string $namespace, array $uses): ?string
    {
        if ($name === '') {
            return null;
        }

        // Nombre completamente calificado
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }

        // Resolver alias de use (primera parte del nombre)
        if (str_contains($name, '\\')) {
            [$firstSegment, $rest] = explode('\\', $name, 2);
            if (isset($uses[$firstSegment])) {
                return $uses[$firstSegment] . '\\' . $rest;
            }

            // Nombre calificado relativo al namespace actual
            return $namespace !== '' ? $namespace . '\\' . $name : $name;
        }

        if (isset($uses[$name])) {
            return $uses[$name];
        }

        return $namespace !== '' ? $namespace . '\\' . $name : $name;
    }

    private function extractFilePaths(string $file): array
    {
        $paths = [];
        $tokens = $this->getTokens($file);
        $count = count($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            $type = null;
            $line = $token[2] ?? 0;

            if (in_array($token[0], [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE], strict: true)) {
                $type = match ($token[0]) {
                    T_REQUIRE => 'require',
                    T_REQUIRE_ONCE => 'require_once',
                    T_INCLUDE => 'include',
                    T_INCLUDE_ONCE => 'include_once',
                };
            }

            if ($token[0] === T_STRING) {
                $funcName = strtolower($token[1]);
                if (in_array(
                    $funcName,
                    [
                        'file_exists',
                        'is_file',
                        'is_dir',
                        'is_readable',
                        'is_writable',
                        'fopen',
                        'file_get_contents',
                        'file_put_contents',
                        'readfile',
                        'unlink',
                        'copy',
                        'rename',
                        'mkdir',
                        'rmdir',
                        'scandir',
                        'glob',
                        'realpath',
                        'file',
                        'touch',
                        'chmod',
                    ],
                    strict: true,
                )) {
                    $type = $funcName;
                }
            }

            if ($type === null) {
                continue;
            }

            $path = $this->extractPathString($tokens, $i, $count);
            if ($path !== null) {
                $paths[] = [
                    'path' => $path,
                    'line' => $line,
                    'type' => $type,
                ];
            }
        }

        return $paths;
    }

    private function extractPathString(array $tokens, int $startIndex, int $count): ?string
    {
        $i = $startIndex + 1;

        while ($i < $count) {
            $t = $tokens[$i];
            if (is_array($t) && $t[0] === T_WHITESPACE) {
                ++$i;
                continue;
            }
            if ($t === '(') {
                ++$i;
                break;
            }
            ++$i;
        }

        while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
            ++$i;
        }

        if ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_CONSTANT_ENCAPSED_STRING) {
            $str = $tokens[$i][1];
            return trim($str, '\'"');
        }

        return null;
    }

    private function resolvePath(string $path, string $baseDir, string $projectRoot): ?string
    {
        if (str_contains($path, '$') || str_contains($path, '{')) {
            return null;
        }

        if ($path[0] === '/' || strlen($path) > 1 && $path[1] === ':') {
            return $path;
        }

        $resolved = $baseDir . DIRECTORY_SEPARATOR . $path;
        $realPath = realpath($resolved);

        return $realPath !== false ? $realPath : null;
    }

    private function verifyPathCaseSensitivity(
        string $resolvedPath,
        string $originalPath,
        string $sourceFile,
        int $line,
        string $type,
        string $projectRoot,
    ): ?array {
        $parts = explode(DIRECTORY_SEPARATOR, $resolvedPath);
        $current = '';

        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }

            if ($current === '') {
                $current = $part;
                if (strlen($part) === 2 && $part[1] === ':') {
                    continue;
                }
            } else {
                $current .= DIRECTORY_SEPARATOR . $part;
            }

            if (!file_exists($current)) {
                continue;
            }

            $parent = dirname($current);
            if (!is_dir($parent)) {
                continue;
            }

            $entries = scandir($parent);
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                if (strcasecmp($entry, $part) === 0 && $entry !== $part) {
                    return [
                        'type' => 'path_case_mismatch',
                        'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $sourceFile),
                        'line' => $line,
                        'path' => $originalPath,
                        'expected_case' => $entry,
                        'used_case' => $part,
                        'context' => $type,
                    ];
                }
            }
        }

        return null;
    }

    private function parsePhpFileFQCN(string $file): array
    {
        $details = $this->parsePhpFileDetails($file);

        return ['namespace' => $details['namespace'], 'class' => $details['class']];
    }

    private function verifyCaseSensitivity(
        string $filePath,
        string $baseDir,
        string $declaredFQCN,
        string $prefix,
        string $projectRoot,
    ): ?array {
        $nsSegments = explode('\\', ltrim($declaredFQCN, '\\'));
        $className = array_pop($nsSegments);

        $prefixTrim = trim($prefix, '\\');
        if ($prefixTrim !== '') {
            $prefixSegs = explode('\\', $prefixTrim);
            $match = true;
            foreach ($prefixSegs as $k => $pseg) {
                if (!(!isset($nsSegments[$k]) || strcasecmp($nsSegments[$k], $pseg) !== 0)) {
                    continue;
                }

                $match = false;
                break;
            }
            if ($match) {
                $nsSegments = array_slice($nsSegments, count($prefixSegs));
            }
        }

        $parts = $nsSegments;
        $parts[] = $className . '.php';

        $current = rtrim($baseDir, DIRECTORY_SEPARATOR);
        foreach ($parts as $part) {
            if (!is_dir($current) && !file_exists($current)) {
                break;
            }

            $entries = is_dir($current) ? scandir($current) : [];
            $matched = null;

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                if (strcasecmp($entry, $part) === 0) {
                    if ($entry !== $part) {
                        return [
                            'type' => 'case_mismatch',
                            'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                            'expected_case' => $part,
                            'actual_case' => $entry,
                            'context' => 'Estructura de directorios no coincide con namespace',
                        ];
                    }
                    $matched = $entry;
                    break;
                }
            }

            if ($matched === null) {
                return null;
            }

            $current .= DIRECTORY_SEPARATOR . $matched;
        }

        return null;
    }

    private function groupIssues(array $issues): array
    {
        $grouped = [];
        foreach ($issues as $issue) {
            $type = $issue['type'] ?? 'unknown';
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $issue;
        }

        return $grouped;
    }

    private function printIssueCategory(string $type, array $issues): void
    {
        $count = count($issues);

        $titles = [
            'fqcn_mismatch' => "Namespace no coincide con ruta ({$count})",
            'case_mismatch' => "Problema de mayúsculas/minúsculas en rutas ({$count})",
            'use_case_mismatch' => "Problema de mayúsculas/minúsculas en 'use' statements ({$count})",
            'static_case_mismatch' => "Problema de mayúsculas/minúsculas en llamadas estáticas Clase:: ({$count})",
            'new_case_mismatch' => "Problema de mayúsculas/minúsculas en instanciaciones new Clase ({$count})",
            'path_not_found' => "Rutas no encontradas en require/include ({$count})",
            'path_case_mismatch' => "Problema de mayúsculas/minúsculas en rutas de archivos ({$count})",
            'method_not_found' => "Método no encontrado ({$count})",
            'method_case_mismatch' => "Problema de mayúsculas/minúsculas en llamadas a métodos ({$count})",
            'missing_dir' => "Directorio no encontrado ({$count})",
            'scan_error' => "Errores de escaneo ({$count})",
        ];

        $title = $titles[$type] ?? "Otros problemas ({$count})";

        echo CLIErrorHandler::colorize("▸ {$title}", 'yellow') . "\n\n";

        foreach ($issues as $issue) {
            $this->printIssue($issue);
        }

        echo "\n";
    }

    private function printIssue(array $issue): void
    {
        switch ($issue['type']) {
            case 'fqcn_mismatch':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}\n";
                echo '    Declarado: ' . CLIErrorHandler::colorize($issue['declared'], 'cyan') . "\n";
                echo '    Esperado:  ' . CLIErrorHandler::colorize($issue['expected'], 'green') . "\n";
                break;

            case 'case_mismatch':
                echo ' ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}\n";
                echo '    Esperado: ' . CLIErrorHandler::colorize($issue['expected_case'], 'green') . "\n";
                echo '    Actual:   ' . CLIErrorHandler::colorize($issue['actual_case'], 'cyan') . "\n";
                if (isset($issue['context'])) {
                    echo '    ' . CLIErrorHandler::colorize($issue['context'], 'yellow') . "\n";
                }
                break;

            case 'use_case_mismatch':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}:{$issue['line']}\n";
                echo '    Usado:     ' . CLIErrorHandler::colorize($issue['used'], 'cyan') . "\n";
                echo '    Correcto:  ' . CLIErrorHandler::colorize($issue['correct'], 'green') . "\n";
                echo "    Definido en: {$issue['definition']}\n";
                break;

            case 'static_case_mismatch':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}:{$issue['line']}\n";
                echo '    Llamado:   ' . CLIErrorHandler::colorize($issue['used'] . '::', 'cyan') . "\n";
                echo '    Resuelto:  ' . CLIErrorHandler::colorize($issue['resolved'], 'yellow') . "\n";
                echo '    Correcto:  ' . CLIErrorHandler::colorize($issue['correct'], 'green') . "\n";
                echo "    Definido en: {$issue['definition']}\n";
                break;

            case 'new_case_mismatch':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}:{$issue['line']}\n";
                echo '    Instanciado: ' . CLIErrorHandler::colorize('new ' . $issue['used'], 'cyan') . "\n";
                echo '    Resuelto:    ' . CLIErrorHandler::colorize($issue['resolved'], 'yellow') . "\n";
                echo '    Correcto:    ' . CLIErrorHandler::colorize($issue['correct'], 'green') . "\n";
                echo "    Definido en: {$issue['definition']}\n";
                break;

            case 'static_call_on_non_static':
                echo '   ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}:{$issue['line']}\n";
                echo "    Clase:     {$issue['class']}\n";
                echo '    Método:     ' . CLIErrorHandler::colorize($issue['method'], 'cyan') . "\n";
                echo
                    '     '
                        . CLIErrorHandler::colorize('El método no es estático pero se llama usando ::', 'red')
                        . "\n\n"
                ;
                break;

            case 'path_not_found':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}:{$issue['line']}\n";
                echo "    Función:   {$issue['context']}\n";
                echo '    Ruta:      ' . CLIErrorHandler::colorize($issue['path'], 'cyan') . "\n";
                echo '    ' . CLIErrorHandler::colorize('Archivo no encontrado', 'red') . "\n";
                break;

            case 'path_case_mismatch':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}:{$issue['line']}\n";
                echo "    Función:   {$issue['context']}\n";
                echo "    Ruta:      {$issue['path']}\n";
                echo '    Usado:     ' . CLIErrorHandler::colorize($issue['used_case'], 'cyan') . "\n";
                echo '    Correcto:  ' . CLIErrorHandler::colorize($issue['expected_case'], 'green') . "\n";
                break;

            case 'method_not_found':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}:{$issue['line']}\n";
                echo "    Clase:     {$issue['class']}\n";
                echo '    Método:    ' . CLIErrorHandler::colorize($issue['method'], 'cyan') . "\n";
                echo '    ' . CLIErrorHandler::colorize('Método no encontrado', 'red') . "\n";
                break;

            case 'method_case_mismatch':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['file']}:{$issue['line']}\n";
                echo "    Clase:     {$issue['class']}\n";
                echo '    Usado:     ' . CLIErrorHandler::colorize($issue['used'], 'cyan') . "\n";
                echo '    Correcto:  ' . CLIErrorHandler::colorize($issue['correct'], 'green') . "\n";
                break;

            case 'missing_dir':
            case 'scan_error':
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . " {$issue['message']}\n";
                break;

            default:
                echo '  ' . CLIErrorHandler::colorize('✗', 'red') . ' Error desconocido: ' . json_encode($issue) . "\n";
        }
    }

    private function scanMethodReferences(string $dir, string $projectRoot): array
    {
        $issues = [];

        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $dir,
                RecursiveDirectoryIterator::SKIP_DOTS,
            ));

            foreach ($iterator as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }

                $filePath = $file->getRealPath();
                $details = $this->parsePhpFileDetails($filePath);
                $currentClass = $details['class'] !== ''
                    ? ($details['namespace'] ? $details['namespace'] . '\\' : '') . $details['class']
                    : null;

                $refs = $this->extractMethodReferences($filePath, $currentClass);

                foreach ($refs as $ref) {
                    $class = $ref['class'];
                    $method = $ref['method'];
                    $line = $ref['line'];
                    $lowercaseClass = strtolower($class);

                    if (!isset($this->availableClasses[$lowercaseClass])) {
                        continue;
                    }

                    $result = $this->checkMethodExists($lowercaseClass, $method, $ref['static']);

                    if ('not_found' === $result) {
                        $issues[] = [
                            'type' => 'method_not_found',
                            'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                            'line' => $line,
                            'class' => $class,
                            'method' => $method,
                        ];
                    } elseif ('static_error' === $result) {
                        $issues[] = [
                            'type' => 'static_call_on_non_static',
                            'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                            'line' => $line,
                            'class' => $class,
                            'method' => $method,
                        ];
                    } elseif (is_string($result)) {
                        $issues[] = [
                            'type' => 'method_case_mismatch',
                            'file' => str_replace($projectRoot . DIRECTORY_SEPARATOR, '', $filePath),
                            'line' => $line,
                            'class' => $class,
                            'used' => $method,
                            'correct' => $result,
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'scan_error',
                'message' => "Error analizando métodos en {$dir}: " . $e->getMessage(),
            ];
        }

        return $issues;
    }

    private function checkMethodExists(
        string $lowercaseClass,
        string $methodName,
        bool $mustBeStatic = false,
    ): bool|string {
        $lowerMethod = strtolower($methodName);

        if (isset($this->classMethods[$lowercaseClass])) {
            foreach ($this->classMethods[$lowercaseClass] as $m) {
                if (strtolower($m['name']) !== $lowerMethod) {
                    continue;
                }

                if ($mustBeStatic && !$m['static']) {
                    return 'static_error';
                }

                return $m['name'] === $methodName ? true : $m['name'];
            }
        }

        if (isset($this->classInheritance[$lowercaseClass])) {
            $inh = $this->classInheritance[$lowercaseClass];

            if ($inh['extends']) {
                $parent = $inh['extends'];
                if (!isset($this->availableClasses[$parent])) {
                    // Si el padre no está indexado (ej: vendor), asumimos que el método podría existir allí
                    return true;
                }
                $res = $this->checkMethodExists($parent, $methodName, $mustBeStatic);
                if ($res !== 'not_found' && false !== $res) {
                    return $res;
                }
            }

            foreach (array_merge($inh['implements'], $inh['traits']) as $ref) {
                if (!isset($this->availableClasses[$ref])) {
                    // Si la interfaz o trait no está indexado, asumimos que el método podría estar allí
                    return true;
                }
                $res = $this->checkMethodExists($ref, $methodName, $mustBeStatic);
                if ($res !== 'not_found' && false !== $res) {
                    return $res;
                }
            }
        }

        return 'not_found';
    }

    private function extractMethodReferences(string $file, ?string $currentClass): array
    {
        $refs = [];
        $tokens = $this->getTokens($file);
        $count = count($tokens);
        $context = $this->extractNamespaceAndUseMap($tokens);

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i];

            if (is_array($token) && T_VARIABLE === $token[0] && '$this' === $token[1]) {
                $next = $this->getNextSignificantTokenIndex($tokens, $i);
                if ($next !== null && is_array($tokens[$next]) && T_OBJECT_OPERATOR === $tokens[$next][0]) {
                    $next2 = $this->getNextSignificantTokenIndex($tokens, $next);
                    if ($next2 !== null && is_array($tokens[$next2]) && T_STRING === $tokens[$next2][0]) {
                        $next3 = $this->getNextSignificantTokenIndex($tokens, $next2);
                        if ($next3 !== null && '(' === $tokens[$next3]) {
                            if ($currentClass) {
                                $refs[] = [
                                    'class' => $currentClass,
                                    'method' => $tokens[$next2][1],
                                    'line' => $tokens[$next2][2],
                                    'static' => false,
                                ];
                            }
                        }
                    }
                }
            }

            if (is_array($token) && T_DOUBLE_COLON === $token[0]) {
                $prevIdx = $this->getPrevSignificantTokenIndex($tokens, $i);
                if ($prevIdx !== null) {
                    $prev = $tokens[$prevIdx];
                    $className = '';

                    if (
                        is_array($prev)
                        && in_array($prev[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], strict: true)
                    ) {
                        $className = $prev[1];
                    }

                    if ($className !== '') {
                        $resolvedClass = null;
                        if (in_array(strtolower($className), ['self', 'static'], strict: true)) {
                            $resolvedClass = $currentClass;
                        } else {
                            $resolvedClass = $this->resolveClassNameFromContext(
                                $className,
                                $context['namespace'],
                                $context['uses'],
                            );
                        }

                        if ($resolvedClass) {
                            $next = $this->getNextSignificantTokenIndex($tokens, $i);
                            if ($next !== null && is_array($tokens[$next]) && T_STRING === $tokens[$next][0]) {
                                $next2 = $this->getNextSignificantTokenIndex($tokens, $next);
                                if ($next2 !== null && '(' === $tokens[$next2]) {
                                    $refs[] = [
                                        'class' => $resolvedClass,
                                        'method' => $tokens[$next][1],
                                        'line' => $tokens[$next][2],
                                        'static' => true,
                                    ];
                                }
                            }
                        }
                    }
                }
            }

            if ('[' === $token || is_array($token) && T_ARRAY === $token[0]) {
                $idx = $this->getNextSignificantTokenIndex($tokens, $i);
                if (
                    $idx !== null
                    && is_array($tokens[$idx])
                    && in_array($tokens[$idx][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], strict: true)
                ) {
                    $className = $tokens[$idx][1];
                    $idx2 = $this->getNextSignificantTokenIndex($tokens, $idx);
                    if ($idx2 !== null && is_array($tokens[$idx2]) && T_DOUBLE_COLON === $tokens[$idx2][0]) {
                        $idx3 = $this->getNextSignificantTokenIndex($tokens, $idx2);
                        if ($idx3 !== null && is_array($tokens[$idx3]) && T_CLASS === $tokens[$idx3][0]) {
                            $idx4 = $this->getNextSignificantTokenIndex($tokens, $idx3);
                            if ($idx4 !== null && ',' === $tokens[$idx4]) {
                                $idx5 = $this->getNextSignificantTokenIndex($tokens, $idx4);
                                if (
                                    $idx5 !== null
                                    && is_array($tokens[$idx5])
                                    && T_CONSTANT_ENCAPSED_STRING === $tokens[$idx5][0]
                                ) {
                                    $method = trim($tokens[$idx5][1], "'\"");
                                    $resolvedClass = $this->resolveClassNameFromContext(
                                        $className,
                                        $context['namespace'],
                                        $context['uses'],
                                    );
                                    if ($resolvedClass) {
                                        $refs[] = [
                                            'class' => $resolvedClass,
                                            'method' => $method,
                                            'line' => $tokens[$idx5][2],
                                            'static' => false,
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $refs;
    }

    /**
     * Retorna los tokens PHP de un archivo, usando cache para evitar re-tokenización.
     *
     * @return array<mixed>
     */
    private function getTokens(string $filePath): array
    {
        if (!isset($this->tokenCache[$filePath])) {
            $this->tokenCache[$filePath] = token_get_all(file_get_contents($filePath));
        }

        return $this->tokenCache[$filePath];
    }

    /**
     * Indica si una clase (por su FQCN en minúsculas) pertenece a un namespace
     * que siempre se excluye del reporte de métodos muertos (cron, commands, etc.).
     */
    private function isAlwaysAliveClass(string $lcFqcn): bool
    {
        foreach ($this->alwaysAliveNamespacePrefixes as $prefix) {
            if (str_starts_with($lcFqcn, strtolower($prefix))) {
                return true;
            }
        }

        return false;
    }

    private function getNextSignificantTokenIndex(array $tokens, int $index): ?int
    {
        $i = $index + 1;
        while (isset($tokens[$i])) {
            if (
                is_array($tokens[$i])
                && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], strict: true)
            ) {
                ++$i;
                continue;
            }

            return $i;
        }

        return null;
    }

    private function getPrevSignificantTokenIndex(array $tokens, int $index): ?int
    {
        $i = $index - 1;
        while ($i >= 0) {
            if (
                is_array($tokens[$i])
                && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], strict: true)
            ) {
                --$i;
                continue;
            }

            return $i;
        }

        return null;
    }

    private function parsePhpFileDetails(string $file): array
    {
        $tokens = $this->getTokens($file);
        $count = count($tokens);

        $namespace = '';
        $class = '';
        $extends = null;
        $implements = [];
        $traits = [];
        $methods = [];

        // Extraer contexto una sola vez para eficiencia
        $context = $this->extractNamespaceAndUseMap($tokens);
        $namespace = $context['namespace'];

        for ($i = 0; $i < $count; ++$i) {
            $token = $tokens[$i];

            if (is_array($token) && in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT], strict: true)) {
                $prevIdx = $this->getPrevSignificantTokenIndex($tokens, $i);
                if ($prevIdx !== null && is_array($tokens[$prevIdx]) && T_NEW === $tokens[$prevIdx][0]) {
                    continue;
                }

                $i = $this->getNextSignificantTokenIndex($tokens, $i);
                if (isset($tokens[$i]) && is_array($tokens[$i]) && T_STRING === $tokens[$i][0]) {
                    $class = $tokens[$i][1];

                    $i++;
                    while ($i < $count && '{' !== $tokens[$i]) {
                        if (is_array($tokens[$i]) && T_EXTENDS === $tokens[$i][0]) {
                            $i = $this->getNextSignificantTokenIndex($tokens, $i);
                            $ext = '';
                            while (
                                isset($tokens[$i])
                                && is_array($tokens[$i])
                                && in_array($tokens[$i][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], strict: true)
                            ) {
                                $ext .= $tokens[$i][1];
                                ++$i;
                            }
                            $extends = $this->resolveClassNameFromContext($ext, $namespace, $context['uses']);
                            continue;
                        }

                        if (is_array($tokens[$i]) && T_IMPLEMENTS === $tokens[$i][0]) {
                            $i = $this->getNextSignificantTokenIndex($tokens, $i);
                            while (
                                isset($tokens[$i])
                                && '{' !== $tokens[$i]
                                && (is_string($tokens[$i]) || $tokens[$i][0] !== T_EXTENDS)
                            ) {
                                $impl = '';
                                while (
                                    isset($tokens[$i])
                                    && is_array($tokens[$i])
                                    && in_array(
                                        $tokens[$i][0],
                                        [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR],
                                        strict: true,
                                    )
                                ) {
                                    $impl .= $tokens[$i][1];
                                    $i = $this->getNextSignificantTokenIndex($tokens, $i);
                                }

                                if ($impl !== '') {
                                    $implements[] = $this->resolveClassNameFromContext(
                                        $impl,
                                        $namespace,
                                        $context['uses'],
                                    );
                                }

                                if (isset($tokens[$i]) && ',' === $tokens[$i]) {
                                    $i = $this->getNextSignificantTokenIndex($tokens, $i);
                                } else {
                                    break;
                                }
                            }
                            continue;
                        }
                        $i++;
                    }

                    if (isset($tokens[$i]) && '{' === $tokens[$i]) {
                        $depth = 1;
                        $i++;
                        while ($i < $count && $depth > 0) {
                            $t = $tokens[$i];

                            if (
                                '{' === $t
                                || is_array($t)
                                && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], strict: true)
                            ) {
                                ++$depth;
                            } elseif ('}' === $t) {
                                --$depth;
                            } elseif (1 === $depth && is_array($t)) {
                                if (T_USE === $t[0]) {
                                    $i = $this->getNextSignificantTokenIndex($tokens, $i);
                                    while (isset($tokens[$i]) && ';' !== $tokens[$i] && '{' !== $tokens[$i]) {
                                        if (
                                            is_array($tokens[$i])
                                            && in_array(
                                                $tokens[$i][0],
                                                [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR],
                                                strict: true,
                                            )
                                        ) {
                                            $trName = $tokens[$i][1];
                                            $traits[] = $this->resolveClassNameFromContext(
                                                $trName,
                                                $namespace,
                                                $context['uses'],
                                            );
                                        }
                                        $i++;
                                    }
                                } elseif (T_FUNCTION === $t[0]) {
                                    $isStatic = false;
                                    $isAbstract = false;
                                    $isPrivate = false;
                                    $prevIdx = $this->getPrevSignificantTokenIndex($tokens, $i);
                                    // Walk backwards through modifiers (static, abstract, private, protected, public)
                                    $checkIdx = $prevIdx;
                                    while ($checkIdx !== null && is_array($tokens[$checkIdx])) {
                                        $mod = $tokens[$checkIdx][0];
                                        if ($mod === T_STATIC) {
                                            $isStatic = true;
                                        }
                                        if ($mod === T_ABSTRACT) {
                                            $isAbstract = true;
                                        }
                                        if ($mod === T_PRIVATE) {
                                            $isPrivate = true;
                                        }
                                        if (in_array(
                                            $mod,
                                            [
                                                T_PUBLIC,
                                                T_PROTECTED,
                                                T_PRIVATE,
                                                T_STATIC,
                                                T_ABSTRACT,
                                                T_FINAL,
                                                T_READONLY,
                                            ],
                                            strict: true,
                                        )) {
                                            $checkIdx = $this->getPrevSignificantTokenIndex($tokens, $checkIdx);
                                        } else {
                                            break;
                                        }
                                    }

                                    $i = $this->getNextSignificantTokenIndex($tokens, $i);
                                    if (isset($tokens[$i]) && is_array($tokens[$i]) && T_STRING === $tokens[$i][0]) {
                                        $methods[] = [
                                            'name' => $tokens[$i][1],
                                            'static' => $isStatic,
                                            'abstract' => $isAbstract,
                                            'private' => $isPrivate,
                                            'line' => $tokens[$i][2] ?? 0,
                                        ];
                                    }
                                }
                            }
                            $i++;
                        }
                    }
                    break;
                }
            }
        }

        return [
            'namespace' => $namespace,
            'class' => $class,
            'extends' => $extends,
            'implements' => $implements,
            'traits' => $traits,
            'methods' => $methods,
        ];
    }
}
