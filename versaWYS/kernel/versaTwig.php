<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use DateTime;
use RuntimeException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\CssInliner\CssInlinerExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;
use versaWYS\kernel\helpers\Functions;

class versaTwig extends Environment
{
    private Environment $twigEnvironment;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config)
    {
        // Verificar usabilidad de twig
        $__TWIG_CACHE_PATH = $config['twig']['compiled_dir'];
        $__TWIG_READABLE_AND_WRITABLE = !is_readable($__TWIG_CACHE_PATH) || !is_writable($__TWIG_CACHE_PATH);
        if ($__TWIG_READABLE_AND_WRITABLE) {
            // Intentar solucionarlo
            if (!is_dir($__TWIG_CACHE_PATH)) {
                // Suprimir warnings de mkdir y capturar el error
                set_error_handler(
                    static function ($errno, $errstr) {
                        // No hacer nada, solo suprimir el warning
                    },
                    E_WARNING,
                );

                $mkdirSuccess = @mkdir($__TWIG_CACHE_PATH, 0o775, true);
                // 02775 = 0775 + setgid: los subdirs/archivos creados dentro heredan el grupo del dir
                @chmod($__TWIG_CACHE_PATH, 0o2775);

                restore_error_handler();

                if (!$mkdirSuccess) {
                    // Mostrar error detallado en pantalla con diseño corporativo
                    http_response_code(500);
                    // El detalle (rutas, usuario del proceso, comandos) solo se muestra al desarrollar
                    if (($config['build']['debug'] ?? false) !== true) {
                        error_log('versaWYS: no se puede crear el directorio de caché de Twig: ' . $__TWIG_CACHE_PATH);
                        echo '<h1>Error interno del servidor</h1><p>Por favor, contacte al administrador.</p>';
                        exit();
                    }
                    echo
                        '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>VersaWYS Error - Permisos</title><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;margin:0;padding:0;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}.error-container{background:white;padding:0;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:800px;margin:20px;overflow:hidden}.error-header{background:linear-gradient(135deg,#dc2626 0%,#991b1b 100%);color:white;padding:30px;text-align:center}.error-header h1{margin:0 0 10px;font-size:28px;font-weight:600}.error-header p{margin:0;opacity:0.9;font-size:16px}.error-body{padding:30px}.error-section{margin:20px 0;padding:20px;background:#f8fafc;border-left:4px solid #dc2626;border-radius:4px}.error-section h3{margin:0 0 15px;color:#1e293b;font-size:18px;font-weight:600}.code{background:#1e293b;color:#e2e8f0;padding:15px;border-radius:6px;font-family:"JetBrains Mono",Consolas,Monaco,monospace;font-size:13px;overflow-x:auto;line-height:1.6}.location{background:#fef3c7;padding:15px;border-radius:6px;margin:10px 0;border-left:4px solid #f59e0b}.info-grid{display:grid;grid-template-columns:120px 1fr;gap:10px;margin:10px 0}.info-grid strong{color:#475569}.solution-steps{background:white;padding:15px;border-radius:6px;border:2px solid #10b981}.solution-steps h4{margin:0 0 15px;color:#059669;font-size:16px}.command{background:#1e293b;color:#10b981;padding:12px;border-radius:4px;font-family:monospace;margin:8px 0;font-size:13px;cursor:pointer;transition:all 0.2s}.command:hover{background:#0f172a;transform:translateX(4px)}.footer{text-align:center;padding:20px;color:#64748b;font-size:14px}.icon{font-size:48px;margin-bottom:10px}</style></head><body><div class="error-container"><div class="error-header"><div class="icon">🚨</div><h1>VersaWYS Framework Error</h1><p>Error de Permisos del Sistema</p></div><div class="error-body"><div class="error-section"><h3>📁 Directorio No Disponible</h3><div class="code">'
                            . htmlspecialchars($__TWIG_CACHE_PATH)
                            . '</div></div><div class="error-section"><h3>⚠️ Descripción del Problema</h3><p>El sistema no puede crear o acceder al directorio de caché de Twig debido a restricciones de permisos.</p><div class="location"><div class="info-grid"><strong>Error:</strong><span>Permission denied (Permisos denegados)</span><strong>Usuario Web:</strong><span>'
                            . htmlspecialchars(get_current_user())
                            . '</span><strong>UID/GID:</strong><span>'
                            . getmyuid()
                            . ' / '
                            . getmygid()
                            . '</span><strong>Directorio:</strong><span>'
                            . htmlspecialchars($__TWIG_CACHE_PATH)
                            . '</span></div></div></div><div class="error-section"><div class="solution-steps"><h4>✅ Solución (Ejecutar en Terminal)</h4><div class="command">sudo mkdir -p '
                            . htmlspecialchars($__TWIG_CACHE_PATH)
                            . '</div><div class="command">sudo chown -R www-data:www-data '
                            . htmlspecialchars($__TWIG_CACHE_PATH)
                            . '</div><div class="command">sudo chmod -R 775 '
                            . htmlspecialchars($__TWIG_CACHE_PATH)
                            . '</div></div></div></div><div class="footer">VersaWYS Framework © 2026 | Sistema de Manejo de Errores</div></div></body></html>'
                    ;
                    exit();
                }
            } else {
                // 02775 = 0775 + setgid: los subdirs/archivos creados dentro heredan el grupo del dir
                @chmod($__TWIG_CACHE_PATH, 0o2775);
            }

            // Revisar la lectura para twig
            $__TWIG_READABLE_AND_WRITABLE = !is_readable($__TWIG_CACHE_PATH) || !is_writable($__TWIG_CACHE_PATH);
            if ($__TWIG_READABLE_AND_WRITABLE) {
                $error_message =
                    'Debe conceder permisos de escritura y lectura a la ruta '
                    . $__TWIG_CACHE_PATH
                    . ' ó crearla si no existe.';

                // Check if we're in debug mode and handle appropriately
                if (isset($config['build']['debug']) && $config['build']['debug']) {
                    // In debug mode, use ErrorHandler to show detailed error
                    Loggin::error('Error de permisos en Twig: ' . $error_message, 'versaTwig', [
                        'exception' => new RuntimeException($error_message),
                    ]);
                    exit();
                }

                // Check if this is an API request using our improved detection
                $isApi = $this->isStaticApiCall();

                if ($isApi) {
                    Response::jsonError([
                        'message' => 'Error interno del servidor',
                        'code' => 500,
                    ], 500);
                } else {
                    // For non-API requests in production, show simple HTML error
                    http_response_code(500);
                    echo '<h1>Error interno del servidor</h1><p>Por favor, contacte al administrador.</p>';
                }

                exit();
            }
        }

        $this->twigEnvironment = $this->init($config);
    }

    /**
     * Initializes the Twig environment with the given configuration.
     *
     * @param array $config the configuration settings for Twig
     *
     * @return Environment the initialized Twig environment
     */
    /**
     * @param array<string, mixed> $config
     */
    public function init(array $config): Environment
    {
        $loader = new FilesystemLoader($config['twig']['templates_dir']);
        $twigInit = new Environment($loader, [
            'cache' => $config['twig']['cache'] ? $config['twig']['compiled_dir'] : false,
            'debug' => $config['build']['debug'],
            'charset' => $config['build']['charset'],
            'auto_escape' => $config['twig']['auto_escape'],
            'strict_variables' => $config['build']['debug']
                ? $config['twig']['strict_variables']
                : $config['build']['debug'],
        ]);

        if ($config['build']['debug']) {
            $twigInit->addExtension(new DebugExtension());
        }

        $twigInit->addExtension(new CssInlinerExtension());

        $this->setFilterTwig($twigInit);
        $this->setFunctionTwig($twigInit);

        return $twigInit;
    }

    /**
     * Adds a global variable to the Twig environment.
     *
     * @param string $name the name of the global variable
     * @param mixed $value the value of the global variable
     */
    public function addGlobal(string $name, $value): void
    {
        $this->twigEnvironment->addGlobal($name, $value);
    }

    /**
     * Adds an extension to the Twig environment.
     *
     * @param mixed $extension the extension to be added
     */
    public function addExtension($extension): void
    {
        if ($extension instanceof ExtensionInterface) {
            $this->twigEnvironment->addExtension($extension);
        }
    }

    /**
     * Adds a filter to the Twig environment.
     *
     * @param TwigFilter $filter the filter to be added
     */
    public function addFilter(TwigFilter $filter): void
    {
        $this->twigEnvironment->addFilter($filter);
    }

    /**
     * Renders a Twig template with the given context and returns the result as a string.
     * Supports SPA navigation by returning JSON when requested via AJAX.
     *
     * @param string|TemplateWrapper $name the path to the Twig template file
     * @param array $context the variables to be passed to the template
     *
     * @return string the rendered template as a string
     */
    /**
     * @param string|TemplateWrapper $name
     * @param array<string, mixed> $context
     */
    public function render($name, array $context = []): string
    {
        if (is_string($name) && !str_ends_with($name, '.twig')) {
            $name .= '.twig';
        }

        try {
            $result = $this->twigEnvironment->render($name, $context);

            // Detectar si es una petición SPA y manejarla
            if ($this->isSPARequest()) {
                $this->handleSPAResponse($name, $result, $context);

                return ''; // No retornamos nada porque ya enviamos la respuesta JSON
            }

            return $result . $this->debug();
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->catch($e);

            return '';
        }
    }

    /**
     * Renderiza una plantilla directamente sin aplicar transformación SPA.
     * Útil para páginas de error que siempre deben mostrarse como HTML.
     *
     * @param string|TemplateWrapper $name
     * @param array<string, mixed> $context
     * @return string
     */
    public function renderDirect($name, array $context = []): string
    {
        if (is_string($name) && !str_ends_with($name, '.twig')) {
            $name .= '.twig';
        }

        try {
            return $this->twigEnvironment->render($name, $context);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            $this->catch($e);

            return '';
        }
    }

    public static function errorHandler($errno, $errstr, $errfile, $errline): void
    {
        global $versawys_error_handling_in_progress;

        // Si ya se está manejando un error, evitar duplicación
        if ($versawys_error_handling_in_progress) {
            return;
        }

        Response::jsonError([
            'success' => 0,
            'message' => $errstr,
            'code' => $errno,
            'line' => $errline,
            'file' => $errfile,
        ], 500);
        exit();
    }

    /**
     * Detects if the current request is a SPA navigation request.
     * Delega al método centralizado de Request.
     *
     * @return bool true if it's a SPA request, false otherwise
     */
    private function isSPARequest(): bool
    {
        return Request::isSPARequestStatic();
    }

    /**
     * Handles SPA response by sending appropriate JSON data.
     *
     * @param string $templateName the name of the template being rendered
     * @param string $renderedContent the rendered HTML content
     * @param array $context the template context
     */
    /**
     * @param array<string, mixed> $context
     */
    private function handleSPAResponse(string $templateName, string $renderedContent, array $context): void
    {
        global $request, $session;
        // Determinar el tipo de respuesta basado en el template
        $isVueTemplate = $this->isVueTemplate($templateName, $context);

        if ($isVueTemplate) {
            // Para templates Vue (como loader.twig)
            $spaContent = $this->renderSPAContent();
            $module = $context['m'] ?? null;

            Response::json([
                'type' => 'vue',
                'content' => $spaContent,
                'module' => $module,
                'csrf_token' => $session->get('csrf_token') ?? '',
                'current_user' => $this->getCurrentUserData(),
                'urlActual' => $request->getUrl(),
                // Incluir scripts y styles específicos para Vue templates también
                'scripts' => $this->extractPageSpecificScripts($renderedContent),
                'styles' => $this->extractPageSpecificStyles($renderedContent),
            ]);
        } else {
            // Para templates HTML normales (login, lost-password, etc.)
            $extractedContent = $this->extractBodyContent($renderedContent);

            Response::json([
                'type' => 'html',
                'content' => $extractedContent,
                'csrf_token' => $_SESSION['csrf_token'] ?? '',
                'urlActual' => $request->getUrl(),
                // Incluir scripts específicos de página (filtrados)
                'scripts' => $this->extractPageSpecificScripts($renderedContent),
                'styles' => $this->extractPageSpecificStyles($renderedContent),
            ]);
        }
    }

    /**
     * Determines if the template is a Vue template based on name and context.
     *
     * @param string $templateName the name of the template
     * @param array $context the template context
     *
     * @return bool true if it's a Vue template, false otherwise
     */
    /**
     * @param array<string, mixed> $context
     */
    private function isVueTemplate(string $templateName, array $context): bool
    {
        // Si el template es loader.twig o tiene parámetro 'm', es Vue
        return str_contains($templateName, 'loader.twig') || isset($context['m']);
    }

    /**
     * Renders the basic content structure for SPA Vue components.
     *
     * @return string the basic HTML content for Vue components
     */
    private function renderSPAContent(): string
    {
        try {
            return $this->twigEnvironment->render('dashboard/spa-content.twig', [
                'current_user' => $this->getCurrentUserData(),
            ]);
        } catch (LoaderError|RuntimeError|SyntaxError) {
            // Fallback simple si no se puede renderizar el template
            $currentUser = json_encode($this->getCurrentUserData());
            $csrfField = Functions::csrf_field();

            return (
                $csrfField
                . '<input type="hidden" id="current_user" value=\''
                . htmlspecialchars($currentUser, ENT_QUOTES, 'UTF-8')
                . "'>"
            );
        }
    }

    /**
     * Gets the current user data for SPA requests.
     *
     * @return array the user data array
     */
    /**
     * @return array<string, mixed>
     */
    private function getCurrentUserData(): array
    {
        global $session;
        $userId = (int) $session->get('id_user');
        $empresaId = (int) $session->get('empresa_id');
        // Generar clave de caché única
        $cacheKey = sprintf('user_data_%d_%d', $userId, $empresaId);
        $cacheData = $session->get($cacheKey);

        return is_array($cacheData) && isset($cacheData['user']) ? $cacheData['user'] : [];
    }

    /**
     * This private method generates the debug HTML code.
     * If the debug mode is enabled in the configuration, it returns the debug HTML code; otherwise, it returns an empty string.
     *
     * @return string the debug HTML code
     */
    private function debug(): string
    {
        global $config;
        if (!$config['build']['debug']) {
            return '';
        }

        $version = $config['framework'];

        $dataNow = new DateTime();
        $dataNow = $dataNow->format('Y-m-d H:i:s');

        $cacheRouteStatus = 'Desactivado';
        if (isset($config['build']['cacheRoute'])) {
            $useCache = $config['build']['cacheRoute'] ?? false;
            $cacheFile = $config['build']['cacheRouteFile'] ?? './versaWYS/cache/routes.compiled.php';
            $minifiedFile = str_replace('.compiled.php', '.compiled.min.php', $cacheFile);

            if ($useCache && (file_exists($minifiedFile) || file_exists($cacheFile))) {
                $cacheRouteStatus = file_exists($minifiedFile) ? 'Activado (Min)' : 'Activado';
            }
        }

        return sprintf(
            "<div class='flex justify-between w-full bottom-0 left-0 bg-red-900 text-white fixed z-50 p-2 animate-Debugfade' id='debug'><div class='flex justify-between gap-2'><span>Modo Debug: Activado</span>-<span>Cache de Rutas: %s </span></div><div class='flex items-center gap-1'><img class='w-6 h-6' src='/public/dashboard/img/favicon.webp'></img>VersaWYS Framework </div><div id='tiempoCarga'></div></div><script type='module'>console.log('%%c%s', 'color: #fff; background-color: #f00; padding: 5px; border-radius: 5px; font-size: 1em;');const antes=new Date('%s').getTime(); function tdc(){ return (new Date().getTime() - antes) / 1e3 + 's';} window.onload=function (){ document.getElementById('tiempoCarga').innerHTML='CARGA: ' + tdc();}; </script>",
            $cacheRouteStatus,
            $version,
            $dataNow,
        );
    }

    /**
     * Sets the Twig filters for the given Twig environment.
     *
     * @param Environment $twig the Twig environment to set the filters for
     */
    private function setFilterTwig(Environment $twig): void
    {
        $rutaCarpeta = __DIR__ . '/helpers';

        if ($handle = opendir($rutaCarpeta)) {
            while (false !== ($entry = readdir($handle))) {
                if (!($entry !== '.' && $entry !== '..')) {
                    continue;
                }

                $rutaArchivo = $rutaCarpeta . '/' . $entry;
                if (is_file($rutaArchivo) && pathinfo($rutaArchivo, PATHINFO_EXTENSION) === 'php') {
                    $nombreClase = pathinfo($rutaArchivo, PATHINFO_FILENAME);
                    $class = 'versaWYS\\kernel\\helpers\\' . $nombreClase;
                    $class = str_replace('.php', '', $class);

                    $classInstancia = new $class();
                    if (method_exists($classInstancia, 'setTwigFilters')) {
                        $filters = $classInstancia->setTwigFilters();
                        foreach ($filters as $filter) {
                            [$nombre, $funcion] = $filter;
                            $twig->addFilter(new TwigFilter($nombre, $funcion));
                        }
                    }
                }
            }

            closedir($handle);
        }
    }

    private function setFunctionTwig(Environment $twig): void
    {
        $rutaCarpeta = __DIR__ . '/helpers';

        if ($handle = opendir($rutaCarpeta)) {
            while (false !== ($entry = readdir($handle))) {
                if (!($entry !== '.' && $entry !== '..')) {
                    continue;
                }

                $rutaArchivo = $rutaCarpeta . '/' . $entry;
                if (is_file($rutaArchivo) && pathinfo($rutaArchivo, PATHINFO_EXTENSION) === 'php') {
                    $nombreClase = pathinfo($rutaArchivo, PATHINFO_FILENAME);
                    $class = 'VersaWYS\\kernel\\helpers\\' . $nombreClase;
                    $class = str_replace('.php', '', $class);

                    $classInstancia = new $class();
                    if (method_exists($classInstancia, 'setTwigFunctions')) {
                        $functions = $classInstancia->setTwigFunctions();
                        foreach ($functions as $function) {
                            [$nombre, $funcion] = $function;
                            $twig->addFunction(new TwigFunction($nombre, $funcion));
                        }
                    }
                }
            }

            closedir($handle);
        }
    }

    private function catch(LoaderError|RuntimeError|SyntaxError $e): void
    {
        global $config, $versawys_error_handling_in_progress;

        // Si ya se está manejando un error, evitar duplicación
        if ($versawys_error_handling_in_progress) {
            // Solo mostrar error simple para evitar loops
            http_response_code(500);
            echo 'Twig template error occurred during error handling. Message: ' . htmlspecialchars($e->getMessage());
            exit();
        }

        // Marcar que estamos manejando un error
        $versawys_error_handling_in_progress = true;

        // Protección contra loops infinitos
        static $catchCount = 0;
        $catchCount++;

        if ($catchCount > 2) {
            // Si ya hemos capturado demasiados errores, salir con error simple
            http_response_code(500);
            echo 'Error loop detected in Twig rendering. Message: ' . htmlspecialchars($e->getMessage());
            exit();
        }

        // Obtener información detallada del error
        $trace = $e->getTrace();
        $realOrigin = $this->findUserCodeOrigin($trace);

        $errorData = [
            'success' => 0,
            'type' => 'Twig Template Error',
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'template_file' => $e->getFile(),
            'template_line' => $e->getLine(),
            'real_origin' => $realOrigin,
            'user_stack_trace' => $this->buildUserStackTrace($trace),
            'timestamp' => date('Y-m-d H:i:s'),
            // Agregar campos que espera la plantilla
            'real_file' => $realOrigin['file'] ?? null,
            'real_line' => $realOrigin['line'] ?? null,
            'real_function' => $realOrigin['function'] ?? null,
            'real_class' => $realOrigin['class'] ?? null,
        ];

        if ($config['build']['debug']) {
            $errorData['full_stack'] = $e->getTraceAsString();
            $errorData['errors'] = error_get_last();
            $errorData['context'] = [
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            ];
        } else {
            $errorData = [
                'success' => 0,
                'message' => 'Internal Server Error',
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }

        Response::jsonError($errorData, 500);
    }

    /**
     * Encuentra el origen real del error en el código del usuario.
     */
    /**
     * @param array<int, mixed> $trace
     *
     * @return array<string, mixed>
     */
    private function findUserCodeOrigin(array $trace): array
    {
        $skipPaths = ['/vendor/', '/twig/', 'versaTwig.php', '/cache/', '/compiled/'];

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            $shouldSkip = false;
            foreach ($skipPaths as $skipPath) {
                if (!str_contains($frame['file'], $skipPath)) {
                    continue;
                }

                $shouldSkip = true;
                break;
            }

            if (!$shouldSkip) {
                return [
                    'file' => str_replace(getcwd(), '', $frame['file']),
                    'line' => $frame['line'] ?? 'Unknown',
                    'function' => $frame['function'] ?? 'Unknown',
                    'class' => $frame['class'] ?? null,
                ];
            }
        }

        return ['file' => 'Unknown', 'line' => 'Unknown', 'function' => 'Unknown'];
    }

    /**
     * Construye un stack trace enfocado en el código del usuario.
     */
    /**
     * @param array<int, mixed> $trace
     *
     * @return array<int, mixed>
     */
    private function buildUserStackTrace(array $trace): array
    {
        $userTrace = [];
        $skipPaths = ['/vendor/', '/twig/', '/cache/', '/compiled/'];

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            $shouldSkip = false;
            foreach ($skipPaths as $skipPath) {
                if (!str_contains($frame['file'], $skipPath)) {
                    continue;
                }

                $shouldSkip = true;
                break;
            }

            if (!$shouldSkip) {
                $userTrace[] = [
                    'file' => str_replace(getcwd(), '', $frame['file']),
                    'line' => $frame['line'] ?? 'Unknown',
                    'function' => $frame['function'] ?? 'Unknown',
                    'class' => $frame['class'] ?? null,
                ];
            }
        }

        return $userTrace;
    }

    /**
     * Método estático para determinar si es una llamada API sin necesidad de instancia de Request.
     * Útil para usar en contextos donde Request no está disponible.
     */
    private function isStaticApiCall(): bool
    {
        $url = strtolower($_SERVER['REQUEST_URI'] ?? '');
        $acceptHeader = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $headers = getallheaders();

        if (isset($headers['X-Spa'])) {
            return false;
        }

        // Comprobación prioritaria por URL
        if (str_contains($url, '/api/')) {
            return true;
        }

        // Comprobación por encabezado Accept, común para APIs RESTful
        if (str_contains($acceptHeader, 'application/json')) {
            return true;
        }

        // Comprobación por Content-Type para solicitudes con cuerpo (POST, PUT, PATCH)
        if ($contentType !== '') {
            $apiContentTypes = [
                'application/json',
                'application/xml',
                'multipart/form-data',
                'application/x-www-form-urlencoded',
            ];

            foreach ($apiContentTypes as $type) {
                if (!str_starts_with(strtolower($contentType), $type)) {
                    continue;
                }

                return true;
            }
        }

        // Si un cliente XHR (como fetch o XMLHttpRequest) establece X-Requested-With
        return isset($headers['X-Requested-With']) && strtolower($headers['X-Requested-With']) === 'xmlhttprequest';
    }

    /**
     * Extracts the body content from rendered HTML, removing head and other elements.
     *
     * @param string $content the rendered HTML content
     *
     * @return string the body content only
     */
    private function extractBodyContent(string $content): string
    {
        // Si el contenido ya es un fragmento (no tiene html/body tags), devolver tal como está
        if (!str_contains($content, '<html') && !str_contains($content, '<body')) {
            return $content;
        }

        // Para páginas SPA, extraer solo el contenido del div#content-general
        // Usar múltiples patrones para mayor compatibilidad
        $patterns = [
            '/<div[^>]*id\s*=\s*["\']content-general["\'][^>]*>(.*)<\/div>\s*<\/body>/is',
            '/<div[^>]*id\s*=\s*["\']content-general["\'][^>]*>(.*)<\/div>/is',
            '/<div\s+id="content-general"[^>]*>(.*)<\/div>/is',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $content, $matches)) {
                continue;
            }

            return trim($matches[1]);
        }

        // Fallback: extraer contenido del body completo
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $content, $matches)) {
            return trim($matches[1]);
        }

        return $content;
    }

    /**
     * Extracts only page-specific scripts, filtering out global ones.
     *
     * @param string $content the rendered HTML content
     *
     * @return array array of page-specific script URLs and inline scripts
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractPageSpecificScripts(string $content): array
    {
        $scripts = [];

        // Scripts del sistema que nunca deben reenviarse al frontend SPA
        $globalScripts = [
            'spa-navigation.js',
            'vue-loader.js',
            'dashboard.js',
            'bootstrap.js',
            'jquery.js',
            'flowbite.min.js',
            'early-init.js',
        ];

        // Extraer todos los scripts del HTML renderizado
        if (preg_match_all('/<script[^>]*src=["\']([^"\']+)["\'][^>]*><\/script>/i', $content, $matches)) {
            foreach ($matches[1] as $src) {
                // Verificar si no es un script global
                $isGlobal = false;
                foreach ($globalScripts as $globalScript) {
                    if (!str_contains($src, $globalScript)) {
                        continue;
                    }

                    $isGlobal = true;
                    break;
                }

                if (!$isGlobal && ($src !== '' && $src !== '0')) {
                    $scripts[] = [
                        'type' => 'external',
                        'src' => $src,
                    ];
                }
            }
        }

        // Extraer scripts inline del bloque appScript
        if (preg_match_all('/<script[^>]*>([^<]+)<\/script>/i', $content, $matches)) {
            foreach ($matches[1] as $inlineScript) {
                $script = trim($inlineScript);
                // Usar el método de validación dinámico
                if (!$this->isInvalidInlineScript($script)) {
                    $scripts[] = [
                        'type' => 'inline',
                        'content' => $script,
                    ];
                }
            }
        }

        return $scripts;
    }

    /**
     * Extracts page-specific styles and ensures core styles are included.
     *
     * @param string $content the rendered HTML content
     *
     * @return array array of CSS URLs and inline styles needed for the page
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractPageSpecificStyles(string $content): array
    {
        $styles = [];

        // Extraer links de CSS del contenido HTML
        if (preg_match_all(
            '/<link[^>]+href=["\']([^"\']+)["\'][^>]*rel=["\']stylesheet["\'][^>]*>/i',
            $content,
            $matches,
        )) {
            foreach ($matches[1] as $href) {
                // Filtrar estilos que ya están en el layout principal
                if ($this->isGlobalStyle($href)) {
                    continue;
                }

                $styles[] = [
                    'type' => 'link',
                    'href' => $href,
                ];
            }
        }

        // Extraer estilos inline del contenido
        if (preg_match_all('/<style[^>]*>([^<]+)<\/style>/i', $content, $matches)) {
            foreach ($matches[1] as $inlineStyle) {
                $style = trim($inlineStyle);
                if ($style !== '' && $style !== '0') {
                    $styles[] = [
                        'type' => 'inline',
                        'content' => $style,
                    ];
                }
            }
        }

        // Para navegación SPA, asegurarnos de que ciertos estilos críticos estén disponibles
        // si no se detectan como ya cargados
        $criticalStyles = $this->getCriticalStyles();
        if (is_array($criticalStyles)) {
            foreach ($criticalStyles as $criticalStyle) {
                $styles[] = $criticalStyle;
            }
        }

        return $styles;
    }

    /**
     * Checks if a style URL is a global style that should not be reloaded.
     *
     * @param string $href the CSS file URL
     *
     * @return bool true if it's a global style, false otherwise
     */
    private function isGlobalStyle(string $href): bool
    {
        $globalStyles = ['bootstrap-icons.min.css', 'flowbite.min.css', 'dashboard.css'];

        foreach ($globalStyles as $globalStyle) {
            if (!str_contains($href, $globalStyle)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Gets critical styles that should be available during SPA navigation.
     * Los estilos globales (flowbite, dashboard.css, bootstrap-icons) ya están cargados
     * en el layout principal y no necesitan reenviarse en cada navegación SPA.
     * Solo se incluirían estilos críticos dinámicos que varíen por página.
     *
     * @return array array of critical styles (vacío por defecto para evitar payload innecesario)
     */
    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCriticalStyles(): array
    {
        return [];
    }

    /**
     * Checks if an inline script content is valid JavaScript and safe to execute.
     *
     * @param string $script the script content to validate
     *
     * @return bool true if the script is invalid/unsafe, false if it's safe
     */
    private function isInvalidInlineScript(string $script): bool
    {
        // Filtrar scripts vacíos
        if (in_array(trim($script), ['', '0'], true)) {
            return true;
        }

        // Filtrar comentarios únicamente
        if (preg_match('/^\s*(\/\/|\/\*|\*)/', $script) !== false && preg_match('/^\s*(\/\/|\/\*|\*)/', $script) > 0) {
            return true;
        }

        // Filtrar contenido que no es JavaScript
        $invalidPatterns = [
            '<?php', // Código PHP
            '{{', // Templates Twig/Blade
            '{%', // Templates Twig
            'Twig', // Referencias a Twig
            'undefined:', // Errores comunes
            'Unexpected token:', // Errores de sintaxis
        ];

        foreach ($invalidPatterns as $pattern) {
            if (!str_contains($script, $pattern)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
