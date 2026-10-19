# ORM y Base de Datos

versaWYS usa VersaORM (paquete Composer `versaorm/versaorm-php`) y funciona con MySQL/MariaDB y PostgreSQL; las pruebas usan SQLite (ver [Pruebas](./Pruebas.md)). La conexión se define en la sección `database` de `config.json`.

## Modelos

Los modelos viven en `app/models/` y extienden `versaWYS\kernel\model\VersaORMModel`. Se crean con:

```bash
php versaCLI model:make:Producto
```

```php
namespace app\models;

use VersaORM\VersaModel;
use versaWYS\kernel\model\VersaORMModel;

class Producto extends VersaORMModel
{
    protected string $table = 'productos';

    public function activos(): array
    {
        return $this->getAll('SELECT * FROM ' . $this->table . ' WHERE estado = ?', [true]);
    }

    public function porId(int $id): ?array
    {
        $producto = $this->findOne($this->table, $id);

        return $producto instanceof VersaModel ? $producto->export() : null;
    }

    public function crear(array $datos): int
    {
        $producto = $this->dispense($this->table);
        $producto->nombre = $datos['nombre'];
        $producto->precio = $datos['precio'];
        $producto->created_at = date('Y-m-d H:i:s');

        return $producto->store();
    }
}
```

Operaciones habituales dentro de un modelo:

| Operación | Código |
|---|---|
| Registro nuevo | `$this->dispense($tabla)` y luego `->store()` |
| Cargar por id o por campo | `$this->load($tabla, $valor)` / `$this->load($tabla, $valor, 'campo')` |
| Buscar uno (o `null`) | `$this->findOne($tabla, $valor, 'campo')` |
| SQL con parámetros | `$this->getAll($sql, $params)`, `$this->getRow($sql, $params)`, `$this->exec($sql, $params)` |
| Query builder | `$this->qb()->where('estado', '=', true)->limit(10)->getAll()` |
| Contar / actualizar / borrar | `$this->qb()->where(...)->count()`, `->update([...])`, `->delete()` |

**Usa siempre parámetros (`?`) para los valores que vienen del usuario**; nunca los concatenes en el SQL.

Si tu código debe correr en ambos motores, evita sintaxis exclusiva de uno: usa `CONCAT()` en lugar de `||`, y no dependas de `ILIKE`, `RETURNING` ni de conversiones `::tipo`.

## Migraciones

```bash
php versaCLI migrate:make:productos   # crea app/migrations/mAAAAMMDDHHMMSS_productos.php
php versaCLI migrate:up               # ejecuta las pendientes
php versaCLI migrate:rollback         # revierte la última
php versaCLI migrate:down:nombre      # revierte una en particular
php versaCLI migrate:refresh          # revierte todo y vuelve a ejecutar (pide confirmación)
php versaCLI migrate:refresh:seeder   # lo mismo y además ejecuta los seeders
php versaCLI migrate:dropAll          # elimina todas las tablas (pide confirmación)
```

Los comandos destructivos piden confirmación; agrega `--force` para omitirla en scripts de despliegue.

```php
namespace app\migrations;

use Exception;
use VersaORM\Schema\Blueprint;
use versaWYS\kernel\model\VersaORMModel;

class m20260101120000_productos extends VersaORMModel
{
    public function up(): array
    {
        try {
            $this->orm()->schemaBuilder()->create('productos', static function (Blueprint $table): void {
                $table->id();
                $table->foreignId('id_empresa');
                $table->string('nombre', 255);
                $table->integer('precio')->default(0);
                $table->boolean('estado')->default(true);
                $table->timestamps();

                $table->foreign('id_empresa')->references('id')->on('empresas');
            }, true);

            return ['message' => 'Tabla productos creada.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }

    public function down(): array
    {
        try {
            $this->orm()->schemaBuilder()->dropIfExists('productos');

            return ['message' => 'Tabla productos eliminada.', 'success' => true];
        } catch (Exception $exception) {
            return ['message' => $exception->getMessage(), 'success' => false];
        }
    }
}
```

> Declara las columnas que apuntan a otra tabla con `foreignId()`. `id()` crea un `BIGINT UNSIGNED` en MySQL, y una columna `integer()` no puede referenciarlo.

Las migraciones ejecutadas se registran en la tabla `versa_migrations`.

### Caché del esquema en producción

Antes de validar o guardar un modelo, el ORM lee las columnas de su tabla (`information_schema` en PostgreSQL y MySQL, `PRAGMA` en SQLite). Con `build.debug` en `false`, esa lectura queda en `Cache` (archivos o Redis): se hace una vez y no en cada petición.

- **Se invalida sola:** `migrate:up`, `migrate:rollback` y cualquier `CREATE`, `ALTER` o `DROP` ejecutado con el ORM.
- **Cambios hechos a mano:** si se cambia una tabla a mano (con `psql`, un cliente gráfico, etc.), el cambio se ve en un día como máximo. Para verlo al instante: `php versaCLI cache:clear`.
- **En desarrollo** (`build.debug: true`) no se guarda nada: cada petición lee el esquema actual.

## Seeders

```bash
php versaCLI seeder:make:Productos
php versaCLI seeder:run:Productos
php versaCLI seeder:runAll            # en orden natural de nombre
```

Un seeder es una clase en `app/seeders/` con un método `run()` que devuelve `['message' => ..., 'success' => bool]`. Usa un prefijo ordenable (`S202601_...`) cuando un seeder dependa de otro.

## Multiempresa

El starter es multiempresa: `versa_users`, `versa_perfil` y los módulos se asocian a una fila de `empresas`. `Tenant::id()` es la empresa de la sesión: pásala al modelo y filtra cada consulta por ella, nunca por un id de empresa que envíe el cliente. Al buscar por id, `Tenant::ensure($fila)` responde 404 si la fila es de otra empresa.

El módulo que genera `versaCLI versaMODULE:vue:Productos` ya viene así: su modelo recibe `$idEmpresa` en cada método y su prueba comprueba que una empresa no ve los registros de otra.
