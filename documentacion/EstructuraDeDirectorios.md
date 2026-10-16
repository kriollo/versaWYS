# Estructura de directorios

La raíz web es la carpeta del proyecto: `index.php` recibe todas las peticiones. El `.htaccess` de la raíz (y `configs/nginx.conf.example` para nginx) bloquea lo que no debe servirse: `app/`, `versaWYS/`, `.env`, `.git`, `*.json`, `*.twig`, `versaCLI`. `app:check:deploy --url=https://tu-sitio` comprueba en el sitio real que sigan bloqueados.

```
mi-proyecto/
├── index.php            entrada web: versaWYS\kernel\App::run(__DIR__)
├── versaCLI             (la línea de comandos, ver LineaDeComandos.md)
├── .htaccess            reglas de Apache; configs/ trae el ejemplo de nginx
├── app/                 tu aplicación (abajo)
├── src/                 frontend fuente: Vue, TypeScript, CSS de Tailwind
├── public/              frontend compilado, imágenes y archivos públicos
├── documentacion/       estas guías
├── versaWYS/            Composer, configuración, logs, caché y pruebas (abajo)
├── composer.json        dependencias de PHP (vendor en versaWYS/vendor)
└── package.json         dependencias y scripts del frontend (pnpm)
```

## `app/`

| Carpeta | Contiene | Se crea con |
|---|---|---|
| `routes/` | Un archivo de rutas por módulo, `ProductosRoutes.php`. El kernel carga todos. | `route:make:Productos` |
| `controllers/` | `ProductosController.php`: recibe la petición y devuelve un arreglo (JSON) o HTML. | `controller:make:Productos` |
| `models/` | `Productos.php`, que extiende `versaWYS\kernel\model\VersaORMModel`. | `model:make:Productos` |
| `requests/` | Form Requests: las reglas de validación de una acción. | `request:make:GuardarProducto` |
| `middleware/` | Filtros que corren antes del controlador. `AuthMiddleware` trae los del panel. | `middleware:make:Nombre` |
| `migrations/` | Cambios del esquema: `m20261016120000_Productos.php`, con `up()` y `down()`. | `migrate:make:Productos` |
| `seeders/` | Datos iniciales. | `seeder:make:Nombre` |
| `templates/` | Plantillas Twig: `dashboard/` (panel), `mail/` (correos), `install/`. Un archivo en `templates/versaWYS/` reemplaza la plantilla del kernel del mismo nombre. | `front:makeTwig:Nombre` |
| `services/` | Clases de la aplicación que no son modelos ni controladores (correo, respaldos). | — |
| `commands/` | Comandos propios: `versaCLI app:<nombre>`. | `command:make:<nombre>` |
| `cron/` | Las tareas que corre el programador. | `cron:make:Nombre` |
| `jobs/` | Trabajos de la cola (`Queue::push`). | `job:make:Nombre` |
| `schedule.php` | Cuándo corre cada tarea programada. | — |
| `container.php` | Servicios del contenedor y permisos de `Gate`. | — |

`versaMODULE:vue:Productos` crea de una vez la migración, el modelo, la Form Request, el controlador, las rutas, la prueba y la factory.

## `versaWYS/`

| Carpeta | Contiene | ¿En git? |
|---|---|---|
| `config/` | `config.json` (claves y base de datos), `installed.lock` y, durante `app:down`, `down.json`. Los proyectos anteriores a 2.1 la tienen en `kernel/config/`, que sigue funcionando. | No |
| `vendor/` | Dependencias de Composer. El kernel está en `vendor/versawys/kernel`: no se edita, se actualiza con `composer update versawys/kernel`. | No |
| `logs/` | Un archivo por módulo y día (ver [Logging](./Logging.md)) y `cron.log`. | No |
| `cache/` | Plantillas Twig compiladas y los datos de `Cache` (`cache/data/`). Se puede borrar: `versaCLI cache:clear`. | No |
| `storage/` | Archivos privados de `Storage`, que la web no sirve. | No |
| `tests/` | Las pruebas (Pest) y sus factories en `tests/factories/`. | Sí |

## `src/` y `public/`

`src/dashboard/js/` tiene los módulos Vue del panel y `src/dashboard/css/dashboard.css` el CSS de Tailwind. [versaCompiler](https://github.com/kriollo/versaCompiler) los compila en `public/dashboard/` (`pnpm watch` al desarrollar, `pnpm compile` para producción). `public/` no se edita a mano, salvo imágenes y archivos estáticos propios.
