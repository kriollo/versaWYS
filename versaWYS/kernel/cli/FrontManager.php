<?php

namespace versaWYS\kernel\cli;

class FrontManager
{
    private static string $templateTwig = 'app/templates/dashboard/';
    private static string $componentVue = 'src/dashboard/js/';

    public static function createTwig(string $moduleName, $prefix = ''): bool
    {
        $path = self::$templateTwig . strtolower($moduleName);
        $templateTWIG = $path . '/' . $prefix . ucfirst($moduleName) . '.twig';

        echo "Creando template $moduleName...\n";

        if (!file_exists($path)) {
            mkdir($path, 2777, true);
        }

        if (file_exists($templateTWIG)) {
            echo "El template $templateTWIG ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen('php://stdin', 'r');
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";

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

        file_put_contents($templateTWIG, $template);
        echo "Template Creado $templateTWIG creada.\n";

        return true;
    }

    public static function createVue(string $moduleName, $prefix = ''): bool
    {
        $path = self::$componentVue . strtolower($moduleName);
        $componentVue = $path . '/' . $prefix . ucfirst($moduleName) . '.vue';

        echo "Creando componente Vue $moduleName...\n";

        if (!file_exists($path)) {
            mkdir($path, 2777, true);
        }

        if (file_exists($componentVue)) {
            echo "El componente Vue $componentVue ya existe.\nDesea sobreescribirlo? (y/n): ";
            $handle = fopen('php://stdin', 'r');
            $line = fgets($handle);
            if (trim($line) != 'y' && trim($line) != 'Y') {
                echo "Saliendo...\n";

                return false;
            }
            unlink($componentVue);
        }

        $component = <<<'EOT'
<script setup>
    import { ref } from 'vue';
    const title = ref('Hello World');
    const count = ref(0);
</script>
<template>
    <div class="grid justify-center items-center content-center p-6">
        <h1 class="text-2xl font-bold clasePersonalizada">$moduleName</h1>
        <p class="clasePersonalizada">{{ title }}</p>
    </div>
    <div class="grid justify-center items-center content-center p-6">
        <button
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            @click="title = 'Hello VersaWYS'">
            Change Title
        </button>
        <div class="pt-6">
            <button
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                @click="count++">
                +
            </button>
            <span
                class="text-white font-bold py-2 px-4 rounded">
                {{ count }}
            </span>
            <button
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                @click="count--">
                -
            </button>
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

        file_put_contents($componentVue, $component);
        echo "Componente Vue Creado $componentVue creada.\n";

        return true;
    }
}
