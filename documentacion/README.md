# Documentación de versaWYS-PHP

versaWYS-PHP es un framework PHP para aplicaciones de gestión multiempresa: panel con usuarios, perfiles y menús listo, API JSON, Vue en el frontend y una línea de comandos que genera módulos completos. El núcleo es el paquete [`versawys/kernel`](https://github.com/kriollo/versawys-kernel); este repositorio es el esqueleto de un proyecto (el panel y tu `app/`).

## Ruta de aprendizaje

Cada paso usa lo del anterior. Al terminar el 5 tienes un módulo propio, probado y en producción.

**1. Instalar y entender el proyecto**

- [Instalación](./INSTALL.md): `composer create-project`, el asistente de `/install` y hosting sin terminal (cPanel).
- [Estructura de directorios](./EstructuraDeDirectorios.md): qué va en `app/`, `versaWYS/`, `src/` y `public/`.
- [Configuración](./CONFIGURATION.md): `config.json`, el modo debug y los ajustes desde el panel.

**2. Tu primer módulo**

```bash
versaCLI versaMODULE:vue:Productos   # migración, modelo, validación, controlador, rutas, prueba y factory
versaCLI migrate:up
composer test                        # la prueba del módulo pasa recién generada
```

- [Línea de comandos](./LineaDeComandos.md): todos los generadores y `versaCLI` sin escribir `php`.
- [Enrutamiento](./Enrutamiento.md): rutas, controladores, middlewares y la página `/doc`.
- [ORM y base de datos](./ORM_y_BaseDeDatos.md): modelos, migraciones y seeders.

**3. Reglas de negocio**

- [Validación](./Validacion.md): reglas y Form Requests (422 con los errores por campo).
- [Autorización](./Autorizacion.md): cada empresa ve solo lo suyo (`Tenant`) y permisos por acción (`Gate`).
- [Contenedor](./Contenedor.md): inyección por constructor y reemplazar un servicio en una prueba.

**4. Probar y depurar**

- [Pruebas](./Pruebas.md): `TestClient`, `TestDatabase` y `Factory`, sin servidor y sin tocar tu base.
- [Profiler](./Profiler.md): tiempos, SQL, N+1 y excepciones de cada petición.
- [Logging](./Logging.md): un archivo por módulo y día, con los secretos ocultos.

**5. Producción**

- [Seguridad](./Seguridad_Principios_Basicos.md): CSRF, XSS, SQL, CSP, secretos cifrados (`Crypt`) y enlaces firmados.
- [Caché, colas y archivos](./Cache_Colas_Archivos.md): `Cache`, `Queue` y `Storage`.
- [Tareas programadas](./Tareas_Programadas.md): `app/schedule.php` y una sola línea en el crontab.
- [Instalación → Producción](./INSTALL.md#producción): OPcache, `app:check:deploy`, `app:deploy:finish` y el [modo mantenimiento](./LineaDeComandos.md#modo-mantenimiento).

## Referencia

- [Redis (`RedisModel`)](./RedisModel.md)
- [Solución de problemas](./Troubleshooting.md) y [preguntas frecuentes](./FAQ.md)
- [Guía de contribución](./Guia_Contribucion.md)
- Kernel: `API.md` (qué es estable y qué interno), `CHANGELOG.md` y `UPGRADE.md`, en `versaWYS/vendor/versawys/kernel/` o en [su repositorio](https://github.com/kriollo/versawys-kernel).

## Licencia

[MIT](https://opensource.org/licenses/MIT).
