<?php

declare(strict_types=1);

namespace versaWYS\kernel\cli;

class FrontManager
{
    private static string $templateTwig = 'app/templates/dashboard/';

    private static string $componentVue = 'src/dashboard/js/';

    public static function createTwig(string $moduleName, string $prefix = ''): bool
    {
        $path = self::$templateTwig . strtolower($moduleName);
        $templateTWIG = $path . '/' . $prefix . ucfirst($moduleName) . '.twig';

        CLIErrorHandler::info("Creando template {$moduleName}...");

        try {
            CLIHelper::ensureDirectory($path, 0o775);
        } catch (\Exception $e) {
            CLIErrorHandler::warning($e->getMessage());
            return false;
        }

        if (file_exists($templateTWIG)) {
            if (!CLIHelper::confirm("El template {$templateTWIG} ya existe. Desea sobreescribirlo?")) {
                CLIErrorHandler::info('Operación cancelada.');
                return false;
            }
            unlink($templateTWIG);
        }

        $template = <<<'EOT'
            {% extends "dashboard/dashboard.twig" %}
            {% block appHeader %}{% endblock %}
            {% block appDashboard %}{% endblock %}
            {% block appScript %}{% endblock %}
            EOT;

        if (file_put_contents($templateTWIG, $template)) {
            chmod($templateTWIG, 0o775);
            CLIErrorHandler::success("Template {$templateTWIG} creado.");
            return true;
        }

        CLIErrorHandler::warning("No se pudo crear el archivo: {$templateTWIG}");
        return false;
    }

    public static function createVue(string $moduleName, string $prefix = ''): bool
    {
        $path = self::$componentVue . strtolower($moduleName);
        $componentVue = $path . '/' . $prefix . ucfirst($moduleName) . '.vue';

        CLIErrorHandler::info("Creando componente Vue {$moduleName}...");

        try {
            CLIHelper::ensureDirectory($path, 0o775);
        } catch (\Exception $e) {
            CLIErrorHandler::warning($e->getMessage());
            return false;
        }

        if (file_exists($componentVue)) {
            if (!CLIHelper::confirm("El componente Vue {$componentVue} ya existe. Desea sobreescribirlo?")) {
                CLIErrorHandler::info('Operación cancelada.');
                return false;
            }
            unlink($componentVue);
        }

        $component = <<<'EOT'
            <script setup>
                import { ref } from 'vue';
                const title = ref('Hello World');
            </script>
            <template>
                <div class="grid justify-center items-center content-center">
                    <h1 class="text-2xl font-bold clasePersonalizada">$moduleName</h1>
                    <p class="clasePersonalizada">{{ title }}</p>
                </div>
            </template>
            <style scoped>
                /* Agrega tu estilo aquí */
                .clasePersonalizada {
                    color: red;
                }
            </style>
            EOT;

        $component = str_replace('$moduleName', $moduleName, $component);

        if (file_put_contents($componentVue, $component)) {
            chmod($componentVue, 0o775);
            CLIErrorHandler::success("Componente Vue {$componentVue} creado.");
            return true;
        }

        CLIErrorHandler::warning("No se pudo crear el archivo: {$componentVue}");
        return false;
    }
}
