# versaWYS-PHP

**Framework PHP moderno, ligero y robusto para desarrollo ágil de aplicaciones web y APIs.**

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/kriollo/versaWYS)
[![Status](https://img.shields.io/badge/status-stable-brightgreen.svg)](#)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg)](#)
[![Packagist](https://img.shields.io/packagist/v/versawys/kernel.svg?label=kernel)](https://packagist.org/packages/versawys/kernel)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](#)

## Inicio rápido

Con una base de datos MySQL o PostgreSQL vacía:

```bash
composer create-project versawys/versawys mi-proyecto
cd mi-proyecto && pnpm install && pnpm compile
php versaCLI app:install && php versaCLI serve
```

Detalles en la [Guía de Instalación](./documentacion/INSTALL.md).

## ¿Qué ofrece versaWYS-PHP?

- **Arquitectura MVC** clara y flexible para proyectos de cualquier tamaño.
- **Enrutamiento avanzado** con soporte para agrupación de rutas, middlewares y parámetros dinámicos.
- **ORM integrado (VersaORM)** compatible con MySQL y PostgreSQL, para trabajar con bases de datos de forma sencilla y segura.
- **Sistema de plantillas Twig** para vistas elegantes y seguras.
- **CLI potente (versaCLI)** para generación de código, migraciones, seeders y automatización de tareas.
- **Seguridad integrada**: protección CSRF, XSS, control de sesiones y middlewares personalizables.
- **Frontend moderno** listo para Vue.js, TailwindCSS y assets compilados automáticamente.
- **Estructura modular** y documentación completa para crecer y mantener tu proyecto fácilmente.
- **Curva de aprendizaje suave**: ideal para quienes buscan productividad sin sacrificar buenas prácticas.

## Documentación

Toda la documentación detallada se encuentra en la carpeta [`/documentacion`](./documentacion/). Te recomendamos comenzar por:

- [Guía de Inicio Rápido](./documentacion/README.md): visión general y primeros pasos.
- [Guía de Instalación](./documentacion/INSTALL.md): cómo instalar y configurar el framework.
- [Guía de Configuración](./documentacion/CONFIGURATION.md): personalización y ajustes del entorno.
- [Enrutamiento](./documentacion/Enrutamiento.md): cómo funcionan las rutas y middlewares.
- [ORM y Base de Datos](./documentacion/ORM_y_BaseDeDatos.md): modelos con VersaORM, migraciones y seeders.
- [Interfaz de Línea de Comandos (CLI)](./documentacion/LineaDeComandos.md): automatización y utilidades.
- [Validación de datos](./documentacion/Validacion.md): reglas de `validateParams`.
- [Seguridad](./documentacion/Seguridad_Principios_Basicos.md): buenas prácticas y protección.
- [Estructura de Directorios](./documentacion/EstructuraDeDirectorios.md): organización del proyecto.
- [Preguntas Frecuentes (FAQ)](./documentacion/FAQ.md)
- [Solución de Problemas (Troubleshooting)](./documentacion/Troubleshooting.md)

## Estructura del Proyecto

- `app/`: código de tu aplicación (controladores, modelos, rutas, middlewares, migraciones, seeders, plantillas).
- `public/`: archivos públicos y assets (CSS, JS, imágenes).
- `src/`: código fuente frontend (Vue.js, estilos, scripts modernos).
- `versaWYS/`: `composer.json`, dependencias (`vendor/`), configuración, caché, logs y pruebas. El núcleo del framework es el paquete [`versawys/kernel`](https://github.com/kriollo/versawys-kernel) y se actualiza con `composer update versawys/kernel`.
- `documentacion/`: toda la documentación oficial.
- `versaCLI`: script CLI para generación de código, migraciones, seeders, etc.

Consulta la [Guía de Estructura de Directorios](./documentacion/EstructuraDeDirectorios.md) para más detalles.

## Contribuciones

¿Quieres ayudar a mejorar versaWYS-PHP? Lee la [Guía de Contribución](./documentacion/Guia_Contribucion.md) para saber cómo reportar bugs, sugerir mejoras o enviar código.

## Recursos útiles

- [Preguntas Frecuentes (FAQ)](./documentacion/FAQ.md)
- [Solución de Problemas (Troubleshooting)](./documentacion/Troubleshooting.md)
- [Repositorio oficial en GitHub](https://github.com/kriollo/versaWYS)

## Licencia

Este proyecto está bajo la [Licencia MIT](https://opensource.org/licenses/MIT).
