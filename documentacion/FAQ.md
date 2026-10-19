# Preguntas Frecuentes (FAQ) - versaWYS-PHP

## ¿Qué versión de PHP necesito?
**PHP 8.3 o superior**, con las extensiones `pdo` (y el driver de tu base), `mbstring`, `intl`, `openssl`, `fileinfo`, `curl`, `zip`, `xml` y `dom`. `versaCLI status` dice cuáles faltan y cómo instalarlas; el asistente de `/install` no avanza sin ellas.

## ¿Cómo creo el archivo de configuración inicial?
Ejecuta en la terminal:
```bash
php versaCLI config:init
```
Esto genera `versaWYS/config/config.json` con una clave de sesión propia. En un hosting sin terminal no hace falta: el primer arranque lo crea solo y lleva al asistente de `/install` (ver [Instalación](./INSTALL.md)).

## ¿Cómo ejecuto migraciones y seeders?
Utiliza la [Interfaz de Línea de Comandos (CLI)](./LineaDeComandos.md):
- Para migraciones:
  ```bash
  php versaCLI migrate:up
  ```
- Para seeders:
  ```bash
  php versaCLI seeder:runAll
  ```

## ¿Dónde defino mis rutas?
En la carpeta `app/routes/`. Puedes crear archivos de rutas personalizados y organizarlos por módulos.

## ¿Cómo compilo los assets (CSS/JS)?
Instala las dependencias con `pnpm install` y luego:
```bash
pnpm watch      # desarrollo: recompila al guardar
pnpm compile    # producción
```
No mezcles los dos modos en la misma carpeta `public/` (ver [Solución de problemas](./Troubleshooting.md#4-cambios-en-archivos-jscss-no-se-ven-reflejados)).

## ¿Cómo inicio el servidor de desarrollo?
Ejecuta:
```bash
php versaCLI serve
```
Por defecto queda en [http://localhost:8000](http://localhost:8000); otro puerto con `--port=8080`.

## ¿Cómo agrego una pantalla nueva?
Lo más rápido es el módulo completo, que trae migración, modelo, validación, controlador, rutas y su prueba:
```bash
php versaCLI versaMODULE:vue:Productos
php versaCLI migrate:up
```
Para una pieza suelta: `controller:make:Nombre`, `model:make:Nombre`, `middleware:make:Nombre`, `request:make:Nombre`.
Consulta la [guía de comandos](./LineaDeComandos.md) para más opciones.

## ¿Cómo reporto un bug o contribuyo al framework?
Lee la [Guía de Contribución](./Guia_Contribucion.md) y abre un issue o pull request en el [repositorio oficial](https://github.com/kriollo/versaWYS).

## ¿Dónde encuentro ejemplos de código?
Revisa la carpeta `app/` (modelos, controladores, rutas) y los ejemplos en la documentación de cada sección.

## ¿Qué hago si tengo problemas con la base de datos?
- Verifica tu configuración en `config.json`.
- Asegúrate de que la base de datos esté creada y accesible.
- Consulta la [guía de ORM y Base de Datos](./ORM_y_BaseDeDatos.md).

---

¿Tienes otra pregunta? ¡Sugiere una nueva entrada para la FAQ!

## ¿Cómo creo un usuario o recupero la clave del administrador sin entrar al panel?

Con `php versaCLI app:user:create`, `app:user:list` y `app:user:password <correo>`. Ver [Usuarios desde la terminal](./LineaDeComandos.md#usuarios-desde-la-terminal).
