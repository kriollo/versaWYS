# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/); versionado [SemVer](https://semver.org/lang/es/).
Los cambios del núcleo están en el CHANGELOG de `versawys/kernel`.

## [3.18.1] - 2026-11-23

### Corregido
- Se quita `finfo_close()` de `MediaController`: no hace nada desde PHP 8.1 y en 8.5 avisa que está obsoleto.

### Cambiado
- `versawys/kernel` 3.16.1 (sin llamadas obsoletas en PHP 8.5), `twig/twig` 3.31, `carthage-software/mago` 1.61 y el resto de los paquetes al día. PHPUnit queda en 12.5.33 porque Pest 4.7.8 no admite una versión mayor.

## [3.18.0] - 2026-11-20

### Corregido
- `versawys/kernel` 3.16.0: **`min` y `max` de un campo `int`, `integer` o `numeric` comparan el valor**, no el largo del texto (`int|min:1` ya no acepta `0` ni `-5`).
- `customTableSimple`: la tabla se vuelve a cargar cuando cambian sus columnas (el `watch` miraba una copia, no la prop).

### Seguridad
- `versawys/kernel` 3.16.0: **una sesión que apunta a otra empresa termina** (solo el superadministrador opera en una empresa que no es la de su cuenta). El latido de sesión (`sessionHeartbeat`) aplica la misma regla.

## [3.17.0] - 2026-11-19

### Agregado
- `versawys/kernel` 3.15.0: **`app/errors.php`** recibe cada excepción no controlada (un closure con la `Throwable`, como `app/schedule.php`), para llevarla a incidencias o avisos. Ver `UPGRADE.md` del kernel.

### Corregido
- `versawys/kernel` 3.15.0: en producción las excepciones no controladas ahora quedan en el log (antes solo se registraban en debug y se perdían sin rastro).

### Cambiado
- `versawys/kernel` ^3.15.0.

## [3.16.0] - 2026-11-16

### Agregado
- `versawys/kernel` 3.14.0: **`php versaCLI serve` atiende varias peticiones a la vez** (uno por núcleo, hasta 8; `--workers=N` para elegir). Con un solo proceso, una descarga larga (un video que se reproduce) dejaba en fila a todo lo demás y la página se trababa.

### Corregido
- `versawys/kernel` 3.14.0: el servidor de desarrollo sirve audio y video por rangos (`206`), así el navegador puede saltar dentro de un video o una canción; y ya no entrega archivos de fuera del proyecto a través de un enlace simbólico.

### Cambiado
- `versawys/kernel` ^3.14.0.

## [3.15.3] - 2026-11-10

### Corregido
- `versawys/kernel` 3.13.7: un deploy ya no puede dejar en línea una plantilla Twig vieja. Con la caché compartida entre releases, una petición que todavía atendía el release anterior podía volver a compilar la versión vieja justo después de `app:deploy:finish`; ahora cada release compila en su propia subcarpeta.

### Cambiado
- `versawys/kernel` ^3.13.7.

## [3.15.2] - 2026-11-09

### Corregido
- **Los avatares por defecto no llegaban al servidor.** El `.gitignore` ignora `public/dashboard/img/avatars/*` (las fotos que suben los usuarios), y en un proyecto creado con `composer create-project` eso dejaba fuera de git también `1.png`, `1.jpg` e `incognito-icon-20.jpg`: desplegado desde git (versaDeploy), el panel mostraba el avatar roto. Ahora esos tres van en git.
- `versawys/kernel` 3.13.6: `app:deploy:finish` ya no falla en el primer despliegue con «Sin permiso de escritura: versaWYS/cache/twig/.cache» cuando la caché compartida está vacía.

### Documentación
- Desplegar en **FrankenPHP** (sin PHP-FPM) con versaDeploy: su ejemplo `deploy.versawys-frankenphp.yml` (PostgreSQL, Redis, cron con `app:schedule:run` y `app:deploy:finish`), probado de punta a punta desde `composer create-project`.

### Cambiado
- `versawys/kernel` ^3.13.6.

## [3.15.1] - 2026-11-05

### Corregido
- `versaWYS/rector.php` ya no convierte `[Clase::class, 'metodo']` en `Clase::metodo(...)`. Ese es el formato de las rutas y los middlewares del router: con un middleware estático, la conversión dejaba la ruta rota y `app:check:deploy` la marcaba como crítica. Se detectó al llevar las herramientas del esqueleto a los proyectos.

## [3.15.0] - 2026-11-05

### Agregado
- **Menú lateral con secciones plegables** (traído de versaABA). Cada sección se pliega con un botón y recuerda su estado en el navegador; la sección de la página actual se abre sola. Con el menú contraído se ven todos los ítems, y al expandirlo vuelve el estado guardado. El hover ya no desplaza el ítem contraído, que recortaba la flecha del submenú.
- `php versaCLI app:user:load --count=N`: usuarios de carga, con su CSV de credenciales, para la prueba de capacidad (`versaSecurity load`). `--delete` los borra.
- Pruebas de seguridad con las vulnerabilidades de 2026 de Laravel, Symfony y WordPress contra el servidor real.

### Rendimiento
- `versawys/kernel` 3.13.5 y VersaORM 2.2.5: una petición que no consulta la base ya no se conecta. Con PostgreSQL local, `/up` baja de ~11,5 ms a ~1,1 ms y el login de ~14 ms a ~2,2 ms.
- Kernel 3.13.4: la sesión se valida con una consulta en vez de dos, Twig se crea recién al renderizar y `/up` no abre sesión.

### Corregido
- Restaurar un respaldo de `pg_dump` 17 o posterior en PostgreSQL 16 o anterior.
- Un comando `app:` inexistente es un error (sale con código 1).

### Cambiado
- `versawys/kernel` ^3.13.5 y VersaORM 2.2.5.
- Rector 2.8 aplicado al código de `app/` y sin avisos de mago lint. Plantillas Twig con las correcciones de twigcs de una línea.
- Ejemplos de Apache y nginx al día con el kernel: solo se ejecuta `index.php`.
- Dependencias de desarrollo al día (frontend y PHP).

## [3.14.0] - 2026-10-29

- VersaORM 2.2.4, versacompiler 2.7.9, symfony/mime 7.4.22 (CVE-2026-45070), menú lateral con scroll y restricciones al día.
