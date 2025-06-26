<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use RuntimeException;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Extra\CssInliner\CssInlinerExtension;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;

class versaTwig extends Environment
{
    private Environment $twigEnvironment;

    public function __construct($config)
    {
        # Verificar usabilidad de twig
        $__TWIG_CACHE_PATH = $config['twig']['compiled_dir'];
        $__TWIG_READABLE_AND_WRITABLE = !is_readable($__TWIG_CACHE_PATH) || !is_writable($__TWIG_CACHE_PATH);
        if ($__TWIG_READABLE_AND_WRITABLE) {
            # Intentar solucionarlo
            if (!is_dir($__TWIG_CACHE_PATH)) {
                mkdir($__TWIG_CACHE_PATH, 2774, true);
            } else {
                chmod($__TWIG_CACHE_PATH, 2774);
            }

            # Revisar la lecutra para twig
            $__TWIG_READABLE_AND_WRITABLE = !is_readable($__TWIG_CACHE_PATH) || !is_writable($__TWIG_CACHE_PATH);
            if ($__TWIG_READABLE_AND_WRITABLE) {
                throw new RuntimeException(
                    'Debe conceder permisos de escritura y lectura a la ruta ' .
                        $__TWIG_CACHE_PATH .
                        ' ó crearla si no existe.'
                );
            }
        }

        $this->twigEnvironment = $this->init($config);
    }

