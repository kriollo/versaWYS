# Guía de Contribución a versaWYS-PHP

¡Gracias por tu interés en contribuir a versaWYS-PHP! Este proyecto es de código abierto y tu ayuda es bienvenida, ya sea reportando errores, sugiriendo mejoras, corrigiendo documentación o enviando código.

---

## 1. ¿Cómo puedo contribuir?

Puedes contribuir de varias formas:
- **Reportando errores**: Si encuentras un bug, por favor repórtalo en el repositorio oficial de GitHub con pasos claros para reproducirlo.
- **Sugerencias y mejoras**: Si tienes ideas para nuevas funcionalidades o mejoras, abre un issue o discútelo en el foro/comunidad.
- **Mejorando la documentación**: Puedes corregir, ampliar o traducir la documentación en la carpeta `documentacion/`.
- **Enviando código (Pull Requests)**: Puedes proponer cambios en el código fuente siguiendo los pasos de abajo.

---

## 2. Flujo para contribuir código

1. **Haz un fork del repositorio** en GitHub y clona tu fork en tu máquina:
    ```bash
    git clone https://github.com/kriollo/versaWYS.git
    cd versaWYS
    ```
2. **Crea una rama para tu cambio**:
    ```bash
    git checkout -b mi-feature-o-fix
    ```
3. **Haz tus cambios** en el código o la documentación. Sigue la estructura del proyecto y los estándares de codificación.
4. **Prueba tu cambio**. Usa los comandos de versaCLI para generar, migrar, o testear según corresponda.
5. **Haz commit y push** de tus cambios:
    ```bash
    git add .
    git commit -m "Descripción clara del cambio"
    git push origin mi-feature-o-fix
    ```
6. **Abre un Pull Request** desde tu rama en GitHub hacia la rama principal del proyecto.
7. **Espera revisión**. El equipo de mantenimiento revisará tu PR y podrá pedirte cambios o aprobarlo.

---

## 3. Buenas prácticas

- Escribe descripciones claras en tus commits y Pull Requests.
- Si tu cambio afecta la documentación, actualízala en la carpeta `documentacion/`.
- Si agregas comandos a versaCLI, documenta su uso en `Guia_versaCLI.md`.
- Mantén el código limpio y sigue la estructura de carpetas del framework.
- Si agregas migraciones o seeders, usa los comandos de versaCLI para generarlos.
- Prueba tu código antes de enviar el PR.

---

## 4. Recursos útiles

- [Repositorio oficial en GitHub](https://github.com/kriollo/versaWYS)
- [Documentación del framework](../documentacion/)
- [Guía de comandos versaCLI](Guia_versaCLI.md)
- [RedBeanPHP ORM](https://redbeanphp.com/index.php?p=/learn)

---

¡Tu aporte es valioso! Si tienes dudas, abre un issue o pregunta en la comunidad.
