# Preguntas Frecuentes (FAQ) - versaWYS-PHP

## ¿Qué versión de PHP necesito?
Debes usar **PHP 8.3 o superior**. Además, asegúrate de tener la extensión `php-intl` instalada.

## ¿Cómo creo el archivo de configuración inicial?
Ejecuta en la terminal:
```bash
php versaCLI config:init
```
Esto generará el archivo `versaWYS/kernel/config/config.json` necesario para que el framework funcione.

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
Instala las dependencias con `npm install` y luego ejecuta:
```bash
npm run watch
```
o consulta los scripts disponibles en tu `package.json`.

## ¿Cómo inicio el servidor de desarrollo?
Ejecuta:
```bash
php versaCLI serve
```
Por defecto, tu app estará disponible en [http://localhost:3000](http://localhost:3000).

## ¿Cómo agrego un nuevo controlador, modelo o middleware?
Usa la CLI:
```bash
php versaCLI controller:make:Nombre
php versaCLI model:make:Nombre
php versaCLI middleware:make:Nombre
```
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
