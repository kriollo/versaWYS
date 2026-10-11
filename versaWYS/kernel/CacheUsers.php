<?php

declare(strict_types=1);

namespace versaWYS\kernel;

use app\models\Dashboard;
use app\models\Empresas;
use app\models\Users;
use Exception;

class CacheUsers
{
    private const MENU_VERSION_KEY = 'menu_version_timestamp';

    /** @var array<string, mixed> */
    protected array $cacheData = [];

    public function __construct()
    {
        global $session, $config;

        // Cargar datos del usuario con caché inteligente
        $this->loadUserDataWithCache($session, $config);
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserData(int $userId, int $empresaId): array
    {
        global $session, $config;

        // Generar clave de caché única
        $cacheKey = sprintf('user_data_%d_%d', $userId, $empresaId);
        $cacheData = $session->get($cacheKey);
        if (!is_array($cacheData)) {
            return $this->loadFreshUserData($userId, $empresaId, $config);
        }

        return $cacheData;
    }

    /**
     * Actualiza la versión del menú. Debe llamarse cuando se editan perfiles o módulos.
     */
    public function updateMenuVersion(): void
    {
        global $session;

        try {
            // Actualizamos el timestamp en la sesión. Esto invalidará el caché
            // para todos los usuarios en su próxima petición.
            $session->set(self::MENU_VERSION_KEY, (string) time());
        } catch (Exception) {
            // En caso de error, no hacer nada para no romper la aplicación.
        }
    }

    /**
     * Invalida manualmente el caché de un usuario específico
     * Útil cuando sabemos que los datos han cambiado.
     */
    public static function invalidateUserCache(int $userId, ?Session $sessionInstance = null): void
    {
        global $session;
        $sessionToUse = $sessionInstance ?? $session;

        if ($sessionToUse && $userId > 0) {
            // OPTIMIZACIÓN: Buscar y eliminar todas las claves de caché para un usuario específico,
            // sin importar el ID de la empresa. Esto asegura que todos los cachés se invaliden
            // de forma correcta y escalable, sin depender de bucles con límites fijos.
            $prefix = sprintf('user_data_%d_', $userId);
            $allSessionKeys = array_keys($sessionToUse->getAll());
            foreach ($allSessionKeys as $key) {
                if (!(is_string($key) && str_starts_with($key, $prefix))) {
                    continue;
                }

                $sessionToUse->delete($key);
            }
        }
    }

    /**
     * Invalida el caché de todos los usuarios
     * Útil cuando hay cambios globales (empresas, menús globales, etc.).
     */
    public static function invalidateAllUserCache(?Session $sessionInstance = null): void
    {
        global $session;
        $sessionToUse = $sessionInstance ?? $session;

        if ($sessionToUse) {
            // OPTIMIZACIÓN: Iterar sobre las claves de sesión existentes en lugar de bucles fijos.
            // Esto es mucho más eficiente, escalable y no depende de IDs hardcodeados.
            $allSessionKeys = array_keys($sessionToUse->getAll());
            foreach ($allSessionKeys as $key) {
                if (!(is_string($key) && str_starts_with($key, 'user_data_'))) {
                    continue;
                }

                $sessionToUse->delete($key);
            }
        }
    }

    /**
     * Carga los datos del usuario con sistema de caché inteligente
     * Incluye invalidación automática basada en timestamps y cambios de datos.
     *
     * @param array<string, mixed> $config
     */
    /**
     * @param array<string, mixed> $config
     */
    private function loadUserDataWithCache(Session $session, array $config): void
    {
        $userId = (int) $session->get('id_user', 0);
        $empresaId = (int) $session->get('empresa_id', 0);

        // Generar clave de caché única
        $cacheKey = sprintf('user_data_%d_%d', $userId, $empresaId);
        /** @var array<string, mixed>|null $cacheData */
        $cacheData = $session->get($cacheKey);

        // Verificar si el caché es válido
        if (!$this->isUserCacheValid($cacheData, $userId, $empresaId)) {
            // Cargar datos frescos de la base de datos
            $cacheLocal = $this->loadFreshUserData($userId, $empresaId, $config);

            // Guardar en caché con metadata de invalidación
            if (isset($cacheLocal['user']) && is_array($cacheLocal['user'])) {
                $this->cacheData = [
                    'user' => $cacheLocal['user'],
                    'empresaSelected' => $cacheLocal['empresaSelected'],
                    'empresas' => $cacheLocal['empresas'],
                    'menu_user' => $cacheLocal['menu_user'],
                    'id_user' => $cacheLocal['id_user'],
                    'cached_at' => time(),
                    'user_updated_at' => strtotime((string) ($cacheLocal['user']['updated_at'] ?? '')) ?: 0,
                    'empresa_version' => $this->getEmpresasVersion(),
                    'menu_version' => $this->getMenuVersion(),
                    'ttl' => time() + 300, // 5 minutos TTL
                ];

                $session->set($cacheKey, $this->cacheData);
            }
        }
    }

    /**
     * Carga datos frescos de la base de datos (sin caché).
     *
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function loadFreshUserData(int $userId, int $empresaId, array $config): array
    {
        $user = (new Users())->findWithCompany($userId);
        $empresas = (new Empresas())->all('1');
        $empresaSelected = (new Empresas())->getEmpresaById($empresaId);

        $id_user = $userId;

        // Cargar menú según rol
        $menu_user = [];
        if ($user) {
            $menu_user = ($user['role'] ?? null) === 'superadmin'
                ? (new Dashboard())->getMenuAdmin()
                : (new Dashboard())->getMenuUser($userId, (int) ($user['id_perfil'] ?? 0));
        }

        return [
            'user' => $user,
            'empresaSelected' => $empresaSelected,
            'empresas' => $empresas,
            'menu_user' => $menu_user,
            'id_user' => $id_user,
        ];
    }

    /**
     * Verifica si el caché del usuario es válido
     * Usando múltiples estrategias de invalidación.
     *
     * @param array<string, mixed>|null $cacheData
     */
    /**
     * @param array<string, mixed>|null $cacheData
     */
    private function isUserCacheValid(?array $cacheData, int $userId, int $empresaId): bool
    {
        if ($cacheData === null) {
            return false;
        }

        // Verificar TTL (Time To Live)
        if (time() > ($cacheData['ttl'] ?? 0)) {
            return false;
        }

        // Verificar si el usuario ha sido modificado
        if ($this->hasUserBeenUpdated($userId, (int) ($cacheData['user_updated_at'] ?? 0))) {
            return false;
        }

        // Verificar si las empresas han cambiado
        if ($this->getEmpresasVersion() !== ($cacheData['empresa_version'] ?? '')) {
            return false;
        }

        // Verificar si el menú ha cambiado
        $currentMenuVersion = $this->getMenuVersion();

        return $currentMenuVersion === ($cacheData['menu_version'] ?? '');
    }

    /**
     * Verifica si el usuario ha sido actualizado desde el último caché.
     */
    private function hasUserBeenUpdated(int $userId, int $cachedTimestamp): bool
    {
        try {
            // Query ligera para obtener solo el timestamp de actualización del usuario
            $userModel = new Users();
            $user = $userModel->findWithCompany($userId);

            // Manejar tanto arrays como objetos
            $updatedAt = null;
            if ($user !== []) {
                if (is_array($user) && isset($user['updated_at'])) {
                    $updatedAt = $user['updated_at'];
                } elseif (is_object($user) && isset($user->updated_at)) {
                    $updatedAt = $user->updated_at;
                }
            }

            $lastUpdate = $updatedAt ? (int) strtotime((string) $updatedAt) : 0;

            return $lastUpdate > $cachedTimestamp;
        } catch (Exception) {
            // En caso de error, invalidar caché por seguridad
            return true;
        }
    }

    /**
     * Obtiene una "versión" de las empresas basada en última modificación.
     */
    private function getEmpresasVersion(): string
    {
        try {
            // Usar count de empresas y timestamp actual como versión simple
            $empresasModel = new Empresas();
            $empresas = $empresasModel->all('1');

            return md5(serialize($empresas)); // Hash de los datos
        } catch (Exception) {
            return (string) time(); // Fallback que siempre invalida
        }
    }

    /**
     * Obtiene una "versión" del menú basada en última modificación.
     */
    private function getMenuVersion(): string
    {
        global $session;

        // Obtenemos el timestamp de la versión del menú desde la sesión.
        // Esto es súper rápido y no requiere I/O de disco en cada petición.
        return (string) $session->get(self::MENU_VERSION_KEY, 'initial');
    }
}
