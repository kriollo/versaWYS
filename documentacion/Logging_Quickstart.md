# 🚀 Guía Rápida - Sistema de Logging versaWYS

## Inicio Rápido en 5 Minutos

### 1. Configuración Básica

Ya está configurado en `versaWYS/config/config.json`:

```json
{
    "logging": {
        "dir": "./logs/",
        "min_level": "DEBUG",
        "default_module": "app",
        "use_console": false,
        "format": "text",
        "max_file_size": 10485760,
        "retention_days": 30,
        "include_stack_trace": true,
        "timezone": "America/Santiago"
    }
}
```

### 2. Uso Inmediato

```php
<?php
use versaWYS\kernel\Loggin;

// ¡Listo! Ya puedes usar el logger sin inicializar nada

// Log básico
Loggin::info('Usuario autenticado exitosamente');

// Log con módulo
Loggin::info('Usuario autenticado exitosamente', 'auth');

// Log con contexto
Loggin::info('Usuario autenticado', 'auth', [
    'user_id' => 123,
    'email' => 'usuario@example.com'
]);
```

### 3. Reemplazar error_log()

**❌ Antes:**

```php
error_log("Usuario registrado: $userId");
error_log("ERROR: " . $e->getMessage());
```

**✅ Ahora:**

```php
Loggin::info('Usuario registrado', 'auth', ['user_id' => $userId]);
Loggin::exception($e, 'auth');
```

## Niveles de Log

```php
<?php
// De menor a mayor severidad
Loggin::debug('Info de debugging', 'module');      // Desarrollo
Loggin::info('Operación normal', 'module');         // Información
Loggin::notice('Evento importante', 'module');      // Noticia
Loggin::warning('Advertencia', 'module');           // Cuidado
Loggin::error('Error recuperable', 'module');       // Error
Loggin::critical('Componente caído', 'module');     // Crítico
Loggin::alert('Acción inmediata', 'module');        // Alerta
Loggin::emergency('Sistema caído', 'module');       // Emergencia
```

## Módulos Comunes

```php
<?php
Loggin::info('mensaje', 'auth');        // Autenticación
Loggin::info('mensaje', 'database');    // Base de datos
Loggin::info('mensaje', 'cache');       // Caché
Loggin::info('mensaje', 'email');       // Emails
Loggin::info('mensaje', 'payment');     // Pagos
Loggin::info('mensaje', 'api');         // APIs externas
Loggin::info('mensaje', 'cron');        // Tareas programadas
Loggin::info('mensaje', 'http');        // Requests HTTP
```

## Helpers Útiles

### 1. Log de Excepciones

```php
<?php
try {
    // código
} catch (Exception $e) {
    Loggin::exception($e, 'modulo');
    // Automáticamente incluye stack trace
}
```

### 2. Log de Queries SQL

```php
<?php
$start = microtime(true);
$result = $db->query($sql, $params);
$duration = microtime(true) - $start;

Loggin::query($sql, $params, $duration, 'database');
```

### 3. Log de Autenticación

```php
<?php
// Login exitoso
Loggin::auth('login', $userId, true);

// Login fallido
Loggin::auth('login', $username, false, ['reason' => 'invalid_password']);

// Logout
Loggin::auth('logout', $userId, true);
```

### 4. Log de Performance

```php
<?php
$start = microtime(true);
// operación pesada
$duration = microtime(true) - $start;

Loggin::performance('nombreOperacion', $duration, 'jobs');
// WARNING automático si tarda más de 1 segundo
```

## Ejemplos por Caso de Uso

### En Controladores

```php
<?php
namespace app\controllers;

use versaWYS\kernel\Loggin;

class UserController
{
    public function register(Request $request): Response
    {
        Loggin::info('Inicio de registro', 'auth');

        try {
            $user = User::create($request->all());

            Loggin::info('Usuario registrado', 'auth', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return Response::json(['success' => true]);

        } catch (Exception $e) {
            Loggin::exception($e, 'auth');
            return Response::json(['error' => 'Server error'], 500);
        }
    }
}
```

### En Middleware

```php
<?php
class AuthMiddleware
{
    public function checkSession(Request $request): bool
    {
        if (!Session::has('user_id')) {
            Loggin::warning('Acceso sin sesión', 'auth', [
                'ip' => $request->ip(),
                'uri' => $request->uri()
            ]);
            return false;
        }

        Loggin::debug('Sesión válida', 'auth', [
            'user_id' => Session::get('user_id')
        ]);

        return true;
    }
}
```

### En Modelos

```php
<?php
class User extends Model
{
    protected function afterSave(): void
    {
        $action = $this->wasRecentlyCreated() ? 'created' : 'updated';

        Loggin::info("Usuario $action", 'database', [
            'user_id' => $this->id,
            'action' => $action
        ]);
    }
}
```

### En Jobs/Cron

