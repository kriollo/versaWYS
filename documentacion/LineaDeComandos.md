# Interfaz de Línea de Comandos (versaCLI)

`versaCLI` se ejecuta desde la raíz del proyecto (o desde cualquier directorio indicando su ruta, por ejemplo en un cron):

```bash
php versaCLI <módulo>:<acción>[:<parámetro>] [opciones]
php versaCLI help          # lista completa
php versaCLI migrate       # ayuda de un módulo
```

### Sin escribir `php`

- **Sin instalar nada:** `./versaCLI help` funciona porque el archivo es ejecutable. Si al clonar se perdió el permiso, corre una vez `chmod +x versaCLI`.
- **Instalación global, una vez por máquina:**

  ```bash
  cp versaWYS/vendor/bin/versaCLI ~/.local/bin/versaCLI   # ~/.local/bin debe estar en el PATH
  ```

  Después, `versaCLI <comando>` funciona en cualquier carpeta de cualquier proyecto versaWYS, también en una subcarpeta como `app/controllers`. Busca hacia arriba la carpeta del proyecto y ejecuta su propio `versaCLI`, así que cada proyecto usa su versión del kernel.

Los ejemplos de esta guía usan `php versaCLI`, que funciona en todos los sistemas, también en Windows.

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
| `cache:clear` | Limpia las cachés de Twig, rate limiting y la de la aplicación (`Cache`). |
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
| `RCMD:<nombre>` | Módulo completo sin frontend (ver abajo). |
| `versaMODULE:twig\|vue:<nombre>` | Módulo completo más la vista Twig o el componente Vue. |
| `request:make:<nombre>` | `app/requests/<Nombre>.php`: una [Form Request](./Validacion.md#form-requests). |
| `job:make:<nombre>` | `app/jobs/<Nombre>.php`: un trabajo para la [cola](./Cache_Colas_Archivos.md#colas-queue). |
| `test:make:<nombre>` | `versaWYS/tests/<Nombre>Test.php`: una prueba con `TestClient` y `TestDatabase`. |
| `factory:make:<nombre>` | `versaWYS/tests/factories/<Nombre>.php`: los datos de prueba de una tabla ([Factory](./Pruebas.md#datos-de-prueba-factory)). |
| `cron:make:<nombre>` | `app/cron/<Nombre>.php`: una tarea para `app/schedule.php`. |

### El módulo completo (`versaMODULE` y `RCMD`)

`php versaCLI versaMODULE:vue:OrdenesCompra` crea un CRUD **aislado por empresa desde el inicio**:

| Archivo | Qué trae |
|---|---|
| `app/migrations/m…_OrdenesCompra.php` | Tabla `ordenes_compra` con `id_empresa` (clave foránea a `empresas`), `nombre` y fechas |
| `app/models/OrdenesCompra.php` | `listar`, `buscar`, `crear`, `actualizar` y `eliminar`, todos filtrando por empresa |
| `app/requests/OrdenesCompraRequest.php` | Las reglas de validación (422 con los errores por campo) |
| `app/controllers/OrdenesCompraController.php` | La pantalla y la API; recibe el modelo por el contenedor y usa `Tenant::id()` |
| `app/routes/OrdenesCompraRoutes.php` | `GET/POST /admin/ordenescompra/api/ordenescompra` y `GET/PUT/DELETE …/{id}`, documentadas para `/doc` |
| `versaWYS/tests/OrdenesCompraTest.php` | CRUD, validación, otra empresa sin acceso e id inválido: pasa recién generada |
| `versaWYS/tests/factories/OrdenesCompra.php` | Los datos de prueba de la tabla (`Factory::create('ordenes_compra')`) |
| `src/dashboard/js/ordenescompra/dashOrdenesCompra.vue` | La pantalla, que lista los registros (solo `versaMODULE`) |

Luego: `php versaCLI migrate:up`, agrega la pantalla al menú en *Módulos* y `composer test`. La pantalla queda protegida con `checkSession` y `onlyUserWithAccess`, y la API con `checkSession`: cada registro se filtra por la empresa del usuario en el modelo.

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

Las tareas se programan en `app/schedule.php` y el crontab del servidor tiene una sola línea. Ver [Tareas programadas](./Tareas_Programadas.md).

```cron
* * * * * /usr/bin/php /var/www/mi-proyecto/current/versaCLI app:schedule:run >> /var/www/mi-proyecto/current/versaWYS/logs/cron.log 2>&1
```

| Comando | Qué hace |
|---|---|
| `app:schedule:run` | Lanza las tareas de este minuto, cada una en su proceso (va en el crontab) |
| `app:schedule:list` | Cada tarea con su id, frecuencia, próxima ejecución y resultado de la última |
| `app:schedule:exec <id>` | Ejecuta una tarea ahora, para probarla |

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
| `app:check:psr4` | Lo que funciona en Windows, que no distingue mayúsculas, y falla en Linux: clases cuyo namespace o archivo no calza con su mapeo PSR-4, clases del proyecto usadas (`use`, `new`, `Clase::`, `extends`, `implements`) con otras mayúsculas o que no existen, y `require`/`include` cuya ruta difiere en mayúsculas de lo que hay en disco. Que los métodos llamados existan lo revisan phpstan y mago (`composer check`). |
| `app:check:deploy` | Revisión previa al despliegue. Ver más abajo. |
| `app:install` | Instala la aplicación desde la terminal: base de datos, migraciones, empresa y superadministrador. |
| `app:user:list [--empresa=ID]` | Lista los usuarios con rol, perfil, empresa, estado y último ingreso. Nunca muestra contraseñas. |
| `app:user:create` | Crea un usuario del panel. Ver «Usuarios desde la terminal». |
| `app:user:password <correo> [--activar]` | Cambia la contraseña de un usuario; `--activar` además reactiva la cuenta. |
| `app:queue:work [cola] [--stop-when-empty]` | Procesa la cola de trabajos. Sin la opción no termina (systemd o supervisor); con ella, la vacía y termina (cron). Ver [Caché, colas y archivos](./Cache_Colas_Archivos.md). |
| `app:queue:failed` | Lista los trabajos que agotaron sus intentos, con su último error. |
| `app:queue:retry <id\|all>` | Vuelve a encolar un trabajo fallido, o todos. |

### Usuarios desde la terminal

Sirve para recuperar el acceso cuando nadie recuerda la clave del administrador, o para crear usuarios en un servidor:

```bash
php versaCLI app:user:list
php versaCLI app:user:password admin@empresa.cl            # pide la clave nueva dos veces, sin mostrarla
php versaCLI app:user:password admin@empresa.cl --activar  # si además la cuenta quedó inactiva
php versaCLI app:user:create                               # pregunta nombre, correo, rol, empresa, perfil y clave
php versaCLI app:user:create --name="Ana" --email=ana@empresa.cl --role=admin --empresa=1 --perfil=1
```

- **Política:** la clave nueva cumple la misma política que en el panel (`auth.password_policy`) y vence según `auth.expiration_days_password`.
- **Recuperación pendiente:** cambiar la clave también anula un enlace de recuperación que estuviera pendiente.
- **Roles y perfiles:** el rol es `user`, `admin` o `superadmin`. El perfil por defecto es 1 (Personalizado) para administradores y 2 (Usuario) para el resto.
- **En scripts:** `--password=...` evita la pregunta, pero la clave queda en el historial de la terminal. Mejor escríbela cuando la pida.

### Despliegue y archivos base

| Comando | Qué hace |
|---|---|
| `app:deploy:finish` | El único hook `post_deploy` que necesita versaDeploy: `config:merge` de `versaWYS/config.compartida.json` (si existe), `migrate:up`, `cache:twig`, activa la caché de plantillas, `command:refresh` y `app:check:deploy --production`. Se detiene en el primer paso que falle. |
| `app:kernel:publish [--force]` | Crea `index.php`, `versaCLI`, los `.htaccess` y los ejemplos de `configs/` que falten, con la versión del kernel instalado. Muestra el diff de los que difieren (`-` proyecto, `+` kernel); `--force` los reemplaza. |

### Modo mantenimiento

Para una migración larga o una restauración: el sitio responde 503 (la API en JSON) y el equipo sigue entrando.

```bash
versaCLI app:down --message="Volvemos a las 10:00" --retry=300 --secret=equipo-2026
# Para entrar igual: abre una vez https://tu-sitio/equipo-2026 (queda una cookie por 12 horas)
versaCLI app:up
```

| Opción | Qué hace |
|---|---|
| `--message` | El texto que ve el visitante. |
| `--retry` | Segundos para la cabecera `Retry-After`; la página se recarga sola en ese tiempo. Por defecto 60. |
| `--secret` | La ruta de entrada para el equipo. Sin ella se genera una al azar y el comando la imprime. |

Todas las rutas responden 503 mientras dure, también `/up`: así el monitoreo lo registra. Quien entró con la clave ve el panel normal, con una franja amarilla que le recuerda que para los demás el sitio está cerrado. El estado queda en `down.json`, junto a `config.json`, y sobrevive a `cache:clear`.

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
