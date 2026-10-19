# Troubleshooting (Solución de Problemas) - versaWYS-PHP

Esta guía te ayudará a identificar y resolver los problemas más comunes al trabajar con versaWYS-PHP. Si tu problema no está aquí, revisa la [FAQ](./FAQ.md) o abre un issue en el repositorio oficial.

---

## 1. El archivo de configuración `config.json` no existe o da error
**Síntoma:**
- Mensaje: `Advertencia: El archivo de configuración (...) no existe.`
- Error al ejecutar comandos de la CLI.

**Solución:**
- Ejecuta: `php versaCLI config:init` para crear el archivo. Desde el navegador, el primer arranque lo crea solo si la carpeta tiene permiso de escritura.
- Verifica permisos de escritura en la carpeta `versaWYS/config/` (`versaCLI status` lo revisa).

---

## 2. Error de conexión a la base de datos
**Síntoma:**
- Mensaje: `Could not connect to database` o similar.

**Solución:**
- Revisa los datos en `config.json` (host, usuario, contraseña, nombre de la base).
- Asegúrate de que el servidor de base de datos esté activo y accesible.
- Verifica que el usuario tenga permisos suficientes.

---

## 3. Migraciones o seeders no se ejecutan
**Síntoma:**
- No se crean tablas o no se insertan datos.

**Solución:**
- Asegúrate de haber ejecutado los comandos correctos:
  - `php versaCLI migrate:up`
  - `php versaCLI seeder:runAll`
- Verifica que los archivos de migración/seeder tengan los métodos `up()`, `down()` o `run()` correctamente definidos.
- Revisa la consola por mensajes de error de sintaxis o permisos.

---

## 4. Cambios en archivos JS/CSS no se ven reflejados
**Síntoma:**
- Modificas archivos en `src/` pero no cambian en el navegador.

**Solución:**
- Ejecuta `pnpm watch` para recompilar automáticamente (modo desarrollo: Vue muestra todos sus avisos).
- Borra la caché del navegador.
- Verifica que los archivos generados estén en `public/` y que el HTML los esté cargando correctamente.

**`v-model` o `provide/inject` dejan de funcionar sin un error claro:** hay dos copias de Vue en la página. Una parte de los archivos se compiló para producción (`pnpm compile` usa `vue.esm-browser.prod.js`) y otra para desarrollo (`vue.esm-browser.js`).

- Para dejar todo en un modo, corre `pnpm compileDev` (desarrollo) o `pnpm compile` (producción).
- Desde versacompiler 2.7.4, `pnpm watch` recompila al iniciar lo que quedó en el otro modo.
- Con un archivo compilado, `grep -rho "vue[a-z.-]*esm-browser[a-z.]*js" public/dashboard/js | sort | uniq -c` debe mostrar una sola variante.

---

## 5. Error 404 en rutas que deberían existir
**Síntoma:**
- Accedes a una URL válida y recibes un error 404.

**Solución:**
- Verifica que la ruta esté definida en los archivos de `app/routes/`.
- Revisa la sintaxis y el método HTTP (GET, POST, etc.).
- Si usas Apache/NGINX, asegúrate de que las reglas de reescritura (`.htaccess` o config de NGINX) estén correctamente configuradas.

---

## 6. Problemas con sesiones o cookies
**Síntoma:**
- El login no persiste, o las cookies no se guardan.

**Solución:**
- Revisa la configuración de sesiones y cookies en `config.json`.
- Verifica que el dominio y los flags `secure` y `http_only` sean correctos para tu entorno.
- Asegúrate de que el navegador no esté bloqueando cookies.

---

## 7. Problemas con CSRF o formularios
**Síntoma:**
- Error de token CSRF inválido o formulario que no se envía.

**Solución:**
- POST, PUT, PATCH y DELETE exigen el token (ver [Enrutamiento](./Enrutamiento.md#rutas)).
- En un formulario Twig: `{{ csrf_field()|raw }}`.
- Desde JavaScript, usa `versaFetch`, que lo envía solo.
- Para un webhook de un tercero, que no puede enviarlo: `->withoutCsrf()` en la ruta, y valida su firma en el controlador.

---

## 8. Problemas con dependencias de Node.js o Composer
**Síntoma:**
- Errores al ejecutar `npm install` o `composer install`.

**Solución:**
- Asegúrate de tener la versión correcta de Node.js y PHP.
- Borra la carpeta `node_modules/` o `vendor/` y ejecuta de nuevo la instalación.
- Revisa los mensajes de error para dependencias incompatibles.

---

## 9. Cambios en la configuración no se aplican
**Síntoma:**
- Modificas `config.json` pero la app no refleja los cambios.

**Solución:**
- `config.json` se lee en cada petición: si el cambio no se ve, casi siempre es un error de sintaxis en el JSON. Revísalo con `php -r 'json_decode(file_get_contents("versaWYS/config/config.json"), flags: JSON_THROW_ON_ERROR);'`.
- Si el cambio afecta plantillas, borra la caché de Twig: `php versaCLI config:ClearCache`.

---

## 10. Nadie recuerda la clave del administrador

Desde la terminal del servidor:

```bash
php versaCLI app:user:list                                   # para ver el correo
php versaCLI app:user:password admin@empresa.cl --activar    # clave nueva; --activar si la cuenta quedó inactiva
```

Si el login sigue bloqueado por intentos fallidos:

- espera `login_attempt.time` segundos;
- o corre `php versaCLI cache:clear`, que borra el conteo por IP del rate limiting.

Ver [Línea de comandos](./LineaDeComandos.md#usuarios-desde-la-terminal).

---

## 11. Un error muestra una página roja con código

Es la página de error del modo debug (`build.debug: true`). Muestra el mensaje, el código alrededor de la línea que falló en cada archivo de la aplicación y los datos de la petición. Las API reciben lo mismo en JSON, con `code`.

En producción (`php versaCLI config:debug:false`) el usuario ve un error 500 genérico y el detalle queda en `versaWYS/logs/`.

---

## 12. El sitio responde 503 «En mantenimiento»

Alguien corrió `versaCLI app:down`. `versaCLI app:up` lo termina. Ver [Modo mantenimiento](./LineaDeComandos.md#modo-mantenimiento).

---

## 13. Las tareas programadas no corren

`versaCLI app:schedule:list` dice si el crontab está llamando al programador y, si no, imprime la línea exacta que hay que agregar, con la ruta completa de PHP. La salida de cada tarea queda en `versaWYS/logs/cron.log`. Ver [Tareas programadas](./Tareas_Programadas.md).

---

## 14. Otros problemas
- Consulta la [FAQ](./FAQ.md) para dudas comunes.
- Revisa la documentación de cada sección.
- Si el problema persiste, abre un issue en el [repositorio oficial](https://github.com/kriollo/versaWYS).

---

¿Tienes un problema no documentado? ¡Sugiere una nueva entrada para el troubleshooting!