```php
<?php
class ProcessEmailsJob
{
    public function execute(): void
    {
        Loggin::info('Iniciando procesamiento de emails', 'cron');

        $start = microtime(true);
        $sent = 0;

        foreach ($emails as $email) {
            try {
                $this->send($email);
                $sent++;
            } catch (Exception $e) {
                Loggin::error('Error al enviar email', 'email', [
                    'to' => $email['to'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        $duration = microtime(true) - $start;

        Loggin::info('Emails procesados', 'cron', [
            'sent' => $sent,
            'duration' => round($duration, 2) . 's'
        ]);
    }
}
```

## Archivos Generados

Los logs se guardan automáticamente en:

```
logs/
├── auth_2024_01_15.log
├── database_2024_01_15.log
├── email_2024_01_15.log
├── cron_2024_01_15.log
└── app_2024_01_15.log
```

### Formato de Salida (text)

```
[2024-01-15 10:30:45] [INFO     ] [auth           ] Usuario autenticado | Context: {"user_id":123,"email":"user@example.com"}
[2024-01-15 10:31:12] [ERROR    ] [database       ] Error al guardar | Context: {"table":"users","error":"Connection timeout"}
  Stack Trace:
    #0 /app/controllers/UserController.php:42 save()
    #1 /versaWYS/kernel/Router.php:156 dispatch()
```

### Formato JSON

```json
{
    "timestamp": "2024-01-15 10:30:45",
    "level": "INFO",
    "module": "auth",
    "message": "Usuario autenticado",
    "context": {
        "user_id": 123,
        "email": "user@example.com"
    }
}
```

## Consultar Logs

```php
<?php
// Leer logs de hoy
$logs = Loggin::read('auth');

// Leer logs de fecha específica
$logs = Loggin::read('auth', '2024-01-15');

// Filtrar por nivel
$errors = Loggin::read('auth', '2024-01-15', Loggin::ERROR);

```

## Ver Logs en Tiempo Real

```bash
# Linux/Mac
tail -f logs/auth_2024_01_15.log

# Ver solo errores
tail -f logs/auth_2024_01_15.log | grep ERROR

# Windows PowerShell
Get-Content logs/auth_2024_01_15.log -Wait -Tail 50
```

## Configuración por Ambiente

### Desarrollo

```json
{
    "logging": {
        "min_level": "DEBUG",
        "use_console": true,
        "format": "text"
    }
}
```

### Producción

```json
{
    "logging": {
        "min_level": "INFO",
        "format": "json"
    }
}
```

## Handler Personalizado

```php
<?php
// Enviar logs críticos a Slack
Loggin::setCustomHandler(function($level, $message, $module, $context, $formatted) {
    if ($level === Loggin::CRITICAL) {
        // Enviar notificación a Slack
        notifySlack($message, $context);
    }

    if ($level === Loggin::EMERGENCY) {
        // Enviar email a administradores
        emailAdmins($message);
    }
});
```

## Mejores Prácticas

### ✅ HACER

- Usar niveles apropiados
- Agregar contexto relevante
- Usar módulos consistentes
- Usar helpers especializados
- Establecer `min_level` según ambiente

### ❌ NO HACER

- Loguear contraseñas o datos sensibles
- Usar nivel ERROR para eventos normales
- Loguear objetos muy grandes en producción
- Dejar nivel DEBUG en producción

## Solución Rápida de Problemas

### Los logs no se guardan

```php
<?php
// Verificar permisos
chmod('./logs/', 0755);

// Verificar configuración
$config = Loggin::getConfig();
print_r($config);

// Forzar log de prueba
Loggin::emergency('Test log', 'test');
```

### Archivos muy grandes

```json
{
    "logging": {
        "max_file_size": 5242880,
        "retention_days": 7,
        "min_level": "INFO"
    }
}
```

## Migrar desde error_log()

Buscar y reemplazar en tu proyecto:

```bash
# Buscar
grep -r "error_log" app/ --include="*.php"

# Reemplazar
error_log("mensaje")              → Loggin::info("mensaje", 'modulo')
error_log("ERROR: " . $msg)       → Loggin::error($msg, 'modulo')
error_log("WARNING: " . $msg)     → Loggin::warning($msg, 'modulo')
error_log(print_r($data, true))   → Loggin::debug('Data', 'modulo', $data)
```

## Recursos

- **Documentación Completa**: `documentacion/Logging.md`
- **Clase**: `versaWYS/vendor/versawys/kernel/src/Loggin.php` (paquete `versawys/kernel`)
- **Configuración**: `versaWYS/config/config.json`

## Cheatsheet

```php
<?php
use versaWYS\kernel\Loggin;

// Niveles básicos
Loggin::debug($msg, $module, $context);
Loggin::info($msg, $module, $context);
Loggin::warning($msg, $module, $context);
Loggin::error($msg, $module, $context);

// Helpers
Loggin::exception($e, $module);
Loggin::query($sql, $params, $time);
Loggin::auth($action, $userId, $success);
Loggin::performance($operation, $duration);

// Consultas
Loggin::read($module, $date, $level);
Loggin::getConfig();
```

---

**¡Listo!** Ya puedes empezar a usar el sistema de logging en todo tu proyecto versaWYS. 🎉

Para más información, consulta la documentación completa en `Loggin.md`.
