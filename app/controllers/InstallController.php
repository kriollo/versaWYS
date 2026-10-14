<?php

declare(strict_types=1);

namespace app\controllers;

use app\middleware\InstallMiddleware;
use app\models\Users;
use RuntimeException;
use Throwable;
use VersaORM\VersaORM;
use versaWYS\kernel\cli\ConfigManager;
use versaWYS\kernel\helpers\Functions;
use versaWYS\kernel\Loggin;
use versaWYS\kernel\model\OrmFactory;
use versaWYS\kernel\model\VersaORMModel;

/**
 * Instalador web de primera ejecución: configura la base de datos, ejecuta migraciones y
 * seeders, y crea la empresa y el superadministrador desde el formulario, sin acceso SSH.
 * Los parámetros llegan validados por InstallMiddleware.
 */
class InstallController
{
    public function index(): string
    {
        global $twig;

        return $twig->render('install/install');
    }

    public function run(): array
    {
        global $request;

        // La contraseña de la BD llega sin sanear: debe ser exacta (la del admin sí pasa por
        // getAllParams, igual que en el login, para que ambos hashes coincidan)
        return $this->install(['db_pass' => (string) ($request->getRawParams()['db_pass'] ?? '')] + $request->getAllParams());
    }

    /**
     * Configura la base de datos, migra, siembra y crea la empresa y el superadministrador. Los
     * datos ya vienen validados (InstallMiddleware::errors). La usan el asistente web y
     * `versaCLI app:install`.
     *
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    public function install(array $params): array
    {
        global $config;

        $newConfig = ConfigManager::getConfig();
        $newConfig['database'] = [
            'driver' => $params['db_driver'],
            'host' => $params['db_host'],
            'port' => (int) $params['db_port'],
            'database' => $params['db_name'],
            'username' => $params['db_user'],
            'password' => (string) ($params['db_pass'] ?? ''),
        ] + ($newConfig['database'] ?? []);

        try {
            $orm = OrmFactory::make(array_replace_recursive($config, $newConfig));
        } catch (Throwable $throwable) {
            // El detalle (host, usuario, error del driver) queda en el log, no en la respuesta
            Loggin::error('Instalador: conexión rechazada: ' . $throwable->getMessage(), 'install');

            return [
                'success' => 0,
                'message' => 'No fue posible conectar a la base de datos con los datos entregados.',
                'code' => 422,
            ];
        }

        ConfigManager::saveConfig($newConfig);
        $config = array_replace_recursive($config, $newConfig);
        VersaORMModel::replaceOrm($orm);

        $log = [];

        try {
            $log[] = $this->runMigrations($orm);

            if ($orm->table('versa_users')->count() > 0) {
                throw new RuntimeException('La base de datos ya contiene usuarios: la instalación no se ejecuta sobre un sitio en uso.');
            }

            // Si un intento anterior falló a medias los datos base ya existen: no duplicarlos
            $log[] = $orm->table('empresas')->count() === 0 ? $this->runSeeders() : 'Datos base ya presentes.';
            $this->createEmpresaAndAdmin($orm, $params);
            $log[] = 'Empresa y superadministrador creados.';
        } catch (Throwable $throwable) {
            Loggin::error('Instalador: ' . $throwable->getMessage(), 'install');

            return [
                'success' => 0,
                'message' => 'Ocurrió un error durante la instalación: ' . $throwable->getMessage(),
                'log' => $log,
                'code' => 500,
            ];
        }

        file_put_contents(InstallMiddleware::lockFile(), json_encode(['installed_at' => date('Y-m-d H:i:s')]));
        if (file_exists(InstallMiddleware::tokenFile())) {
            unlink(InstallMiddleware::tokenFile());
        }

        return [
            'success' => 1,
            'message' => 'Instalación completada con éxito.',
            'log' => $log,
            'redirect' => '/' . $config['admin']['custom_url'] . '/login',
        ];
    }

    /**
     * Ejecuta las migraciones pendientes. Equivale a `versaCLI migrate:up`, pero sin la
     * salida de consola ni los exit() de MigrationManager.
     */
    private function runMigrations(VersaORM $orm): string
    {
        $executed = in_array('versa_migrations', $orm->schema('tables'), strict: true)
            ? array_column($orm->exec('SELECT name FROM versa_migrations'), 'name')
            : [];

        $files = glob('app/migrations/*.php') ?: [];
        sort($files);
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (in_array($name, $executed, strict: true)) {
                continue;
            }

            $result = (new ('\\app\\migrations\\' . $name)())->up();
            if (!$result['success']) {
                throw new RuntimeException("Migración {$name}: {$result['message']}");
            }

            $orm->table('versa_migrations')->insert(['name' => $name, 'created_at' => date('Y-m-d H:i:s')]);
            $count++;
        }

        return "{$count} migraciones ejecutadas.";
    }

    private function runSeeders(): string
    {
        $files = glob('app/seeders/*.php') ?: [];
        sort($files, SORT_NATURAL);

        foreach ($files as $file) {
            $name = basename($file, '.php');
            // Vía instancia: hay seeders con run() estático y otros de instancia
            $result = (new ('\\app\\seeders\\' . $name)())->run();
            if (!$result['success']) {
                throw new RuntimeException("Seeder {$name}: {$result['message']}");
            }
        }

        return count($files) . ' seeders ejecutados.';
    }

    /**
     * El seeder de empresas deja la empresa 1 con datos de ejemplo: aquí toma los del formulario.
     */
    private function createEmpresaAndAdmin(VersaORM $orm, array $params): void
    {
        global $config;

        $orm->table('empresas')->where('id', '=', 1)->update([
            'nombre' => $params['empresa_nombre'],
            'rut' => $params['empresa_rut'],
            'email' => $params['admin_email'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $days = $config['auth']['expiration_days_password'] ?? 90;
        $id = (new Users())->createUser([
            'name' => $params['admin_name'],
            'email' => $params['admin_email'],
            'password' => Functions::hash((string) $params['admin_password']),
            'expiration_pass' => date('Y-m-d', strtotime("+{$days} days")),
            'role' => 'superadmin',
            'status' => 1,
            'id_perfil' => 1,
            'empresa' => 1,
        ]);

        if (!$id) {
            throw new RuntimeException('No se pudo crear el usuario superadministrador.');
        }
    }
}
