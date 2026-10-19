# Profiler: qué hizo cada petición

Con `build.debug: true`, cada página muestra abajo a la derecha una barra con el resumen de la petición:

```
200 · 18.3 ms · 10 SQL (15.4 ms) · 2 MB · TablerosController::ver   ⚠ N+1 ×1
```

Un clic abre el detalle en **`/_profiler/<id>`**, y **`/_profiler`** lista las últimas 100 peticiones, incluidas las de la API y las del SPA, que no muestran barra.

## Qué muestra

| Sección | Contenido |
|---|---|
| Resumen | Estado HTTP, tiempo total y de base de datos, memoria máxima, controlador |
| Petición | Método, URL con su query, cabeceras y cuerpo (JSON, formulario o texto); de los archivos subidos, nombre, tipo y tamaño |
| Respuesta | Estado, cabeceras y cuerpo |
| Tiempos | Cada middleware y el controlador, en milisegundos |
| Consultas | El SQL de cada consulta, cuántos parámetros llevó y su duración; las que fallaron, en rojo y con su error |
| Posible N+1 | La misma consulta repetida 5 veces o más en una petición: suele resolverse con un `JOIN`, un `WHERE id IN (...)` o cargando la relación una vez |
| Excepciones | Clase, mensaje, archivo y línea |
| Caché | Aciertos, fallos y escrituras de `Cache` |
| Sesión | Los nombres de las claves |
| Cola | Los trabajos encolados con `Queue::push` y su cola |

## Repetir una petición

En el detalle, **«Repetir en el probador»** abre el probador de [`/doc`](./Enrutamiento.md#el-probador-de-rutas) con esa petición cargada: método, URL, cabeceras y cuerpo. Puedes cambiar lo que quieras y enviarla otra vez, como en el inspector de ngrok.

- Va con **tu** sesión y tu token CSRF, no con los de quien hizo la petición original.
- Los valores ocultos (`[oculto]`) hay que completarlos antes de enviar.
- Los archivos no se guardan: el probador pide elegirlos de nuevo.
- Un formulario se reenvía como JSON con los mismos campos; el kernel lee los dos igual.
- No se repiten las cabeceras de la conexión ni las que pone el navegador (`Cookie`, `Host`, `Origin`, `Referer`, `User-Agent`, `Sec-*`).

## Seguridad

- **Solo en desarrollo.** Sin `build.debug`, el profiler no corre: no mide, no guarda y `/_profiler` responde 404.
- **Acceso protegido.** El panel solo responde a peticiones desde la propia máquina (`127.0.0.1`, `::1`). Para verlo desde otra, por ejemplo una máquina virtual o un contenedor, agrega su IP en `profiler.allowed_ips`.
- **Sin datos sensibles.** Nunca se guardan:
  - las cabeceras `Cookie`, `Authorization` ni el token CSRF; de `Set-Cookie`, solo el nombre de la cookie;
  - los campos que parecen secretos, en la query, el cuerpo y la respuesta JSON: `password`, `token`, `secret`, `api_key`… (el mismo criterio que el [log](./Logging.md#lo-que-nunca-queda-en-el-log));
  - los valores de las consultas (el SQL queda con sus `?`);
  - los valores de la sesión (solo sus claves);
  - los datos de los trabajos (solo la clase);
  - el contenido de los archivos subidos y de las respuestas binarias.

  Cada cuerpo se guarda hasta 64 KB. Otros datos personales del cuerpo (un RUT, un nombre) sí quedan: el profiler es para tu máquina de desarrollo, no para producción.

  Los registros quedan en `versaWYS/cache/profiler/`, uno por petición, y se guardan los últimos 100.

## Configuración

```json
"profiler": { "enabled": true, "allowed_ips": [] }
```

`enabled: false` lo apaga aunque `build.debug` esté activo.

Las consultas las entrega VersaORM 2.1 o posterior (`VersaORM::onQuery`).
