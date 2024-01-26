<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class VersaModuleManager
{
    private static string $template = 'app/templates/dashboard/';
    private static string $assets = 'src/dashboard/js/';

    public static function createModule(string $moduleName): void
    {
        $assets = self::$assets . ucfirst($moduleName);
        echo "Creando modulo $moduleName...\n";

        self::createTemplate($moduleName);
        self::createAssets($moduleName);
    }

    public static function createTemplate(string $moduleName): bool
    {
        $path = self::$template . strtolower($moduleName);
        $templateTWIG = $path . '/dash' . ucfirst($moduleName) . '.twig';
        $moduleNameUC = ucfirst($moduleName);


        echo "Creando template $moduleName...\n";

        if (!file_exists($path)) {
            mkdir($path, 2777, true);
        }

        if (file_exists($templateTWIG)) {
            echo "El template {$templateTWIG} ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";

                return false;
            }
            unlink($templateTWIG);
        }

        $template = <<<'EOT'
                        {% extends "dashboard/dashboard" %} {% block appDashboard %}
                        {{ csrf_field() | raw }}
                        <$moduleNameapp></$moduleNameapp>
                        {% endblock %} {% block appScript %}
                        <script type="module" src="{{ getAssets('dashboard', 'js', '/$moduleName/dash$moduleNameUC.js') }}?{{ date()|date('U') }}"></script>
                        {% endblock %}
                        EOT;

        $template = str_replace('$moduleNameUC', $moduleNameUC, $template);
        $template = str_replace('$moduleName', $moduleName, $template);
        file_put_contents($templateTWIG, $template);
        echo "Template Creado {$templateTWIG} creada.\n";

        return true;
    }

    public static function createAssets(string $moduleName): void
    {
        $path = self::$assets . strtolower($moduleName);
        $templateJS = $path . '/dash' . ucfirst($moduleName) . '.js';
        $moduleNameUC = ucfirst($moduleName);

        echo "Creando assets $moduleName...\n";

        if (!file_exists($path)) {
            mkdir($path, 2777, true);
        }

        if (file_exists($templateJS)) {
            echo "El assets {$templateJS} ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";

                return;
            }
            unlink($templateJS);
        }

        $template = <<<'EOT'
                    'use strict';
                    // @ts-ignore
                    const { ref, computed, reactive, watch } = Vue;
                    import { versaAlert, versaFetch } from '../functions.js';
                    import { app } from '../vue-instancia.js';
                    app.component('$moduleNameapp', {
                        setup() {
                            return {};
                        },
                        methods: {
                            accion(accion) {
                                const actions = {};

                                const selectedAction = actions[accion.accion] || actions['default'];
                                if (typeof selectedAction === 'function') {
                                    selectedAction();
                                }
                            },
                        },
                        template: /*html*/ `
                            <div class="mx-4 my-4 lg:flex lg:justify-between max-sm:flex-col max-sm:flex-wrap">
                                <div class="flex gap-2">
                                    <svg
                                        class="w-6 h-6 text-gray-500 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm14-7.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm-5-4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm-5-4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1Zm0 4a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5v-1ZM20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4Z"
                                        />
                                    </svg>
                                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">$moduleNameUC</h1>
                                </div>

                                <nav class="flex" aria-label="Breadcrumb">
                                    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
                                        <li class="inline-flex items-center">
                                            <a
                                                href="/admin/dashboard"
                                                class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white"
                                            >
                                                <svg
                                                    class="w-3 h-3 me-2.5"
                                                    aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path
                                                        d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"
                                                    />
                                                </svg>
                                                Home
                                            </a>
                                        </li>
                                        <li>
                                            <div class="flex items-center">
                                                <svg
                                                    class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1"
                                                    aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 6 10"
                                                >
                                                    <path
                                                        stroke="currentColor"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="m1 9 4-4-4-4"
                                                    />
                                                </svg>
                                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">$moduleNameUC</span>
                                            </div>
                                        </li>
                                        <li aria-current="page">
                                            <div class="flex items-center">
                                                <svg
                                                    class="rtl:rotate-180 w-3 h-3 text-gray-400 mx-1"
                                                    aria-hidden="true"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    fill="none"
                                                    viewBox="0 0 6 10"
                                                >
                                                    <path
                                                        stroke="currentColor"
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="m1 9 4-4-4-4"
                                                    />
                                                </svg>
                                                <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                                                    <a @click="accion({accion:'openModal'})" class="text-white bg-blue-500 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 cursor-pointer">
                                                        <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 8v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0Zm12 7h-1v1a1 1 0 0 1-2 0v-1H8a1 1 0 0 1 0-2h1v-1a1 1 0 1 1 2 0v1h1a1 1 0 0 1 0 2Z"/>
                                                        </svg>
                                                        <span class="max-lg:hidden ms-2">Nuevo $moduleNameUC</span>
                                                    </a>
                                                </span>
                                            </div>
                                        </li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="relative overflow-x-auto shadow-md sm:rounded-lg mx-4">
                                <hr class="h-px mt-8 mb-4 bg-gray-200 border-0 dark:bg-gray-700">

                            </div>
                            `,
                    });

                    app.mount('.content-wrapper');
                    EOT;

        $template = str_replace('$moduleNameUC', $moduleNameUC, $template);
        $template = str_replace('$moduleName', $moduleName, $template);
        file_put_contents($templateJS, $template);
        echo "Assets Creado {$templateJS} creada.\n";

        return;
    }
}
