# versaWYS-PHP 0.9.8 - Guía Principal

Bienvenido a la documentación de versaWYS-PHP, un framework PHP versátil, simple, ligero, rápido y fácil de usar.

Esta guía te proporciona una visión general del framework y enlaces a las guías detalladas para cada tema. Usa este archivo como índice principal y navega a las secciones específicas según tus necesidades.

## Tabla de Contenidos

- [Visión General](#visión-general)
- [Guías Detalladas](#guías-detalladas)
- [Licencia](#licencia)

## Visión General

versaWYS-PHP es un framework PHP MVC (Modelo-Vista-Controlador) moderno y ligero, diseñado para desarrolladores que buscan una base sólida y eficiente para construir aplicaciones web y APIs RESTful. Su filosofía se centra en la simplicidad, la velocidad y la facilidad de uso, permitiendo un desarrollo ágil sin sacrificar la potencia ni la flexibilidad.

Inspirado en la necesidad de un arranque rápido de proyectos y una curva de aprendizaje suave, versaWYS-PHP integra componentes robustos y populares de la comunidad PHP, como VersaORM para el acceso a datos y Twig para un sistema de plantillas elegante y seguro.

El núcleo es el paquete [`versawys/kernel`](https://github.com/kriollo/versawys-kernel). Trae:

- pruebas de la aplicación sin servidor;
- un contenedor de dependencias optativo;
- validación con Form Requests y autorización por empresa y por acción;
- caché, una cola de trabajos y almacenamiento de archivos.

Su `ROADMAP.md` resume qué cubre y cuáles son sus límites conocidos.

## Guías Detalladas

- [Guía de Instalación](./INSTALL.md)
- [Guía de Configuración](./CONFIGURATION.md)
- [Enrutamiento](./Enrutamiento.md)
- [ORM y Base de Datos (VersaORM, Migraciones y Seeders)](./ORM_y_BaseDeDatos.md)
- [Interfaz de Línea de Comandos (CLI)](./LineaDeComandos.md)
- [Seguridad del Framework](./Seguridad_Principios_Basicos.md)
- [Pruebas de la aplicación (TestClient)](./Pruebas.md)
- [Contenedor de dependencias](./Contenedor.md)
- [Validación de datos](./Validacion.md)
- [Autorización](./Autorizacion.md)
- [Caché, colas y archivos](./Cache_Colas_Archivos.md)
- [Profiler: qué hizo cada petición](./Profiler.md)
- [Documentación interactiva de rutas (`/doc`)](./Enrutamiento.md#la-página-doc)
- [Logging](./Logging_Quickstart.md) y [referencia completa](./Logging.md)
- [Redis (RedisModel)](./RedisModel.md)
- [Solución de problemas](./Troubleshooting.md)
- [Estructura de Directorios](./EstructuraDeDirectorios.md)
- [Guía de Contribución](./Guia_Contribucion.md)
- [Preguntas Frecuentes (FAQ)](./FAQ.md)

## Licencia

versaWYS-PHP es un software de código abierto licenciado bajo la [Licencia MIT](https://opensource.org/licenses/MIT).