    /**
     * Initializes the Twig environment with the given configuration.
     *
     * @param array $config The configuration settings for Twig.
     * @return Environment The initialized Twig environment.
     */
    public function init(array $config): Environment
    {
        $loader = new FilesystemLoader($config['twig']['templates_dir']);
        $twigInit = new Environment($loader, [
            'cache' => $config['twig']['cache'] ? $config['twig']['compiled_dir'] : false,
            'debug' => $config['build']['debug'],
            'charset' => $config['build']['charset'],
            'auto_escape' => $config['twig']['auto_escape'],
            'strict_variables' => !$config['build']['debug']
                ? $config['build']['debug']
                : $config['twig']['strict_variables'],
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
     * @param string $name The name of the global variable.
     * @param mixed $value The value of the global variable.
     * @return void
     */
    public function addGlobal(string $name, $value): void
    {
        $this->twigEnvironment->addGlobal($name, $value);
    }

    /**
     * Adds an extension to the Twig environment.
     *
     * @param mixed $extension The extension to be added.
     * @return void
     */
    public function addExtension($extension): void
    {
        $this->twigEnvironment->addExtension($extension);
    }

    /**
     * Adds a filter to the Twig environment.
     *
     * @param TwigFilter $filter The filter to be added.
     * @return void
     */
    public function addFilter(TwigFilter $filter): void
    {
        $this->twigEnvironment->addFilter($filter);
    }

    /**
     * Renders a Twig template with the given context and returns the result as a string.
     *
     * @param string|TemplateWrapper $name The path to the Twig template file.
     * @param array $context The variables to be passed to the template.
     * @return string The rendered template as a string.
     */
    public function render($name, array $context = []): string
    {
        if (!str_ends_with($name, '.twig')) {
            $name .= '.twig';
        }
        try {
            $result = $this->twigEnvironment->render($name, $context);
            return $result . self::debug();
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
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

        Response::jsonError(
            [
                'success' => 0,
                'message' => $errstr,
                'code' => $errno,
                'line' => $errline,
                'file' => $errfile,
            ],
            500
        );
        exit();
    }

    /**
     * This private method generates the debug HTML code.
     * If the debug mode is enabled in the configuration, it returns the debug HTML code; otherwise, it returns an empty string.
     *
     * @return string The debug HTML code.
     */
    private function debug(): string
    {
        global $config;
        if (!$config['build']['debug']) {
            return '';
        }

        $version = $config['framework'];

        $dataNow = new \DateTime();
        $dataNow = $dataNow->format('Y-m-d H:i:s');

        return "<div class='flex justify-between w-full bottom-0 left-0 bg-red-900 text-white fixed z-50 p-2 animate-Debugfade' id='debug'><div class='flex items-center'>Modo Debug: Activado</div><div class='flex items-center gap-1'><img class='w-6 h-6' src='/public/dashboard/img/favicon.webp'></img>VersaWYS Framework </div><div id='tiempoCarga'></div></div><script type='module'>console.log('%c$version', 'color: #fff; background-color: #f00; padding: 5px; border-radius: 5px; font-size: 1em;');const antes=new Date('$dataNow').getTime(); function tdc(){ return (new Date().getTime() - antes) / 1e3 + 's';} window.onload=function (){ document.getElementById('tiempoCarga').innerHTML='CARGA: ' + tdc();}; </script>";
    }

    /**
     * Sets the Twig filters for the given Twig environment.
     *
     * @param Environment $twig The Twig environment to set the filters for.
     * @return void
     */
    private function setFilterTwig(Environment $twig): void
    {
        $rutaCarpeta = __DIR__ . '/helpers';

        if ($handle = opendir($rutaCarpeta)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    $rutaArchivo = $rutaCarpeta . '/' . $entry;
                    if (is_file($rutaArchivo) && pathinfo($rutaArchivo, PATHINFO_EXTENSION) == 'php') {
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
            }
            closedir($handle);
        }
    }

    private function setFunctionTwig($twig): void
    {
        $rutaCarpeta = __DIR__ . '/helpers';

        if ($handle = opendir($rutaCarpeta)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != '.' && $entry != '..') {
                    $rutaArchivo = $rutaCarpeta . '/' . $entry;
                    if (is_file($rutaArchivo) && pathinfo($rutaArchivo, PATHINFO_EXTENSION) == 'php') {
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
            }
            closedir($handle);
        }
    }

    private function catch($e): void
    {
        global $config, $versawys_error_handling_in_progress;

        // Si ya se está manejando un error, evitar duplicación
        if ($versawys_error_handling_in_progress) {
            // Solo mostrar error simple para evitar loops
            http_response_code(500);
            echo "Twig template error occurred during error handling. Message: " . htmlspecialchars($e->getMessage());
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
            echo "Error loop detected in Twig rendering. Message: " . htmlspecialchars($e->getMessage());
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
            'real_class' => $realOrigin['class'] ?? null
        ];

        if ($config['build']['debug']) {
            $errorData['full_stack'] = $e->getTraceAsString();
            $errorData['errors'] = error_get_last();
            $errorData['context'] = [
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown'
            ];
        } else {
            $errorData = [
                'success' => 0,
                'message' => 'Internal Server Error',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        Response::jsonError($errorData, 500);
    }

    /**
     * Encuentra el origen real del error en el código del usuario
     */
    private function findUserCodeOrigin(array $trace): array
    {
        $skipPaths = [
            '/vendor/',
            '/twig/',
            'versaTwig.php',
            '/cache/',
            '/compiled/'
        ];

        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $shouldSkip = false;
                foreach ($skipPaths as $skipPath) {
                    if (strpos($frame['file'], $skipPath) !== false) {
                        $shouldSkip = true;
                        break;
                    }
                }

                if (!$shouldSkip) {
                    return [
                        'file' => str_replace(getcwd(), '', $frame['file']),
                        'line' => $frame['line'] ?? 'Unknown',
                        'function' => $frame['function'] ?? 'Unknown',
                        'class' => $frame['class'] ?? null
                    ];
                }
            }
        }

        return ['file' => 'Unknown', 'line' => 'Unknown', 'function' => 'Unknown'];
    }

    /**
     * Construye un stack trace enfocado en el código del usuario
     */
    private function buildUserStackTrace(array $trace): array
    {
        $userTrace = [];
        $skipPaths = ['/vendor/', '/twig/', '/cache/', '/compiled/'];

        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $shouldSkip = false;
                foreach ($skipPaths as $skipPath) {
                    if (strpos($frame['file'], $skipPath) !== false) {
                        $shouldSkip = true;
                        break;
                    }
                }

                if (!$shouldSkip) {
                    $userTrace[] = [
                        'file' => str_replace(getcwd(), '', $frame['file']),
                        'line' => $frame['line'] ?? 'Unknown',
                        'function' => $frame['function'] ?? 'Unknown',
                        'class' => $frame['class'] ?? null
                    ];
                }
            }
        }

        return $userTrace;
    }
}
