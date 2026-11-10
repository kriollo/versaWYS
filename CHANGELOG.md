# Changelog

Formato basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/); versionado [SemVer](https://semver.org/lang/es/).
Los cambios del núcleo están en el CHANGELOG de `versawys/kernel`.

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
