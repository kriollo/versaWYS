# Guía Detallada de la Interfaz de Línea de Comandos (versaCLI)

La herramienta `versaCLI` es la interfaz de línea de comandos oficial de versaWYS-PHP. Permite automatizar y agilizar tareas comunes de desarrollo, como la creación de controladores, modelos, rutas, migraciones, seeders, manejo de configuración, y más.

---

## ¿Cómo iniciar?

Antes de usar cualquier comando, asegúrate de tener el archivo de configuración creado. Si es tu primer uso, ejecuta:

```bash
php versaCLI config:init
```

Esto generará el archivo `versaWYS/kernel/config/config.json` necesario para el funcionamiento del framework y de la CLI. Para más detalles sobre comandos, consulta la [Guía de Línea de Comandos (CLI)](./Guia_versaCLI.md).

---

## Ejecución básica

Para usar la CLI, ejecuta en la terminal desde la raíz del proyecto:

```bash
php versaCLI [comando]
```

Puedes ver la ayuda general y todos los comandos disponibles ejecutando:

```bash
php versaCLI help
```

Consulta la [Guía de Línea de Comandos (CLI)](./Guia_versaCLI.md) para ejemplos y descripciones detalladas de cada comando.

---

## Comandos principales

### Configuración

- `config:init`
  Crea el archivo de configuración base si no existe. Más información en la [Guía de Configuración](./CONFIGURATION.md).
- `config:debug:[true/false]`
  Activa o desactiva el modo debug.
- `config:templateCache:[true/false]`
  Activa o desactiva el cache de plantillas Twig.
- `config:ClearCache`
  Limpia el cache de plantillas Twig.

### Migraciones y Seeders

- `migrate:make:[nombre]`
  Crea una nueva migración. Consulta la [sección de migraciones](./ORM_y_BaseDeDatos.md#migraciones-control-de-esquema).
- `migrate:up`
  Ejecuta las migraciones pendientes.
- `migrate:down:[nombre]`
  Revierte una migración específica.
- `migrate:rollback`
  Revierte la última migración.
- `migrate:refresh`
  Revierte todas las migraciones y las vuelve a ejecutar.
- `migrate:refresh:seeder`
  Igual que el anterior, pero ejecuta todos los seeders.
- `seeder:make:[nombre]`
  Crea un nuevo seeder. Consulta la [sección de seeders](./ORM_y_BaseDeDatos.md#seeders-poblado-de-datos).
- `seeder:runAll`
  Ejecuta todos los seeders.
- `seeder:run:[nombre]`
  Ejecuta un seeder específico.

### Generadores de código

- `controller:make:[nombre]`
  Crea un nuevo controlador.
- `controller:delete:[nombre]`
  Elimina un controlador.
- `model:make:[nombre]`
  Crea un nuevo modelo.
- `model:delete:[nombre]`
  Elimina un modelo.
- `middleware:make:[nombre]`
  Crea un nuevo middleware.
- `middleware:delete:[nombre]`
  Elimina un middleware.
- `route:make:[nombre]`
  Crea un nuevo archivo de rutas.
- `route:delete:[nombre]`
  Elimina un archivo de rutas.
- `route:list`
  Lista todas las rutas registradas.

### Frontend

- `front:makeTwig:[nombre]`
  Crea un nuevo template Twig.
- `front:makeVue:[nombre]`
  Crea un nuevo componente Vue.

### Atajos y módulos

- `RCMD:[nombre]`
  Crea ruta, controlador, modelo y middleware con un solo comando.
- `versaMODULE:twig:[nombre]`
  Crea un módulo completo con Twig.
- `versaMODULE:vue:[nombre]`
  Crea un módulo completo con Vue.

### Servidor de desarrollo

- `serve`
  Inicia el servidor de desarrollo integrado de PHP.

---

## Ejemplos prácticos

- Crear un controlador:
  ```bash
  php versaCLI controller:make:MiControlador
  ```
- Crear un modelo:
  ```bash
  php versaCLI model:make:MiModelo
  ```
- Crear una migración:
  ```bash
  php versaCLI migrate:make:crear_tabla_usuarios
  ```
- Ejecutar migraciones:
  ```bash
  php versaCLI migrate:up
  ```
- Crear un módulo completo con Vue:
  ```bash
  php versaCLI versaMODULE:vue:MiModulo
  ```

---

## Extender versaCLI

Puedes crear tus propios comandos extendiendo las clases en `versaWYS/kernel/cli/`. Consulta la documentación interna y los ejemplos de las clases como `CommandLineInterface`, `ControllerManager`, `ModelManager`, etc. Para más detalles, revisa la [Guía de Contribución](./Guia_Contribucion.md).

---

## Notas adicionales

- Si el archivo de configuración no existe, versaCLI te preguntará si deseas crearlo automáticamente.
- Todos los comandos están diseñados para facilitar el flujo de trabajo y evitar tareas repetitivas.
- Puedes combinar comandos para generar módulos completos de forma rápida.

- La CLI está diseñada para ser intuitiva y fácil de usar, pero siempre puedes consultar la ayuda con `php versaCLI help` para obtener más información sobre cada comando.
- Asegúrate de tener los permisos adecuados para ejecutar los comandos, especialmente en entornos de producción.

¿Quieres ejemplos prácticos de uso de algún comando específico? ¡Dímelo! También puedes consultar la [Guía de Línea de Comandos (CLI)](./Guia_versaCLI.md) para más ejemplos.
