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
| `--no-interaction`, `-n` | Toda pregunta toma su valor por defecto sin esperar respuesta; una confirmación es "no". Para scripts y procesos que no tienen terminal. |

Un comando que falla o no existe, o una operación destructiva cancelada, termina con código de salida 1, así que puede usarse en scripts y CI.

Los nombres que se pasan a generadores y borradores solo admiten letras, números, `_`, `-` y `.`.

## Proyecto

| Comando | Qué hace |
|---|---|
| `serve` | Servidor de desarrollo en `localhost:8000`. Acepta `--port=N` y `--host=H`. |
| `status` | Estado del entorno: PHP, extensiones, base de datos, permisos de carpetas. |
| `version` | Versión del proyecto y del stack. |

## Configuración y caché

| Comando | Qué hace |
|---|---|
| `config:init` | Crea `config.json` con claves aleatorias y el token del instalador web. |
| `config:debug:true\|false` | Activa o desactiva el modo debug. |
| `config:templateCache:true\|false` | Activa o desactiva la caché de Twig. |
| `config:merge <archivo.json>` | Aplica ajustes versionados sin secretos (CSP, CORS, correo) sobre `config.json`. Ver [Configuración](./CONFIGURATION.md). |
| `cache:clear` | Limpia las cachés de Twig y rate limiting. |
| `cache:status` | Estado de las cachés. |
| `cache:twig` | Limpia solo la caché de Twig. |

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
| `migrate:status` | Muestra qué migraciones ya se ejecutaron y cuáles están pendientes. |
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
| `route:list` | Lista las rutas de un archivo (interactivo). Con `--all` lista todas sin preguntar: método, URL real, controlador y si valida CSRF. |
| `route:check` | Revisa todas las rutas sin ejecutarlas. Falla (código 1) si un controlador, método o middleware no existe; con `--strict` también si falta documentación. |
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
| `logs:tail:<nombre>` | Últimas 20 líneas, o las que indique `--lines=N`. |
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
| `app:check:psr4` | Revisa que clases, namespaces y nombres de archivo coincidan; también referencias a clases del proyecto que no existen y `require`/`include` cuya ruta difiere en mayúsculas de lo que hay en disco. |
| `app:check:deploy` | Revisión previa al despliegue. Ver más abajo. |

### Despliegue y archivos base

| Comando | Qué hace |
|---|---|
| `app:deploy:finish` | El único hook `post_deploy` que necesita versaDeploy: `config:merge` de `versaWYS/config.compartida.json` (si existe), `migrate:up`, `cache:twig`, activa la caché de plantillas, `command:refresh` y `app:check:deploy --production`. Se detiene en el primer paso que falle. |
| `app:kernel:publish [--force]` | Crea `index.php`, `versaCLI`, los `.htaccess` y los ejemplos de `configs/` que falten, con la versión del kernel instalado. Muestra el diff de los que difieren (`-` proyecto, `+` kernel); `--force` los reemplaza. |

### Revisión previa al despliegue

```bash
php versaCLI app:check:deploy                 # antes de subir cambios: revisa el código
php versaCLI app:check:deploy --strict        # en CI: los avisos también hacen fallar
php versaCLI app:check:deploy --production    # en el servidor: revisa además configuración y entorno
```

Termina con código 1 si encuentra algo que impida desplegar.

| Revisión | Qué detecta | Nivel |
|---|---|---|
| Sintaxis PHP | Archivos que no se pueden cargar | Crítico |
| PSR-4, clases y archivos | Namespaces que no coinciden con la ruta, clases inexistentes, `require` con otra capitalización | Crítico |
| Rutas de la aplicación | Controladores, métodos o middlewares que no existen; documentación faltante | Crítico / aviso |
| Contrato kernel ↔ app | Métodos de `app/models` y `app/services` que el kernel llama y el proyecto no tiene | Crítico |
| Plantillas Twig | `render`, `extends` o `include` a plantillas que no existen o con otra capitalización | Crítico |
| Windows → Linux | Rutas `C:\`, comandos de Windows en llamadas al sistema, scripts con CRLF | Aviso / crítico |
| Restos de depuración | `var_dump`, `dd`, `dump`, `print_r`, `phpinfo` | Aviso |
| Credenciales en el código | Claves privadas y tokens de proveedores; contraseñas asignadas a mano | Crítico / aviso |
| Repositorio | `config.json`, `.env` o `install.token` versionados en git | Crítico |
| Configuración | `debug` activo, clave de sesión corta, cookies sin `secure`, CORS con `*`, cachés apagadas | Aviso; crítico con `--production` |
| Entorno del servidor | Solo con `--production`: versión de PHP, extensiones, permisos de escritura, dependencias, frontend compilado, conexión a la base de datos y migraciones pendientes | Crítico |
| Archivos expuestos en la web | Con `--url=https://...` (o `build.base_url` y `--production`): pide al sitio `.env`, `.git/config`, `config.json`, `composer.*`, `vendor/`, `logs/`, `versaCLI`, `node_modules/` y archivos de `app/routes`; ninguno debe responder 200 | Crítico |

La configuración local no es la del servidor, por eso sin `--production` sus problemas son solo avisos.

Un hallazgo intencional se silencia en el propio código:

```php
$ruta = 'C:\\xampp\\php\\cacert.pem'; // deploy-check:ignore

// deploy-check:ignore-start
...
// deploy-check:ignore-end
```

Desde el código PHP puedes lanzar un comando con `versaWYS\kernel\cli\CommandExecutor::run()` o, sin esperar el resultado, con `runInBackground()`.
