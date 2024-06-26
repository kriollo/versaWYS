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
                mkdir($__TWIG_CACHE_PATH, 0644, true);
            } else {
                chmod($__TWIG_CACHE_PATH, 0644);
            }

            # Revisar la lecutra para twig
            $__TWIG_READABLE_AND_WRITABLE = !is_readable($__TWIG_CACHE_PATH) || !is_writable($__TWIG_CACHE_PATH);
            if ($__TWIG_READABLE_AND_WRITABLE) {
                throw new RuntimeException('Debe conceder permisos de escritura y lectura a la ruta ' . $__TWIG_CACHE_PATH . ' ó crearla si no existe.');
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
            'strict_variables' => !$config['build']['debug'] ? $config['build']['debug'] : $config['twig']['strict_variables'],
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



        return "
            <style>
                .flex {
                    display: flex;
                }
                .justify-between {
                    justify-content: space-between;
                }
                .w-full {
                    width: 100%;
                }
                .bottom-0 {
                    bottom: 0px;
                }
                .left-0 {
                    left: 0px;
                }
                .bg-red-900 {
                    --tw-bg-opacity: 1;
                    background-color: rgb(127 29 29 / var(--tw-bg-opacity));
                }
                .text-white {
                    --tw-text-opacity: 1;
                    color: rgb(255 255 255 / var(--tw-text-opacity));
                }
                .absolute {
                    position: absolute;
                }
                .z-50 {
                    z-index: 50;
                }
                .p-2 {
                    padding: 0.5rem;
                }
                .gap-1 {
                    gap: 0.25rem;
                }
                .w-6 {
                    width: 1.5rem;
                }
                .h-6 {
                    height: 1.5rem;
                }
                .fixed {
                    position: fixed;
                }
                .animate-Debugfade {
                    animation: fadeIn 1.5s ease-in-out;
                }
                @keyframes fadeIn {
                    0% {
                        opacity: 0;
                    }
                    100% {
                        opacity: 1;
                    }
                }
            </style>
            <div class='flex justify-between w-full bottom-0 left-0 bg-red-900 text-white fixed z-50 p-2 animate-Debugfade' id='debug'><div>Modo Debug: Activado</div>
                <div class='flex gap-1'>
                    <svg class='w-6 h-6' xmlns='http://www.w3.org/2000/svg' xml:space='preserve' viewBox='0 0 164 160'>
                        <image width='164' height='160' href='data:image/png;base64,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 SUVORK5CYII='/>
                    </svg>
                    VersaWYS Framework
                </div>
                <div id='tiempoCarga'></div>
            </div>
            <script>
                const antes = new Date().getTime();
                function tdc() {
                    return (new Date().getTime() - antes) / 1e3 + 's';
                }
                window.onload = function () {
                    document.getElementById('tiempoCarga').innerHTML = 'CARGA: ' + tdc();
                };
            </script>";
    }



    /**
     * Sets the Twig filters for the given Twig environment.
     *
     * @param Environment $twig The Twig environment to set the filters for.
     * @return void
     */
    private function setFilterTwig(Environment $twig): void
    {
        $rutaCarpeta = __DIR__ . '/Helpers';

        if ($handle = opendir($rutaCarpeta)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
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
        $rutaCarpeta = __DIR__ . '/Helpers';

        if ($handle = opendir($rutaCarpeta)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
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
        global $config;
        if ($config['build']['debug']) {
            echo Response::jsonError([
                'success' => 0,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        } else {
            echo Response::jsonError([
                'success' => 0,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
