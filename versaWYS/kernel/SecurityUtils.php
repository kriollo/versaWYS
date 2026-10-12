<?php

declare(strict_types=1);

namespace versaWYS\kernel;

/**
 * Clase de utilidades de seguridad para sanitización y validación de datos.
 */
class SecurityUtils
{
    /**
     * Sanitiza una URL removiendo caracteres peligrosos para SQL injection
     * de manera inteligente, preservando rutas legítimas.
     *
     * @param string $url URL a sanitizar
     *
     * @return string URL sanitizada
     */
    public static function sanitizeUrl(string $url): string
    {
        // Solo remover patrones que claramente son intentos de SQL injection
        // NO remover palabras SQL que forman parte de nombres legítimos de rutas

        // Caracteres claramente maliciosos que siempre deben removerse
        $alwaysDangerous = ["'", '"', ';', '--', '/*', '*/'];

        // Remover caracteres claramente maliciosos
        foreach ($alwaysDangerous as $char) {
            $url = str_replace($char, '', $url);
        }

        // Patrones de SQL injection MUY específicos (solo ataques reales)
        $sqlInjectionPatterns = [
            // Procedimientos almacenados específicos
            '/\b(xp_cmdshell|sp_executesql|xp_regwrite)\b/i',
            // SQL injection con contexto completo muy específico
            '/\b(drop|create)\s+(table|database|schema)\s+\w+/i',
            '/\b(delete|insert|update)\s+(from|into|set)\s+\w+/i',
            '/\bunion\s+(all\s+)?select\s+/i',
            '/\bselect\s+[\w\*,\s]+\s+from\s+\w+/i',
            // Patrones después de punto y coma (inyección de comandos)
            '/;\s*(drop|delete|insert|update|select|create)\s+/i',
            // Comentarios SQL específicos con contexto
            '/\s+(or|and)\s+[\w\s]*--/i',
            '/\s+(or|and)\s+[\w\s]*\/\*/i',
        ];

        // Aplicar solo patrones muy específicos
        foreach ($sqlInjectionPatterns as $pattern) {
            $url = preg_replace($pattern, '', $url);
        }

        // Escapar caracteres especiales adicionales manteniendo funcionalidad
        return filter_var($url, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitiza datos para prevenir XSS en respuestas JSON.
     *
     * @param mixed $data Datos a sanitizar
     *
     * @return mixed Datos sanitizados
     */
    public static function sanitizeForJson($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeForJson'], $data);
        }

        if (is_string($data)) {
            // Escapar HTML/JS para prevenir XSS
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return $data;
    }

    /**
     * Valida y sanitiza parámetros de entrada.
     *
     * @param array $params Parámetros a validar
     *
     * @return array Parámetros sanitizados
     */
    public static function sanitizeInputParams(array $params): array
    {
        $sanitized = [];

        foreach ($params as $key => $value) {
            // Sanitizar la clave
            $key = filter_var($key, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeInputParams($value);
            } elseif (is_string($value)) {
                // Detectar si es JSON válido
                if (self::isValidJson($value)) {
                    // Para JSON válido, validar estructura pero no modificar
                    $sanitized[$key] = self::sanitizeJsonField($value);
                } else {
                    // Para texto normal, aplicar sanitización completa
                    $sanitized[$key] = self::sanitizeForJson(self::sanitizeSqlInput($value));
                }
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitiza entrada contra inyección SQL de forma contextual.
     *
     * @param string $input Entrada a sanitizar
     * @param string $context Contexto del campo (opcional, ayuda a determinar si es legítimo)
     *
     * @return string Entrada sanitizada
     */
    public static function sanitizeSqlInput(string $input, string $context = ''): string
    {
        // Si el input está vacío, retornarlo tal como está
        if (in_array(trim($input), ['', '0'], true)) {
            return $input;
        }

        // Primero, detectar si realmente parece una inyección SQL
        if (!self::containsSqlInjection($input)) {
            // Si no parece inyección SQL, solo escapar caracteres básicos
            return self::escapeBasicChars($input);
        }

        // Si parece inyección SQL, aplicar sanitización más agresiva
        return self::sanitizeAggressively($input);
    }

    /**
     * Detecta si un input contiene patrones de inyección SQL real.
     *
     * @param string $input Entrada a analizar
     *
     * @return bool True si contiene patrones de inyección SQL
     */
    public static function containsSqlInjection(string $input): bool
    {
        // Convertir a minúsculas para análisis
        $lowerInput = strtolower($input);

        // Patrones que indican inyección SQL real (no solo palabras sueltas)
        $injectionPatterns = [
            // Comentarios SQL
            '/--\s*\w+/',
            '/\/\*.*?\*\//',
            // Combinaciones típicas de inyección SQL
            '/\b(union|select|insert|update|delete|drop|create|alter)\s+(select|from|into|table|database|schema)\b/i',
            '/\b(or|and)\s+\d+\s*=\s*\d+/i', // OR 1=1, AND 1=1
            '/\b(or|and)\s+[\'"](\w+)[\'"]\s*=\s*[\'"]\1[\'"]/', // OR 'a'='a'
            // Terminadores de consulta con nuevas consultas
            '/;\s*(select|insert|update|delete|drop|create|alter)\b/i',
            // Funciones de sistema peligrosas
            '/\b(xp_|sp_|exec|execute)\s*\(/i',
            // Caracteres de escape seguidos de comandos
            '/[\'"]\s*;\s*(select|insert|update|delete|drop|create|alter)\b/i',
            // Patrones de concatenación maliciosa
            '/\+\s*(select|insert|update|delete|drop|create|alter)\b/i',
        ];

        foreach ($injectionPatterns as $pattern) {
            if (!preg_match($pattern, $lowerInput)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Genera un token CSRF.
     *
     * @return string Token CSRF
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;

        return $token;
    }

    /**
     * Valida un token CSRF.
     *
     * @param string|null $token Token a validar
     *
     * @return bool True si es válido, false en caso contrario
     */
    public static function validateCsrfToken(#[\SensitiveParameter] ?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$token || !isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Valida si una petición POST tiene token CSRF válido.
     *
     * @param array $params Parámetros de la petición
     *
     * @return bool True si es válido o no es POST, false si falta o es inválido
     */
    public static function validateCsrfForRequest(array $params): bool
    {
        // Solo validar CSRF para métodos que modifican datos
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], strict: true)) {
            return true; // No necesita CSRF para GET
        }

        $token = $params['csrf_token'] ?? null;

        return self::validateCsrfToken($token);
    }

    /**
     * Limpia y valida una URL manteniendo funcionalidad.
     *
     * @param string $url URL a validar
     *
     * @return string URL validada
     */
    public static function validateAndCleanUrl(string $url): string
    {
        // Primero aplicar sanitización básica
        $cleanUrl = self::sanitizeUrl($url);

        // Normalizar la URL (lógica original)
        $cleanUrl = trim($cleanUrl, '/');
        $cleanUrl = parse_url($cleanUrl, PHP_URL_PATH) ?: $cleanUrl;

        // Normalizar múltiples barras consecutivas a una sola barra
        $cleanUrl = preg_replace('#/+#', '/', $cleanUrl);

        // Eliminar barras al final si quedaron después de la normalización
        $cleanUrl = rtrim($cleanUrl, '/');

        return '/' . $cleanUrl;
    }

    /**
     * Validación avanzada de entrada para detectar patrones sospechosos.
     *
     * @param string $input Entrada a validar
     *
     * @return bool True si es segura, false si es sospechosa
     */
    public static function isInputSafe(string $input): bool
    {
        // Patrones sospechosos comunes
        $suspiciousPatterns = [
            '/\b(script|javascript|vbscript|onload|onerror|onclick)\b/i', // XSS
            '/\b(union|select|insert|update|delete|drop|create|alter)\b.*\b(from|into|table|database)\b/i', // SQL
            '/<\s*script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', // Script tags
            '/javascript\s*:/i', // JavaScript protocol
            '/data\s*:\s*text\/html/i', // Data URI XSS
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (!preg_match($pattern, $input)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * Prepara datos seguros para respuesta JSON.
     *
     * @param array $data Datos originales
     *
     * @return array Datos preparados para JSON
     */
    public static function prepareSecureJsonData(array $data): array
    {
        // Aplicar sanitización profunda
        $secureData = self::sanitizeForJson($data);

        // Agregar headers de seguridad si es necesario
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
        }

        return $secureData;
    }

    /**
     * Verifica si un identificador ha superado el límite de solicitudes permitido.
     *
     * @param string $identifier Identificador único para el IP + ruta
     * @param string $rule Nombre de la regla de límite a aplicar
     *
     * @return array|true Datos de error si el límite es excedido, true si está dentro del límite
     */
    public static function checkRateLimit(string $identifier, string $rule = 'global'): array|true
    {
        global $config;
        // Obtener configuración
        $settings = $config['rate_limiting'] ?? [];
        if (!($settings['enabled'] ?? false)) {
            return true; // Rate limiting no habilitado
        }

        $rules = $settings['rules'][$rule] ?? $settings['rules']['global'] ?? [];

        if (!($rules['enabled'] ?? false)) {
            return true; // Regla específica no habilitada
        }

        $rateData = self::incrementLocked(self::generateKey($identifier, $rule), (int) $rules['window']);

        // Limpieza ocasional de contadores vencidos (1 de cada `cleanup_probability` peticiones)
        if (random_int(1, max(1, (int) ($settings['cleanup_probability'] ?? 100))) === 1) {
            self::cleanExpiredLimits();
        }

        // Verificar si excede el límite
        if ($rateData['count'] > $rules['requests']) {
            return [
                'success' => 0,
                'message' => 'Rate limit exceeded',
                'errors' => [
                    'retry_after' => $rules['window'] - (time() - $rateData['window_start']),
                    'limit' => $rules['requests'],
                    'remaining' => 0,
                    'reset_time' => date('c', $rateData['window_start'] + $rules['window']),
                ],
                'code' => 429,
            ];
        }

        return true;
    }

    /**
     * Verificación de rate limiting global aplicada automáticamente por el Router.
     *
     * @param object $request Objeto Request con información de la petición
     *
     * @return array|true Datos de error si el límite es excedido, true si está dentro del límite
     */
    public static function checkGlobalRateLimit($request): array|true
    {
        $ip = $request->getIp();
        $identifier = $ip . ':global';

        return self::checkRateLimit($identifier, 'global');
    }

    /**
     * Limpia límites expirados.
     */
    public static function cleanExpiredLimits(): void
    {
        global $config;

        // Verificar que la configuración de rate limiting existe
        if (!isset($config['rate_limiting']) || !isset($config['rate_limiting']['cache_dir'])) {
            return;
        }

        $dir = $config['rate_limiting']['cache_dir'];
        $files = glob($dir . '*.json');

        if (!$files) {
            return;
        }

        // La ventana más larga de todas las reglas: un contador vigente para alguna regla no se borra
        $defaultWindow = max([3600, ...array_column($config['rate_limiting']['rules'] ?? [], 'window')]);

        foreach ($files as $file) {
            $data = json_decode((string) @file_get_contents($file), true);
            if (!isset($data['window_start']) || !self::isWithinWindow((int) $data['window_start'], $defaultWindow)) {
                @unlink($file); // Eliminar archivo si expiró o quedó corrupto
            }
        }
    }

    /**
     * Escapa solo caracteres básicos para inputs que no parecen inyecciones.
     *
     * @param string $input Entrada a escapar
     *
     * @return string Entrada con caracteres básicos escapados
     */
    private static function escapeBasicChars(string $input): string
    {
        // Solo escapar caracteres realmente peligrosos en contextos específicos
        $replacements = [
            "'" => '&#39;',
            '"' => '&quot;',
            '<' => '&lt;',
            '>' => '&gt;',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $input);
    }

    /**
     * Sanitiza agresivamente inputs que parecen inyecciones SQL.
     *
     * @param string $input Entrada a sanitizar agresivamente
     *
     * @return string Entrada sanitizada
     */
    private static function sanitizeAggressively(string $input): string
    {
        // Remover comentarios SQL
        $input = preg_replace('/--.*$/', '', $input);
        $input = preg_replace('/\/\*.*?\*\//', '', $input);

        // Escapar comillas y caracteres peligrosos
        $input = str_replace(["'", '"', ';'], ['&#39;', '&quot;', '&#59;'], $input);

        // Remover patrones de inyección SQL específicos
        $dangerousPatterns = [
            '/\b(union|select|insert|update|delete|drop|create|alter)\s+(select|from|into|table|database)\b/i',
            '/\b(or|and)\s+\d+\s*=\s*\d+/i',
            '/\b(exec|execute|xp_|sp_)\s*\(/i',
            '/;\s*(select|insert|update|delete|drop|create|alter)\b/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }

        return trim($input);
    }

    /**
     * Genera una clave única para almacenar los límites según IP y ruta.
     */
    private static function generateKey(string $identifier, string $rule): string
    {
        return hash('sha256', $identifier . '::' . $rule);
    }

    /**
     * Suma una petición al contador de la clave y devuelve su estado. Lectura y escritura ocurren
     * bajo un bloqueo exclusivo, para que peticiones simultáneas no se pisen ni se pierdan.
     *
     * @return array{count: int, window_start: int}
     */
    private static function incrementLocked(string $key, int $window): array
    {
        global $config;

        $cacheDir = $config['rate_limiting']['cache_dir'];
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0o2775, true);
        }

        $fresh = ['count' => 1, 'window_start' => time()];
        $handle = @fopen($cacheDir . $key . '.json', 'c+');
        if ($handle === false) {
            // Sin almacenamiento no se puede contar: se registra y la petición continúa
            Loggin::error('Rate limiting sin almacenamiento escribible: ' . $cacheDir, 'security');

            return $fresh;
        }

        flock($handle, LOCK_EX);
        $data = json_decode((string) stream_get_contents($handle), true);

        if (is_array($data) && isset($data['window_start']) && self::isWithinWindow((int) $data['window_start'], $window)) {
            $data = ['count' => (int) ($data['count'] ?? 0) + 1, 'window_start' => (int) $data['window_start']];
        } else {
            $data = $fresh;
        }

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) json_encode($data));
        flock($handle, LOCK_UN);
        fclose($handle);

        return $data;
    }

    /**
     * Verifica si la ventana de tiempo aún es válida.
     */
    private static function isWithinWindow(int $windowStart, int $windowDuration): bool
    {
        return (time() - $windowStart) < $windowDuration;
    }

    /**
     * Verifica si una cadena es JSON válido.
     *
     * @param string $string Cadena a verificar
     *
     * @return bool True si es JSON válido
     */
    private static function isValidJson(string $string): bool
    {
        if (in_array(trim($string), ['', '0'], true)) {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Sanitiza un campo JSON verificando su estructura sin corromper el formato.
     *
     * @param string $jsonString JSON como string
     *
     * @return string JSON sanitizado
     */
    private static function sanitizeJsonField(string $jsonString): string
    {
        // Decodificar JSON para verificar estructura
        $decoded = json_decode($jsonString, true);

        if ($decoded === null) {
            // Si no es JSON válido, tratar como texto normal
            return self::sanitizeForJson(self::sanitizeSqlInput($jsonString));
        }

        // Verificar si el JSON contiene patrones maliciosos en sus valores
        if (self::containsMaliciousJsonContent($decoded)) {
            // Si contiene contenido malicioso, sanitizar recursivamente
            $sanitized = self::sanitizeJsonContent($decoded);

            return json_encode($sanitized, JSON_UNESCAPED_UNICODE);
        }

        // Si el JSON es seguro, retornarlo tal como está
        return $jsonString;
    }

    /**
     * Verifica si el contenido JSON contiene patrones maliciosos.
     *
     * @param mixed $data Datos decodificados del JSON (array, string, int, bool, etc.)
     *
     * @return bool True si contiene contenido malicioso
     */
    private static function containsMaliciousJsonContent($data): bool
    {
        // Si es array, revisar recursivamente
        if (is_array($data)) {
            foreach ($data as $value) {
                if (!self::containsMaliciousJsonContent($value)) {
                    continue;
                }

                return true;
            }
        } elseif (is_string($data)) {
            // Solo considerar malicioso si contiene inyecciones SQL reales
            if (self::containsSqlInjection($data)) {
                return true;
            }

            // O XSS real (no solo tags HTML legítimos)
            if (self::containsRealXss($data)) {
                return true;
            }
        }

        // Para otros tipos (int, bool, float, null) no hay patrones maliciosos

        return false;
    }

    /**
     * Sanitiza recursivamente el contenido JSON.
     *
     * @param mixed $data Datos a sanitizar (array, string, int, bool, etc.)
     *
     * @return mixed Datos sanitizados
     */
    private static function sanitizeJsonContent($data)
    {
        // Si es array, sanitizar recursivamente
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $sanitized[$key] = self::sanitizeJsonContent($value);
            }

            return $sanitized;
        }

        if (is_string($data)) {
            // Solo sanitizar si realmente contiene patrones maliciosos
            if (self::containsSqlInjection($data) || self::containsRealXss($data)) {
                return self::sanitizeSqlInput($data);
            }

            return $data; // Mantener valor original si es seguro
        }

        // Para otros tipos (int, bool, float, null) retornar sin cambios
        return $data;
    }

    /**
     * Detecta XSS real (no solo tags HTML legítimos).
     *
     * @param string $input Entrada a verificar
     *
     * @return bool True si contiene XSS real
     */
    private static function containsRealXss(string $input): bool
    {
        $xssPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i',
            '/javascript\s*:/i',
            '/data\s*:\s*text\/html/i',
            '/on\w+\s*=/i', // event handlers
            '/<iframe\b[^>]*src\s*=\s*["\']?javascript:/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (!preg_match($pattern, $input)) {
                continue;
            }

            return true;
        }

        return false;
    }
}
