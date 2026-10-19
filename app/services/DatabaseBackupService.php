<?php

declare(strict_types=1);

namespace app\services;

use SensitiveParameter;
use versaWYS\kernel\cli\ConfigManager;
use versaWYS\kernel\File;

/**
 * Genera y restaura respaldos completos de la base de datos (PostgreSQL) usando
 * las herramientas nativas del motor (pg_dump / psql), para clonar datos de un
 * entorno a otro durante el desarrollo.
 */
class DatabaseBackupService
{
    private const string BACKUP_DIR = __DIR__ . '/../../versaWYS/cache/db_backups';

    private const array ALLOWED_IMPORT_EXTENSIONS = ['sql'];

    private const int MAX_IMPORT_SIZE_BYTES = 500 * 1024 * 1024;

    private const string TOKEN_PATTERN = '/^[a-f0-9]{32}$/';

    private const string UNSUPPORTED = 'Los respaldos desde el panel funcionan con MySQL/MariaDB y PostgreSQL.';

    public function exportToFile(): array
    {
        if (!$this->isSupported()) {
            return ['success' => false, 'message' => self::UNSUPPORTED];
        }

        $this->ensureBackupDir();

        $token = bin2hex(random_bytes(16));
        $filename = sprintf('db_%s_%s.sql', date('Ymd_His'), $token);
        $absolutePath = self::BACKUP_DIR . DIRECTORY_SEPARATOR . $filename;

        $result = $this->isMysql()
            ? $this->runProcess([
                'mysqldump',
                ...$this->connectionArgs(),
                '--single-transaction',
                '--routines',
                '--result-file=' . $absolutePath,
                $this->db()['database'] ?? '',
            ])
            : $this->runProcess([
                'pg_dump',
                ...$this->connectionArgs(),
                '--no-owner',
                '--no-privileges',
                '--clean',
                '--if-exists',
                '--format=plain',
                '--file=' . $absolutePath,
            ]);

        if ($result['exit_code'] !== 0 || !is_file($absolutePath)) {
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }

            return [
                'success' => false,
                'message' => 'Error al generar el respaldo de la base de datos.',
                'error' => $result['stderr'],
            ];
        }

        return [
            'success' => true,
            'token' => $token,
            'filename' => $filename,
            'size_bytes' => filesize($absolutePath),
        ];
    }

    public function getDumpPath(#[SensitiveParameter] string $token): ?string
    {
        if (preg_match(self::TOKEN_PATTERN, $token) !== 1) {
            return null;
        }

        $matches = glob(self::BACKUP_DIR . DIRECTORY_SEPARATOR . 'db_*_' . $token . '.sql');

        return $matches !== false && $matches !== [] ? $matches[0] : null;
    }

    public function deleteDump(#[SensitiveParameter] string $token): void
    {
        $path = $this->getDumpPath($token);
        if ($path !== null && is_file($path)) {
            unlink($path);
        }
    }

    public function importFromFile(File $uploadedFile): array
    {
        $extension = strtolower($uploadedFile->getExtension());
        if (!in_array($extension, self::ALLOWED_IMPORT_EXTENSIONS, true)) {
            return ['success' => false, 'message' => 'Solo se aceptan archivos .sql'];
        }

        if ($uploadedFile->getSize() > self::MAX_IMPORT_SIZE_BYTES) {
            return ['success' => false, 'message' => 'El archivo supera el tamaño máximo permitido (500 MB).'];
        }

        if (!$this->isSupported()) {
            return ['success' => false, 'message' => self::UNSUPPORTED];
        }

        $result = $this->isMysql()
            // El cliente mysql lee el dump por stdin
            ? $this->runProcess(
                ['mysql', ...$this->connectionArgs(), $this->db()['database'] ?? ''],
                $uploadedFile->getTmpName(),
            )
            : $this->runProcess([
                'psql',
                ...$this->connectionArgs(),
                '--set=ON_ERROR_STOP=1',
                '--single-transaction',
                '--quiet',
                '--file=' . $uploadedFile->getTmpName(),
            ]);

        if ($result['exit_code'] !== 0) {
            return [
                'success' => false,
                'message' => 'Error al restaurar la base de datos.',
                'error' => $result['stderr'],
            ];
        }

        return ['success' => true, 'message' => 'Base de datos restaurada correctamente.'];
    }

    private function db(): array
    {
        return ConfigManager::getConfig()['database'] ?? [];
    }

    /** Respaldos con mysqldump/mysql o pg_dump/psql: otro motor (SQLite) no tiene cliente que llamar */
    private function isSupported(): bool
    {
        $driver = strtolower((string) ($this->db()['driver'] ?? ''));

        return str_contains($driver, 'mysql') || str_contains($driver, 'pgsql') || str_contains($driver, 'postgres');
    }

    private function isMysql(): bool
    {
        return str_contains(strtolower((string) ($this->db()['driver'] ?? '')), 'mysql');
    }

    private function connectionArgs(): array
    {
        $db = $this->db();
        $args = [
            '--host=' . (string) ($db['host'] ?? 'localhost'),
            '--port=' . (string) ($db['port'] ?? ''),
        ];

        return (
            $this->isMysql()
                ? [...$args, '--protocol=TCP', '--user=' . (string) ($db['username'] ?? '')]
                : [
                    ...$args,
                    '--username=' . (string) ($db['username'] ?? ''),
                    '--dbname=' . (string) ($db['database'] ?? ''),
                ]
        );
    }

    private function ensureBackupDir(): void
    {
        if (!is_dir(self::BACKUP_DIR)) {
            mkdir(self::BACKUP_DIR, 0o770, true);
        }
    }

    /**
     * Ejecuta el cliente de BD sin exponer la contraseña en la línea de comandos: viaja en
     * variables de entorno del proceso hijo (PGPASSWORD / MYSQL_PWD).
     *
     * @param string|null $stdinFile Archivo que se entrega al proceso por stdin
     */
    private function runProcess(array $command, ?string $stdinFile = null): array
    {
        $descriptors = [
            0 => $stdinFile === null ? ['pipe', 'r'] : ['file', $stdinFile, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $password = (string) ($this->db()['password'] ?? '');
        $env = getenv();
        $env['PGPASSWORD'] = $password;
        $env['MYSQL_PWD'] = $password;

        $cmdLine = implode(' ', array_map('escapeshellarg', $command));

        $process = proc_open($cmdLine, $descriptors, $pipes, null, $env);
        if (!is_resource($process)) {
            return ['exit_code' => -1, 'stdout' => '', 'stderr' => 'No se pudo iniciar el proceso.'];
        }

        if (($pipes[0] ?? null) !== null) {
            fclose($pipes[0]);
        }
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return ['exit_code' => $exitCode, 'stdout' => (string) $stdout, 'stderr' => (string) $stderr];
    }
}
