# ORM y Base de Datos en versaWYS-PHP

Esta guía explica cómo versaWYS-PHP gestiona la interacción con la base de datos usando RedBeanPHP como ORM, y cómo puedes crear y ejecutar migraciones y seeders para mantener tu esquema y tus datos de forma estructurada y reproducible.

---

## 1. Configuración de la Conexión

La conexión a la base de datos se configura en el archivo `versaWYS/kernel/config/config.json`, en la sección `DB`:

```json
"DB": {
    "DB_HOST": "localhost",
    "DB_USER": "usuario",
    "DB_PASS": "contraseña",
    "DB_NAME": "nombre_bd"
}
```

El framework utiliza la clase `versaWYS\kernel\RedBeanCnn` para gestionar la conexión y cierre de la base de datos. No necesitas configuraciones adicionales: la conexión se establece automáticamente al usar los modelos.

---

## 2. Modelos y Operaciones Básicas con RedBeanPHP

Los modelos en `app/models/` extienden `RedBeanCnn` y encapsulan la lógica de acceso a datos. RedBeanPHP permite trabajar con la base de datos de forma orientada a objetos, sin escribir SQL para operaciones CRUD básicas.

### Ejemplo básico de uso en un modelo:

```php
// Crear un nuevo usuario
$user = R::dispense('users');
$user->name = 'Juan';
$user->email = 'juan@ejemplo.com';
R::store($user); // Guarda el usuario

// Buscar un usuario
$user = R::findOne('users', 'email = ?', ['juan@ejemplo.com']);

// Actualizar
$user->name = 'Juan Pérez';
R::store($user);

// Eliminar
R::trash($user);
```

Puedes ver ejemplos reales en los modelos `app/models/Users.php`, `Modules.php`, `Perfil.php`, etc.

---

## 3. Migraciones: Control de Esquema

Las migraciones te permiten versionar y modificar el esquema de la base de datos de forma controlada y reproducible.

### Crear una migración

Usa versaCLI para generar una migración:

```bash
php versaCLI migrate:make:NombreDeLaMigracion
```

Esto crea un archivo en `app/migrations/` con métodos `up()` y `down()` para aplicar y revertir cambios.

### Ejecutar migraciones

```bash
php versaCLI migrate:up
```

Esto aplicará todas las migraciones pendientes.

### Ejemplo de migración

```php
class m20230515100000_CreateProductsTable {
    public static function up() {
        R::exec("CREATE TABLE products (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255))");
    }
    public static function down() {
        R::exec("DROP TABLE IF EXISTS products");
    }
}
```

Si quieres borrar todo y volver a ejecutar las migraciones, puedes usar:

```bash
php versaCLI migrate:refresh
```

#### para más información consulta la documentación de [versaCLI](./LineaDeComandos.md)

---

## 4. Seeders: Poblado de Datos

Los seeders permiten poblar la base de datos con datos iniciales o de prueba.

### Crear un seeder

```bash
php versaCLI seeder:make:NombreDelSeeder
```

Esto crea un archivo en `app/seeders/` con un método `run()`.

### Ejecutar seeders

- Ejecutar todos:
  ```bash
  php versaCLI seeder:runAll
  ```
- Ejecutar uno específico:
  ```bash
  php versaCLI seeder:run:NombreDelSeeder
  ```

### Ejemplo de seeder

```php
class ProductsSeeder {
    public static function run() {
        $product = R::dispense('products');
        $product->name = 'Laptop';
        R::store($product);
    }
}
```

***Nota***: Los seeders son útiles para poblar datos de prueba o iniciales. Puedes crear múltiples seeders para diferentes tablas o conjuntos de datos.

Si necesitar poblar datos de prueba, puedes usar `php versaCLI migrate:refresh:seeder`, que revertirá todas las migraciones y ejecutará todos los seeders.

#### para más información consulta la documentación de [versaCLI](./LineaDeComandos.md)

---

## 5. Relaciones y Consultas Avanzadas

RedBeanPHP permite definir relaciones entre tablas usando propiedades como `ownXList` (uno a muchos) y `sharedXList` (muchos a muchos). Para consultas complejas, puedes usar `R::getAll()` con SQL directo.

### Ejemplo de relación uno a muchos

```php
$author = R::dispense('author');
$book = R::dispense('book');
$author->ownBookList[] = $book;
R::store($author);
```

### Ejemplo de consulta avanzada

```php
$results = R::getAll('SELECT * FROM users WHERE status = ?', ['active']);
```

---

## 6. Buenas Prácticas

- Usa migraciones para cualquier cambio de esquema.
- Usa seeders para poblar datos de prueba o iniciales.
- Encapsula la lógica de acceso a datos en modelos dentro de `app/models/`.
- Para operaciones CRUD simples, usa las funciones ORM de RedBeanPHP. Para consultas complejas, puedes usar SQL directo con `R::getAll()`.

---

¿Dudas? Consulta la documentación de RedBeanPHP o revisa los modelos y migraciones de ejemplo en tu proyecto.
