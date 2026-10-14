# Loggin - Sistema de Logging Centralizado

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Instalación y Configuración](#instalación-y-configuración)
3. [Niveles de Log](#niveles-de-log)
4. [Uso Básico](#uso-básico)
5. [Módulos](#módulos)
6. [Contexto Adicional](#contexto-adicional)
7. [Métodos Helper](#métodos-helper)
8. [Handlers Múltiples](#handlers-múltiples)
9. [Consulta de Logs](#consulta-de-logs)
10. [Ejemplos Prácticos](#ejemplos-prácticos)
11. [Mejores Prácticas](#mejores-prácticas)
12. [Troubleshooting](#troubleshooting)

---

## Introducción

`Loggin` es el sistema de logging centralizado de versaWYS Framework. Proporciona una interfaz completa y flexible para registrar eventos, errores y mensajes de debug en toda la aplicación.

### Características Principales

✅ **8 Niveles de Log** - DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY  
✅ **Organización por Módulos** - Logs separados por módulo de la aplicación  
✅ **Contexto Rico** - Array de datos adicionales en cada log  
✅ **Handlers** - Archivo y consola  
✅ **Rotación Automática** - Por tamaño y fecha  
✅ **Stack Traces** - Automáticos en errores  
✅ **Formato Flexible** - Texto plano o JSON  
✅ **Performance** - Optimizado para no afectar la aplicación  
✅ **Filtrado** - Por nivel y módulo  
✅ **Limpieza Automática** - Retención configurable

---

## Instalación y Configuración

### Configuración en `config.json`

```json
{
    "logging": {
        "dir": "./logs/",
        "min_level": "DEBUG",
        "default_module": "app",
        "use_console": true,
        "format": "text",
        "max_file_size": 10485760,
        "retention_days": 30,
        "include_stack_trace": true,
        "timezone": "America/Santiago"
    }
}
```

### Parámetros de Configuración

| Parámetro             | Tipo   | Default    | Descripción                          |
| --------------------- | ------ | ---------- | ------------------------------------ |
| `dir`                 | string | `./logs/`  | Directorio donde se guardan los logs |
| `min_level`           | string | `DEBUG`    | Nivel mínimo de log a registrar      |
| `default_module`      | string | `app`      | Módulo por defecto                   |
| `use_console`         | bool   | `false`    | Mostrar logs en consola (CLI)        |
| `format`              | string | `text`     | Formato: `text` o `json`             |
| `max_file_size`       | int    | `10485760` | Tamaño máximo por archivo (bytes)    |
| `retention_days`      | int    | `30`       | Días de retención de logs            |
| `include_stack_trace` | bool   | `true`     | Incluir stack trace en errores       |
| `timezone`            | string | `UTC`      | Zona horaria                         |

### Inicialización

```php
<?php
use versaWYS\kernel\Loggin;

// Inicialización automática (usa config.json)
Loggin::info('Aplicación iniciada');

// Inicialización manual con configuración personalizada
Loggin::initialize([
    'logging' => [
        'dir' => './logs/',
        'min_level' => 'INFO',
        'format' => 'json',
    ],
]);
```

---

## Niveles de Log

El sistema soporta 8 niveles estándar (compatible con PSR-3):

### Tabla de Niveles

| Nivel       | Prioridad | Descripción                          | Uso                                       |
| ----------- | --------- | ------------------------------------ | ----------------------------------------- |
| `DEBUG`     | 0         | Información detallada de debugging   | Variables, estados internos               |
| `INFO`      | 1         | Eventos informativos                 | Operaciones normales                      |
| `NOTICE`    | 2         | Eventos normales pero significativos | Eventos importantes                       |
| `WARNING`   | 3         | Advertencias                         | Situaciones anormales no críticas         |
| `ERROR`     | 4         | Errores de ejecución                 | Errores que no requieren acción inmediata |
| `CRITICAL`  | 5         | Condiciones críticas                 | Componentes no disponibles                |
| `ALERT`     | 6         | Se requiere acción inmediata         | Situaciones graves                        |
| `EMERGENCY` | 7         | Sistema inutilizable                 | Sistema caído                             |

### Constantes Disponibles

```php
<?php
Loggin::DEBUG      // 'DEBUG'
Loggin::INFO       // 'INFO'
Loggin::NOTICE     // 'NOTICE'
Loggin::WARNING    // 'WARNING'
Loggin::ERROR      // 'ERROR'
Loggin::CRITICAL   // 'CRITICAL'
Loggin::ALERT      // 'ALERT'
Loggin::EMERGENCY  // 'EMERGENCY'
```

---

## Uso Básico

### Método General

```php
<?php
use versaWYS\kernel\Loggin;

// Sintaxis completa
Loggin::log(level: Loggin::INFO, message: 'Usuario registrado', module: 'auth', context: ['user_id' => 123]);
```

### Métodos de Conveniencia

```php
<?php
// Debug
Loggin::debug('Valor de variable X', 'modulo', ['x' => $valor]);

// Info
Loggin::info('Operación completada', 'modulo');

// Notice
Loggin::notice('Evento importante', 'modulo');

// Warning
Loggin::warning('Advertencia: límite cercano', 'modulo');

// Error
Loggin::error('Error al procesar solicitud', 'modulo');

// Critical
Loggin::critical('Base de datos no disponible', 'database');

// Alert
Loggin::alert('Disco lleno al 95%', 'system');

// Emergency
Loggin::emergency('Sistema caído', 'system');
```

### Ejemplos Simples

```php
<?php
// Log básico sin módulo ni contexto
Loggin::info('Servidor iniciado');

// Log con módulo
Loggin::info('Usuario autenticado', 'auth');

// Log con contexto
Loggin::error('Error al guardar', 'database', [
    'table' => 'users',
    'error' => 'Connection timeout',
]);

// Log completo
Loggin::log(Loggin::WARNING, 'Caché no disponible, usando fallback', 'cache', [
    'cache_server' => 'redis1',
    'fallback' => 'memory',
]);
```

---

## Módulos

Los módulos permiten organizar logs por componente de la aplicación.

### Convención de Nombres

```php
<?php
// Módulos comunes
Loggin::info('mensaje', 'auth'); // Autenticación
Loggin::info('mensaje', 'database'); // Base de datos
Loggin::info('mensaje', 'cache'); // Sistema de caché
Loggin::info('mensaje', 'email'); // Envío de emails
Loggin::info('mensaje', 'payment'); // Procesamiento de pagos
Loggin::info('mensaje', 'api'); // API externa
Loggin::info('mensaje', 'cron'); // Tareas programadas
Loggin::info('mensaje', 'websocket'); // WebSockets
```

### Archivos Generados

Los logs se guardan en archivos separados por módulo y fecha:

```
logs/
├── auth_2024_01_15.log
├── database_2024_01_15.log
├── cache_2024_01_15.log
├── email_2024_01_15.log
└── app_2024_01_15.log
```

### Módulo por Defecto

```php
<?php
// Si no se especifica módulo, usa el default (configurado en config.json)
Loggin::info('Este log va al módulo por defecto');

// Equivalente a:
Loggin::info('Este log va al módulo por defecto', 'app');
```

---

## Contexto Adicional

El contexto permite agregar datos estructurados a cada log.

### Uso Básico

```php
<?php
Loggin::error('Error al procesar pago', 'payment', [
    'order_id' => 12345,
    'amount' => 99.99,
    'currency' => 'USD',
    'gateway' => 'stripe',
    'error_code' => 'card_declined',
]);
```

### Contexto Automático en Errores

Para niveles ERROR y superiores, se agrega automáticamente:

```php
<?php
// Stack trace
'trace' => [
    ['file' => 'Controller.php', 'line' => 42, 'function' => 'process'],
    ['file' => 'Router.php', 'line' => 156, 'function' => 'dispatch']
]

// Información de la petición HTTP
'request' => [
    'method' => 'POST',
    'uri' => '/api/payment',
    'ip' => '192.168.1.1',
    'user_agent' => 'Mozilla/5.0...'
]
```

### Contexto en DEBUG

En modo DEBUG se agrega información de performance:

```php
<?php
'memory_usage' => 2097152,      // Bytes
'memory_peak' => 3145728,       // Bytes
'execution_time' => 0.0234      // Segundos
```

### Ejemplo Completo

```php
<?php
try {
    $order = processOrder($orderId);

    Loggin::info('Orden procesada exitosamente', 'orders', [
        'order_id' => $order->id,
        'user_id' => $order->user_id,
        'total' => $order->total,
        'items_count' => count($order->items),
        'processing_time' => microtime(true) - $startTime,
    ]);
} catch (Exception $e) {
    Loggin::error('Error al procesar orden', 'orders', [
        'order_id' => $orderId,
        'error' => $e->getMessage(),
        'user_id' => $userId,
    ]);
}
```

---

## Métodos Helper

El sistema incluye métodos especializados para casos comunes.

### 1. Log de Excepciones

```php
<?php
try {
    // Código que puede fallar
    conectarBaseDatos();
} catch (Exception $e) {
    // Log automático de excepción con stack trace
    Loggin::exception($e, 'database');
}

// Genera un log con:
// - Clase de la excepción
// - Mensaje
// - Código de error
// - Archivo y línea
// - Stack trace completo
```

### 2. Log de Queries SQL

```php
<?php
$start = microtime(true);
$result = $db->query($sql, $params);
$duration = microtime(true) - $start;

Loggin::query($sql, $params, $duration, 'database');

// Output:
// [2024-01-15 10:30:45] [DEBUG] [database] SQL Query executed
// Context: {"query":"SELECT * FROM users WHERE id = ?","params":[123],"execution_time":"0.0123s"}
```

### 3. Log de Peticiones HTTP

```php
<?php
$start = microtime(true);
$response = $httpClient->get('https://api.example.com/data');
$duration = microtime(true) - $start;

Loggin::request(
    method: 'GET',
    url: 'https://api.example.com/data',
    statusCode: $response->getStatusCode(),
    responseTime: $duration,
    module: 'api',
);

// Nivel automático según status code:
// 2xx -> INFO
// 4xx -> WARNING
// 5xx -> ERROR
```

### 4. Log de Autenticación

```php
<?php
// Login exitoso
Loggin::auth('login', $userId, true, [
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
]);

// Login fallido
Loggin::auth('login', $username, false, [
    'reason' => 'invalid_password',
    'attempts' => 3,
]);

// Logout
Loggin::auth('logout', $userId, true);

// Cambio de contraseña
Loggin::auth('password_change', $userId, true);
```

### 5. Log de Performance

```php
<?php
$start = microtime(true);

// Operación pesada
$result = procesarDatosComplejos();

$duration = microtime(true) - $start;

Loggin::performance('procesarDatosComplejos', $duration, 'jobs', [
    'records_processed' => count($result),
    'memory_used' => memory_get_usage(true),
]);

// WARNING automático si tarda más de 1 segundo
```

---

## Handlers Múltiples

El sistema puede escribir logs en múltiples destinos simultáneamente.

### 1. Handler de Archivo (Default)

```php
<?php
// Siempre activo
Loggin::info('Este log se guarda en archivo');

// Formato del archivo:
// [2024-01-15 10:30:45] [INFO     ] [auth           ] Usuario autenticado | Context: {"user_id":123}
```

### 2. Handler de Consola

```php
<?php
// Habilitar en config.json
{
  "logging": {
    "use_console": true
  }
}

// Solo funciona en CLI
// Muestra logs con colores ANSI:
// DEBUG -> Cyan
// INFO -> Green
// WARNING -> Yellow
// ERROR -> Red
// CRITICAL -> Magenta
// EMERGENCY -> Red background
```

### 4. Handler Personalizado

```php
<?php
// Definir handler personalizado
Loggin::setCustomHandler(function ($level, $message, $module, $context, $formatted) {
    // Enviar a servicio externo
    if ($level === Loggin::CRITICAL) {
        enviarNotificacionSlack($message, $context);
    }

    // Guardar en base de datos
    if (in_array($level, [Loggin::ERROR, Loggin::CRITICAL])) {
        guardarEnBD($level, $message, $module, $context);
    }

    // Enviar email a administradores
    if ($level === Loggin::EMERGENCY) {
        enviarEmailEmergencia($message);
    }
});
```

---

## Consulta de Logs

### Leer Logs

```php
<?php
// Leer todos los logs de hoy de un módulo
$logs = Loggin::read('auth');

// Leer logs de una fecha específica
$logs = Loggin::read('auth', '2024-01-15');

// Filtrar por nivel
$errors = Loggin::read('auth', '2024-01-15', Loggin::ERROR);
$warnings = Loggin::read('database', null, Loggin::WARNING);

// Resultado (si formato es JSON):
[
    [
        'timestamp' => '2024-01-15 10:30:45',
        'level' => 'ERROR',
        'module' => 'auth',
        'message' => 'Login fallido',
        'context' => ['user_id' => 123],
    ],
    // ...
];
```

### Estadísticas

```php
<?php
// Obtener estadísticas de logs

// Resultado:
[
    'total' => 1523,
    'by_level' => [
        'DEBUG' => 500,
        'INFO' => 800,
        'NOTICE' => 50,
        'WARNING' => 150,
        'ERROR' => 20,
        'CRITICAL' => 3,
        'ALERT' => 0,
        'EMERGENCY' => 0
    ]
]

// Dashboard simple
foreach ($stats['by_level'] as $level => $count) {
    if ($count > 0) {
        echo "$level: $count\n";
    }
}
```

### Obtener Configuración

```php
<?php
$config = Loggin::getConfig();
print_r($config);

// Resultado:
[
    'log_dir' => './logs/',
    'min_level' => 'DEBUG',
    'default_module' => 'app',
    'use_console' => false,
    'format' => 'text',
    'max_file_size' => 10485760,
    'retention_days' => 30,
    'timezone' => 'America/Santiago',
    'include_stack_trace' => true,
];
```

---

## Ejemplos Prácticos

### 1. Logging en Controladores

```php
<?php
namespace app\controllers;

use versaWYS\kernel\Loggin;

class UserController
{
    public function register(Request $request): Response
    {
        Loggin::info('Inicio de registro de usuario', 'auth');

        try {
            $data = $request->validate([
                'email' => 'required|email',
                'password' => 'required|min:8',
            ]);

            $user = User::create($data);

            Loggin::info('Usuario registrado exitosamente', 'auth', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return Response::json(['success' => true]);
        } catch (ValidationException $e) {
            Loggin::warning('Validación fallida en registro', 'auth', [
                'errors' => $e->getErrors(),
                'input' => $request->all(),
            ]);

            return Response::json(['errors' => $e->getErrors()], 422);
        } catch (Exception $e) {
            Loggin::exception($e, 'auth');
            return Response::json(['error' => 'Server error'], 500);
        }
    }
}
```

### 2. Logging en Jobs/Cron

```php
<?php
class EmailQueueProcessor
{
    public function process(): void
    {
        Loggin::info('Iniciando procesamiento de cola de emails', 'cron');

        $start = microtime(true);
        $sent = 0;
        $failed = 0;

        while ($email = $this->getNextEmail()) {
            try {
                $this->sendEmail($email);
                $sent++;

                Loggin::debug('Email enviado', 'email', [
                    'to' => $email['to'],
                    'subject' => $email['subject'],
                ]);
            } catch (Exception $e) {
                $failed++;

                Loggin::error('Error al enviar email', 'email', [
                    'to' => $email['to'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $duration = microtime(true) - $start;

        Loggin::info('Procesamiento de emails completado', 'cron', [
            'sent' => $sent,
            'failed' => $failed,
            'duration' => round($duration, 2) . 's',
        ]);

        if ($duration > 60) {
            Loggin::warning('Procesamiento de emails tardó demasiado', 'cron', [
                'duration' => $duration,
                'threshold' => 60,
            ]);
        }
    }
}
```

### 3. Logging en Middleware

```php
<?php
class LoggingMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);

        // Log de entrada
        Loggin::debug('Request recibido', 'http', [
            'method' => $request->method(),
            'uri' => $request->uri(),
            'ip' => $request->ip(),
        ]);

        try {
            $response = $next($request);
            $duration = microtime(true) - $start;

            // Log de salida exitosa
            Loggin::request($request->method(), $request->uri(), $response->getStatusCode(), $duration, 'http');

            return $response;
        } catch (Exception $e) {
            $duration = microtime(true) - $start;

            // Log de error
            Loggin::error('Request falló', 'http', [
                'method' => $request->method(),
                'uri' => $request->uri(),
                'duration' => $duration,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

### 4. Logging en Modelos

```php
<?php
class User extends Model
{
    protected function beforeSave(): void
    {
        Loggin::debug('Guardando usuario', 'database', [
            'table' => 'users',
            'id' => $this->id ?? 'new',
            'data' => $this->toArray(),
        ]);
    }

    protected function afterSave(): void
    {
        $action = $this->wasRecentlyCreated() ? 'created' : 'updated';

        Loggin::info("Usuario $action", 'database', [
            'user_id' => $this->id,
            'action' => $action,
        ]);
    }

    protected function beforeDelete(): void
    {
        Loggin::warning('Eliminando usuario', 'database', [
            'user_id' => $this->id,
            'email' => $this->email,
        ]);
    }
}
```

### 5. Sistema de Monitoreo

```php
<?php
class HealthCheck
{
    public function check(): array
    {
        $results = [];

        // Check base de datos
        try {
            DB::query('SELECT 1');
            $results['database'] = 'ok';
            Loggin::debug('Database health check: OK', 'monitoring');
        } catch (Exception $e) {
            $results['database'] = 'error';
            Loggin::critical('Database health check: FAILED', 'monitoring', [
                'error' => $e->getMessage(),
            ]);
        }

        // Check Redis
        try {
            RedisModel::ping();
            $results['redis'] = 'ok';
            Loggin::debug('Redis health check: OK', 'monitoring');
        } catch (Exception $e) {
            $results['redis'] = 'error';
            Loggin::critical('Redis health check: FAILED', 'monitoring');
        }

        // Check disco
        $diskFree = disk_free_space('/');
        $diskTotal = disk_total_space('/');
        $diskUsagePercent = (1 - $diskFree / $diskTotal) * 100;

        if ($diskUsagePercent > 90) {
            Loggin::alert('Disco casi lleno', 'monitoring', [
                'usage_percent' => round($diskUsagePercent, 2),
                'free_space' => round($diskFree / 1024 / 1024 / 1024, 2) . 'GB',
            ]);
        }

        return $results;
    }
}
```

### 6. Logging de API Externa

```php
<?php
class PaymentGateway
{
    public function charge(array $paymentData): array
    {
        Loggin::info('Iniciando cargo en gateway', 'payment', [
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currency'],
        ]);

        $start = microtime(true);

        try {
            $response = $this->httpClient->post('/charge', $paymentData);
            $duration = microtime(true) - $start;

            Loggin::request('POST', 'https://gateway.com/charge', $response->getStatusCode(), $duration, 'payment');

            $result = $response->json();

            if ($result['success']) {
                Loggin::info('Cargo exitoso', 'payment', [
                    'transaction_id' => $result['transaction_id'],
                    'amount' => $paymentData['amount'],
                ]);
            } else {
                Loggin::error('Cargo rechazado', 'payment', [
                    'reason' => $result['error'],
                    'amount' => $paymentData['amount'],
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Loggin::critical('Error en gateway de pago', 'payment', [
                'error' => $e->getMessage(),
                'amount' => $paymentData['amount'],
            ]);

            throw $e;
        }
    }
}
```

---

## Mejores Prácticas

### 1. Usar Niveles Apropiados

```php
<?php
// ✅ BUENO: Niveles apropiados
Loggin::debug('Variable x = ' . $x, 'app'); // Debug info
Loggin::info('Usuario logueado', 'auth'); // Evento normal
Loggin::warning('Cache miss, usando BD', 'cache'); // Advertencia
Loggin::error('No se pudo enviar email', 'email'); // Error recuperable
Loggin::critical('BD no responde', 'database'); // Sistema crítico
Loggin::emergency('Servidor sin memoria', 'system'); // Emergencia

// ❌ MALO: Nivel incorrecto
Loggin::error('Usuario logueado', 'auth'); // Debería ser INFO
Loggin::debug('Sistema caído', 'system'); // Debería ser EMERGENCY
```

### 2. Usar Módulos Consistentes

```php
<?php
// ✅ BUENO: Módulos consistentes y descriptivos
Loggin::info('mensaje', 'auth');
Loggin::info('mensaje', 'database');
Loggin::info('mensaje', 'payment');

// ❌ MALO: Módulos inconsistentes
Loggin::info('mensaje', 'Auth'); // Inconsistente con minúsculas
Loggin::info('mensaje', 'db'); // Diferente a 'database'
Loggin::info('mensaje', 'payments'); // Plural inconsistente
```

### 3. Agregar Contexto Relevante

```php
<?php
// ✅ BUENO: Contexto útil
Loggin::error('Error al procesar orden', 'orders', [
    'order_id' => $orderId,
    'user_id' => $userId,
    'amount' => $amount,
    'error_code' => $errorCode,
]);

// ❌ MALO: Sin contexto
Loggin::error('Error al procesar orden', 'orders');

// ❌ MALO: Demasiado contexto
Loggin::error('Error', 'orders', [
    'entire_request' => $_REQUEST,
    'entire_session' => $_SESSION,
    'entire_database_dump' => $allData,
]);
```

### 4. No Loguear Datos Sensibles

```php
<?php
// ❌ MALO: Logueando datos sensibles
Loggin::info('Login', 'auth', [
    'password' => $password, // ¡NUNCA!
    'credit_card' => $creditCard, // ¡NUNCA!
    'ssn' => $ssn, // ¡NUNCA!
]);

// ✅ BUENO: Datos seguros
Loggin::info('Login', 'auth', [
    'user_id' => $userId,
    'email' => $email,
    'ip' => $_SERVER['REMOTE_ADDR'],
]);
```

### 5. Usar Helpers Especializados

```php
<?php
// ✅ BUENO: Usar helpers
Loggin::exception($e, 'module');
Loggin::query($sql, $params, $duration);
Loggin::auth('login', $userId, true);

// ❌ MALO: Log manual
Loggin::error('Exception: ' . $e->getMessage(), 'module', [
    'file' => $e->getFile(),
    'line' => $e->getLine(),
    // ... missing trace
]);
```

### 6. Performance en Producción

```php
<?php
// ✅ BUENO: Nivel apropiado en producción
{
  "logging": {
    "min_level": "INFO"  // En producción
  }
}

// Desarrollo
{
  "logging": {
    "min_level": "DEBUG"  // En desarrollo
  }
}

// ✅ BUENO: Debug condicional
if (isDevelopment()) {
    Loggin::debug('Estado detallado', 'app', $largeArray);
}
```

### 7. Rotación y Limpieza

```php
<?php
// ✅ BUENO: Configurar límites
{
  "logging": {
    "max_file_size": 10485760,    // 10MB
    "retention_days": 30           // 30 días
  }
}

// Los archivos rotan automáticamente:
// app_2024_01_15.log
// app_2024_01_15.log.1  (cuando se supera max_file_size)
// app_2024_01_15.log.2
```

### 8. Formato JSON para Análisis

```php
<?php
// ✅ BUENO: JSON para herramientas de análisis
{
  "logging": {
    "format": "json"  // Para ELK, Splunk, etc.
  }
}

// ✅ BUENO: Texto para lectura humana
{
  "logging": {
    "format": "text"  // Para development
  }
}
```

---

## Troubleshooting

### Problema: Los logs no se guardan

```php
<?php
// Verificar permisos del directorio
$config = Loggin::getConfig();
echo 'Log dir: ' . $config['log_dir'] . "\n";

if (!is_writable($config['log_dir'])) {
    echo "Directorio no escribible\n";
    chmod($config['log_dir'], 0775);
}

// Verificar nivel mínimo
if ($config['min_level'] === 'ERROR') {
    echo "Solo se guardan errores. Cambiar a DEBUG o INFO.\n";
}

// Forzar un log de prueba
Loggin::emergency('Test log', 'test');
```

### Problema: Archivos de log muy grandes

```php
<?php
// Reducir max_file_size
{
  "logging": {
    "max_file_size": 5242880  // 5MB en lugar de 10MB
  }
}

// O reducir retention_days
{
  "logging": {
    "retention_days": 7  // 7 días en lugar de 30
  }
}

// O aumentar min_level en producción
{
  "logging": {
    "min_level": "WARNING"  // Solo warnings y superiores
  }
}
```

### Problema: Logs duplicados

```php
<?php
// Verificar que no se está llamando initialize() múltiples veces
// Verificar que no hay múltiples error_log() además de Loggin

// Reemplazar todos los error_log() por Loggin
// Buscar: error_log
// Reemplazar por: Loggin::error
```

### Problema: Logs sin stack trace

```php
<?php
// Verificar configuración
{
  "logging": {
    "include_stack_trace": true
  }
}

// O forzar en el contexto
Loggin::error('Error', 'module', [
    'trace' => debug_backtrace()
]);
```

### Problema: Redis no funciona

```php
<?php
// Verificar que RedisModel esté disponible
if (class_exists('versaWYS\kernel\model\RedisModel')) {
    if (RedisModel::ping()) {
        echo "Redis OK\n";
    } else {
        echo "Redis no responde\n";
    }
} else {
    echo "RedisModel no disponible\n";
}

// Desactivar Redis temporalmente
{
  "logging": {
  }
}
```

### Ver Logs en Tiempo Real

```bash
# Linux/Mac
tail -f logs/app_2024_01_15.log

# Ver solo errores
tail -f logs/app_2024_01_15.log | grep ERROR

# Windows PowerShell
Get-Content logs/app_2024_01_15.log -Wait -Tail 50
```

---

## Migración desde error_log()

### Antes (error_log)

```php
<?php
error_log('Usuario registrado: ' . $userId);
error_log('ERROR: ' . $e->getMessage());
error_log(print_r($data, true));
```

### Después (Loggin)

```php
<?php
Loggin::info('Usuario registrado', 'auth', ['user_id' => $userId]);
Loggin::exception($e, 'module');
Loggin::debug('Data', 'module', $data);
```

### Script de Búsqueda y Reemplazo

```bash
# Buscar todos los error_log en el proyecto
grep -r "error_log" app/ --include="*.php"

# Patrón común a reemplazar:
# error_log("mensaje") → Loggin::info("mensaje")
# error_log("ERROR: ...") → Loggin::error("...")
# error_log("WARNING: ...") → Loggin::warning("...")
```

---

## Resumen de Métodos

### Métodos Principales

- `log($level, $message, $module, $context)` - Log general
- `debug($message, $module, $context)` - Nivel DEBUG
- `info($message, $module, $context)` - Nivel INFO
- `notice($message, $module, $context)` - Nivel NOTICE
- `warning($message, $module, $context)` - Nivel WARNING
- `error($message, $module, $context)` - Nivel ERROR
- `critical($message, $module, $context)` - Nivel CRITICAL
- `alert($message, $module, $context)` - Nivel ALERT
- `emergency($message, $module, $context)` - Nivel EMERGENCY

### Métodos Helper

- `exception($exception, $module, $context)` - Log de excepción
- `query($query, $params, $time, $module)` - Log de query SQL
- `request($method, $url, $status, $time, $module)` - Log de HTTP request
- `auth($action, $userId, $success, $context)` - Log de autenticación
- `performance($operation, $duration, $module, $context)` - Log de performance

### Métodos de Consulta

- `read($module, $date, $level)` - Leer logs
- `stats($module, $date)` - Estadísticas de logs
- `getConfig()` - Obtener configuración actual

### Métodos de Configuración

- `initialize($config)` - Inicializar sistema
- `setCustomHandler($callback)` - Establecer handler personalizado

---

## Soporte

Para reportar bugs o solicitar features, contacta al equipo de desarrollo de versaWYS.

**Versión**: 2.0.0  
**Última actualización**: 2024  
**Framework**: versaWYS-PHP
