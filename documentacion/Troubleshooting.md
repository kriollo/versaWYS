# Troubleshooting (Solución de Problemas) - versaWYS-PHP

Esta guía te ayudará a identificar y resolver los problemas más comunes al trabajar con versaWYS-PHP. Si tu problema no está aquí, revisa la [FAQ](./FAQ.md) o abre un issue en el repositorio oficial.

---

## 1. El archivo de configuración `config.json` no existe o da error
**Síntoma:**
- Mensaje: `Advertencia: El archivo de configuración (...) no existe.`
- Error al ejecutar comandos de la CLI.

**Solución:**
- Ejecuta: `php versaCLI config:init` para crear el archivo.
- Verifica permisos de escritura en la carpeta `versaWYS/kernel/config/`.

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
- Ejecuta `npm run watch` para recompilar automáticamente.
- Borra la caché del navegador.
- Verifica que los archivos generados estén en `public/` y que el HTML los esté cargando correctamente.

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
- Asegúrate de incluir el campo CSRF en todos los formularios:
  - En PHP: `<?php echo versaWYS\kernel\helpers\Functions::csrf_field(); ?>`
  - En Twig: `{{ csrf_field() | raw }}`
- Si usas AJAX, envía el token como parte de los datos o en una cabecera personalizada.

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
- Borra la caché de Twig si usas plantillas (`php versaCLI config:ClearCache`).
- Reinicia el servidor de desarrollo.
- Verifica que no haya errores de sintaxis en el JSON.

---

## 10. Otros problemas
- Consulta la [FAQ](./FAQ.md) para dudas comunes.
- Revisa la documentación de cada sección.
- Si el problema persiste, abre un issue en el [repositorio oficial](https://github.com/kriollo/versaWYS).

---

¿Tienes un problema no documentado? ¡Sugiere una nueva entrada para el troubleshooting!
