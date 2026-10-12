# Interfaz de Línea de Comandos (versaCLI)

`versaCLI` se ejecuta desde la raíz del proyecto (o desde cualquier directorio indicando su ruta, por ejemplo en un cron):

```bash
php versaCLI <módulo>:<acción>[:<parámetro>] [opciones]
php versaCLI help          # lista completa
php versaCLI migrate       # ayuda de un módulo
```

## Opciones globales

| Opción | Efecto |
|---|---|
| `--verbose`, `-v` | Muestra el detalle de lo que se ejecuta. |
| `--force` | Omite la confirmación de los comandos destructivos (`migrate:refresh`, `migrate:dropAll`). Pensado para despliegues automáticos. |

Los nombres que se pasan a generadores y borradores solo admiten letras, números, `_`, `-` y `.`.

## Proyecto

| Comando | Qué hace |
|---|---|
| `serve` | Servidor de desarrollo en `localhost:8000`. |
| `status` | Estado del entorno: PHP, extensiones, base de datos, permisos de carpetas. |
| `version` | Versión del proyecto y del stack. |

## Configuración y caché

| Comando | Qué hace |
|---|---|
| `config:init` | Crea `config.json` con claves aleatorias y el token del instalador web. |
| `config:debug:true\|false` | Activa o desactiva el modo debug. |
| `config:templateCache:true\|false` | Activa o desactiva la caché de Twig. |
| `cache:clear` | Limpia las cachés de rutas, Twig y rate limiting. |
| `cache:status` | Estado de las cachés. |
| `cache:routes`, `cache:twig` | Limpia solo una caché. |
| `route:compile` | Compila todas las rutas en un solo archivo y ofrece activar `build.cacheRoute`. |
| `route:cache` | Estado de la caché de rutas. |
| `route:clear-cache` | Borra la caché de rutas y la desactiva. |

## Base de datos

| Comando | Qué hace |
|---|---|
| `migrate:make:<nombre>` | Crea una migración. |
| `migrate:up` | Ejecuta las migraciones pendientes. |
| `migrate:rollback` | Revierte la última migración. |
| `migrate:down:<nombre>` | Revierte una migración específica. |
| `migrate:refresh` | Revierte todo y vuelve a migrar. Pide confirmación. |
| `migrate:refresh:seeder` | Igual, y luego ejecuta los seeders. |
| `migrate:dropAll` | Elimina todas las tablas. Pide confirmación. |
| `seeder:make:<nombre>` | Crea un seeder. |
| `seeder:run:<nombre>` | Ejecuta un seeder. |
| `seeder:runAll` | Ejecuta todos los seeders en orden de nombre. |

Más detalle en [ORM y Base de Datos](./ORM_y_BaseDeDatos.md).

## Generadores

| Comando | Qué crea |
|---|---|
| `controller:make\|delete:<nombre>` | `app/controllers/<Nombre>Controller.php` |
| `model:make\|delete:<nombre>` | `app/models/<Nombre>.php` |
| `middleware:make\|delete:<nombre>` | `app/middleware/<Nombre>Middleware.php` |
| `route:make\|delete:<nombre>` | `app/routes/<Nombre>Routes.php` |
| `route:list` | Lista las rutas de un archivo (interactivo). |
| `front:makeTwig:<nombre>` | Plantilla Twig. |
| `front:makeVue:<nombre>` | Componente Vue. |
| `RCMD:<nombre>` | Ruta + controlador + modelo + middleware. |
| `versaMODULE:twig\|vue:<nombre>` | Lo anterior más la vista Twig o el componente Vue. |

Las rutas generadas quedan protegidas con `checkSession` y `onlyUserWithAccess`. Quita esos middlewares solo si la ruta debe ser pública.

## Logs

| Comando | Qué hace |
|---|---|
| `logs:list` | Lista los archivos de `versaWYS/logs/`. |
| `logs:view:<nombre>` | Muestra un log completo. |
| `logs:tail:<nombre>` | Últimas 20 líneas. |
| `logs:clear` | Elimina los logs. |

## Tareas programadas

```bash
php versaCLI cron:make:LimpiarTemporales   # crea app/cron/LimpiarTemporales.php
php versaCLI cron:list
php versaCLI cron:run:LimpiarTemporales
```

No hay planificador interno: cada tarea se agenda en el crontab del sistema.

```cron
0 3 * * * php /var/www/mi-proyecto/current/versaCLI cron:run:LimpiarTemporales
```

## Comandos de la aplicación

Los comandos propios viven en `app/commands/`. El nombre de la clase define el comando: `EmailSendCommand` se ejecuta como `app:email:send`.

```bash
php versaCLI command:make:email:send "Envía correos pendientes"
php versaCLI command:list
php versaCLI app:email:send --help
php versaCLI command:refresh        # tras agregar o renombrar comandos
```

Comandos incluidos:

| Comando | Qué hace |
|---|---|
| `app:mail:send` | Envía un correo (plantilla Twig o HTML). Lo usa el framework para enviar correos en segundo plano. |
| `app:check:psr4` | Revisa que clases, namespaces y nombres de archivo coincidan (importante al pasar de Windows a Linux). |
| `app:check:deploy` | Valida el proyecto antes de desplegar: rutas de Windows, finales de línea, permisos y credenciales en el código. |

Desde el código PHP puedes lanzar un comando con `versaWYS\kernel\cli\CommandExecutor::run()` o, sin esperar el resultado, con `runInBackground()`.
