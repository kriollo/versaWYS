# Guía Detallada del ORM (RedBeanPHP) y Base de Datos en versaWYS-PHP

## ORM y Base de Datos en versaWYS-PHP

versaWYS-PHP utiliza RedBeanPHP como ORM (Object-Relational Mapper) para facilitar la interacción con la base de datos. RedBeanPHP es conocido por su simplicidad y su capacidad de "cero configuración", ya que puede crear y alterar el esquema de la base de datos sobre la marcha durante el desarrollo.

## Configuración de la Conexión (`versaWYS\kernel\RedBeanCnn`)

La gestión de la conexión a la base de datos se centraliza en la clase `versaWYS\kernel\RedBeanCnn`.

### Puntos Clave:

1.  **Obtención de Credenciales**:
    *   La clase `RedBeanCnn` lee la configuración de la base de datos (host, usuario, contraseña, nombre de la base de datos) de la variable global `$config`, específicamente de la sección `$config['DB']`. Esta configuración se define típicamente en tu archivo `config.json`.

2.  **Establecimiento de la Conexión (`connet()` method)**:
    *   Utiliza `RedBeanPHP\R::setup()` para conectar con una base de datos MySQL.
    *   La cadena de conexión se construye dinámicamente con los datos obtenidos del archivo de configuración: `mysql:host=DB_HOST;dbname=DB_NAME`.
    *   Verifica si ya existe una conexión (`R::$currentDB == null`) para evitar reconfiguraciones innecesarias.
    *   Habilita la opción `PDO::MYSQL_ATTR_LOCAL_INFILE`, que permite la carga masiva de datos con sentencias como `LOAD DATA LOCAL INFILE`.

3.  **Conjunto de Características de RedBeanPHP**:
    *   Se configura RedBeanPHP para usar el conjunto de características `'novice/latest'` mediante `R::useFeatureSet('novice/latest')`. Esto influye en cómo RedBeanPHP maneja ciertas operaciones y convenciones.

4.  **Modo Congelado (`R::freeze()`):**
    *   **Muy importante**: El método `connet()` llama a `R::freeze()`. En RedBeanPHP, "congelar" el esquema significa que RedBeanPHP ya no intentará alterar la estructura de la base de datos (como crear nuevas tablas o añadir columnas) automáticamente. Esto es crucial para entornos de producción para prevenir cambios accidentales en el esquema que podrían llevar a la pérdida de datos o inconsistencias.
    *   Durante el desarrollo, es común tener el esquema "descongelado" para que RedBeanPHP pueda adaptarse a los cambios en tus modelos. Para producción, siempre debe estar congelado.

5.  **Cierre de la Conexión (`closeDB()` method)**:
    *   Proporciona un método `closeDB()` que simplemente invoca a `R::close()` para cerrar la conexión activa con la base de datos.

6.  **Escapado de Datos Personalizado (`scape($e)` method)**:
    *   La clase incluye un método `scape($e)` para escapar valores. Este método maneja `null`, números (enteros y flotantes), y cadenas de texto, reemplazando caracteres especiales como `\`, `\x00`, `\n`, `\r`, `'`, `"`, `\x1a`.
    *   **Nota Importante**: RedBeanPHP generalmente se encarga de su propio escapado y parametrización de consultas cuando utilizas sus funciones ORM (por ejemplo, `R::store()`, `R::find()`, `R::dispense()`, `R::exec()`). El uso de un método de escapado manual como este debe hacerse con precaución y, por lo general, solo sería necesario en escenarios donde se construyen fragmentos de SQL de forma manual o para otros fines de sanitización muy específicos. Para las operaciones CRUD estándar, confía en las capacidades de RedBeanPHP para prevenir inyecciones SQL.

### Ejemplo de Configuración en `config.json`:

Tu archivo `config.json` debería tener una sección similar a esta:

```json
{
    // ... otras configuraciones ...
    "DB": {
        "DB_HOST": "localhost",
        "DB_USER": "tu_usuario_db",
        "DB_PASS": "tu_contraseña_db",
        "DB_NAME": "tu_nombre_db"
    }
    // ... otras configuraciones ...
}

## Modelos (Beans) en RedBeanPHP

En RedBeanPHP, los objetos que representan filas en las tablas de la base de datos se conocen como "beans". Un bean es simplemente un objeto PHP que RedBeanPHP puede almacenar y recuperar de la base de datos.

### Conceptos Clave de los Beans:

1.  **Creación de Beans (`R::dispense()`):**
    *   Para crear un nuevo bean (que representa una nueva fila potencial en una tabla), utilizas `R::dispense('tipo_de_bean')`.
    *   El `'tipo_de_bean'` es una cadena que RedBeanPHP usará para determinar el nombre de la tabla. Por convención, si dispensas un bean de tipo `'user'`, RedBeanPHP buscará o creará una tabla llamada `user`.
    *   Ejemplo: `$user = R::dispense('user');`

2.  **Propiedades del Bean:**
    *   Puedes asignar propiedades al bean como si fuera un objeto PHP estándar.
    *   Ejemplo: `$user->name = 'John Doe'; $user->email = 'john.doe@example.com';`
    *   Cuando almacenas el bean, estas propiedades se convertirán en columnas en la tabla correspondiente. Si el esquema no está congelado (`R::freeze(false)`), RedBeanPHP intentará crear estas columnas si no existen.

3.  **Almacenamiento de Beans (`R::store()`):**
    *   Para guardar un bean (ya sea nuevo o modificado) en la base de datos, utilizas `R::store($bean)`.
    *   RedBeanPHP se encarga de determinar si debe hacer un `INSERT` (para beans nuevos) o un `UPDATE` (para beans existentes que ya tienen un ID).
    *   Ejemplo: `$id = R::store($user);`
    *   `R::store()` devuelve el ID del bean guardado.

4.  **Carga de Beans (`R::load()` y `R::loadAll()`):**
    *   Para cargar un bean específico por su ID, usas `R::load('tipo_de_bean', $id)`.
    *   Ejemplo: `$user = R::load('user', 1);`
    *   Para cargar múltiples beans por sus IDs, usas `R::loadAll('tipo_de_bean', [$id1, $id2, ...])`.

5.  **Búsqueda de Beans (`R::find()`, `R::findOne()`, `R::findAll()`):**
    *   `R::find('tipo_de_bean', 'condicion SQL WHERE', [$valores])`: Busca múltiples beans que coincidan con la condición.
        *   Ejemplo: `$activeUsers = R::find('user', 'status = ?', ['active']);`
    *   `R::findOne('tipo_de_bean', 'condicion SQL WHERE', [$valores])`: Similar a `find`, pero solo devuelve el primer bean encontrado o `null`.
        *   Ejemplo: `$admin = R::findOne('user', 'role = ? AND email = ?', ['admin', 'admin@example.com']);`
    *   `R::findAll('tipo_de_bean', 'condicion SQL ORDER BY, LIMIT, etc.')`: Similar a `find` pero más orientado a consultas generales con cláusulas `ORDER BY`, `LIMIT`, etc., sin placeholders directos para condiciones `WHERE` en el segundo argumento (se construyen como parte de la cadena SQL).

6.  **Eliminación de Beans (`R::trash()`):**
    *   Para eliminar un bean de la base de datos, usas `R::trash($bean)`.
    *   Ejemplo: `R::trash($user);`

7.  **Modelos Personalizados (FUSE - Fuse Is Simple Extension):**
    *   RedBeanPHP permite extender la funcionalidad de los beans mediante clases de modelo personalizadas. Si tienes una tabla llamada `product`, puedes crear una clase `Model_Product extends RedBean_SimpleModel` (o la clase base que estés usando, como `versaWYS\kernel\RedBeanCnn` si esta extiende `SimpleModel`).
    *   Cuando RedBeanPHP dispensa o carga un bean de tipo `'product'`, automáticamente lo asociará con tu clase `Model_Product` si existe. Esto te permite añadir métodos personalizados, lógica de validación, hooks (como `update()`, `open()`, `delete()`, `dispense()`) a tus beans.
    *   En tu estructura, parece que los archivos en `app/models/` (como `Users.php`, `Modules.php`) podrían ser estos modelos personalizados. Examinaremos esto a continuación.

### Convenciones de Nomenclatura:

*   **Tablas**: Los nombres de las tablas son generalmente en minúsculas y coinciden con el tipo de bean dispensado (ej. `R::dispense('product')` -> tabla `product`).
*   **Columnas**: Los nombres de las columnas coinciden con las propiedades asignadas a los beans (ej. `$product->product_name = '...';` -> columna `product_name`).
*   **Claves Primarias**: Por defecto, RedBeanPHP usa una columna `id` como clave primaria autoincremental.
*   **Relaciones**: RedBeanPHP puede manejar relaciones (uno a uno, uno a muchos, muchos a muchos) mediante convenciones de nomenclatura y el uso de listas de beans (ej. `$user->ownProductList[] = $product;`).

## Ejemplo Práctico: La Clase `app\models\Users`

Para ilustrar cómo se utiliza RedBeanPHP en versaWYS-PHP, examinemos la clase `app\models\Users.php`. Esta clase actúa como una capa de servicio o repositorio para la entidad de usuarios, encapsulando la lógica de acceso a datos.

### Estructura y Conexión

*   La clase `Users` extiende `versaWYS\kernel\RedBeanCnn`.
*   El constructor (`__construct()`) llama a `$this->connet()`, estableciendo una conexión a la base de datos al instanciar un objeto `Users`.
*   El destructor (`__destruct()`) llama a `$this->closeDB()`, cerrando la conexión cuando el objeto se destruye.
*   La tabla de base de datos asociada principalmente con esta clase es `versausers` (se usa `R::dispense('versausers')`, `R::findOne('versausers', ...)`).

### Operaciones Comunes con RedBeanPHP (Ejemplos de `Users.php`)

1.  **Crear un Nuevo Usuario (`create($params)` method):**
    ```php
    // Dentro de app\models\Users.php
    public function create($params): int|string
    {
        $userBean = R::dispense('versausers'); // Crear un nuevo bean para la tabla 'versausers'
        $userBean->tokenid = Functions::generateCSRFToken();
        $userBean->name = $params['name'];
        $userBean->email = $params['email'];
        $userBean->password = $params['password']; // Asumiendo que ya está hasheada
        $userBean->expiration_pass = $params['expiration_pass'];
        $userBean->last_login = date('Y-m-d H:i:s');
        $userBean->role = $params['role'];
        $userBean->status = $params['status'];
        $userBean->id_perfil = $params['perfil'];
        $userBean->created_at = date('Y-m-d H:i:s');
        $userBean->updated_at = date('Y-m-d H:i:s');
        
        $id = R::store($userBean); // Guardar el bean en la base de datos

        if ($id) {
            // Lógica adicional, como asignar un perfil detallado
            $this->setPerfilUser($userBean->id, $params['perfil']);
        }
        return $id;
    }
    ```
    *   `R::dispense('versausers')`: Crea un objeto bean vacío asociado a la tabla `versausers`.
    *   Se asignan propiedades al bean.
    *   `R::store($userBean)`: Inserta el nuevo registro en la base de datos y devuelve su ID.

2.  **Buscar un Usuario (`find($id, $field = 'id')` method):**
    ```php
    // Dentro de app\models\Users.php
    public function find(int|string $id, string $field = 'id'): array
    {
        $userBean = R::findOne('versausers', " $field = ? ", [$id]); // Buscar un bean
        return $userBean ? $userBean->export() : []; // Exportar a array o devolver array vacío
    }

    // Ejemplo de uso (en un controlador, por ejemplo):
    // $usersModel = new Users();
    // $userData = $usersModel->findUserByEmail('test@example.com');
    ```
    *   `R::findOne('versausers', " $field = ? ", [$id])`: Busca un solo bean en la tabla `versausers` donde la columna `$field` coincida con `$id`.
    *   `$userBean->export()`: Convierte las propiedades del bean encontrado en un array asociativo.

3.  **Actualizar un Usuario (`update($params)` method):**
    ```php
    // Dentro de app\models\Users.php
    public function update($params): int|string
    {
        $userBean = R::findOne('versausers', 'tokenid = ?', [$params['tokenid']]);
        if ($userBean) {
            $userBean->name = $params['name'];
            $userBean->password = $params['password']; // Asumiendo hasheada
            // ... otras propiedades ...
            $userBean->updated_at = date('Y-m-d H:i:s');
            $result = R::store($userBean); // Guarda los cambios (UPDATE)
            // ... lógica de setPerfilUser ...
            return $result;
        }
        return 'Usuario no encontrado'; // O manejar de otra forma
    }
    ```
    *   Primero se carga el bean con `R::findOne()`.
    *   Se modifican sus propiedades.
    *   `R::store($userBean)`: Actualiza el registro existente en la base de datos.

4.  **"Eliminar" un Usuario (Soft Delete en `delete($id)` method):**
    ```php
    // Dentro de app\models\Users.php
    public function delete($id): int|string // $id aquí es tokenid
    {
        $userBean = R::findOne('versausers', 'tokenid = ?', [$id]);
        if ($userBean) {
            $userBean->status = $userBean->status === '1' ? '0' : '1'; // Alternar estado
            if ($userBean->status === '1') {
                $userBean->last_login = date('Y-m-d H:i:s');
            }
            return R::store($userBean);
        }
        return 'Usuario no encontrado';
    }
    ```
    *   Este método implementa un "soft delete" cambiando el campo `status` en lugar de eliminar el registro.

5.  **Obtener Múltiples Registros con Paginación (`getUsersPaginate(...)`):**
    ```php
    // Dentro de app\models\Users.php
    public function getUsersPaginate(...): array 
    {
        // ... construcción de $filter, $fields, $order, $limit ...
        $result = R::getAll("SELECT SQL_CALC_FOUND_ROWS $fields \n        FROM versausers vu \n        LEFT JOIN versaperfil vp \n        ON vp.id = vu.id_perfil \n        $filter $order $limit");
        $total = R::getCell('SELECT FOUND_ROWS()');
        return ['total' => $total, 'data' => $result];
    }
    ```
    *   `R::getAll()`: Se utiliza para ejecutar consultas SQL más complejas y obtener múltiples filas como un array de arrays asociativos.
    *   `R::getCell()`: Obtiene el valor de una sola celda de una consulta, útil aquí para `FOUND_ROWS()`.

6.  **Ejecutar SQL Directo (`setPerfilUser($idUser, $idPerfil)`):**
    ```php
    // Dentro de app\models\Users.php
    private function setPerfilUser($idUser, $idPerfil): void
    {
        R::exec('DELETE FROM versaperfildetalleuser WHERE id_user = ?', [$idUser]);
        R::exec(
            'INSERT INTO versaperfildetalleuser (id_user, menu_id, submenu_id) SELECT ?, menu_id, submenu_id FROM versaperfildetalle WHERE perfil_id = ?',
            [$idUser, $idPerfil]
        );
    }
    ```
    *   `R::exec()`: Se usa para ejecutar sentencias SQL que no devuelven un conjunto de resultados directamente (como `INSERT`, `UPDATE`, `DELETE`).

### Puntos a Destacar de `Users.php`:

*   **Encapsulación**: La clase `Users` encapsula la lógica de acceso a datos para los usuarios, haciendo que los controladores sean más limpios.
*   **Uso Directo de `R`**: Se utilizan los métodos estáticos de la clase `R` de RedBeanPHP para todas las operaciones de base de datos.
*   **SQL Personalizado**: Cuando es necesario (ej. joins complejos, operaciones masivas), se recurre a `R::getAll()`, `R::getCell()` o `R::exec()` con SQL escrito directamente.
*   **Manejo de Relaciones**: La relación usuario-perfil se gestiona manualmente mediante `R::exec()` en `setPerfilUser`, interactuando con la tabla de unión `versaperfildetalleuser`.

Este modelo de `Users.php` es un buen ejemplo de cómo se puede estructurar el acceso a datos con RedBeanPHP en una aplicación, combinando las funciones ORM de alto nivel con la flexibilidad del SQL directo cuando se requiere.

## Ejemplo Práctico: La Clase `app\models\Modules`

La clase `app\models\Modules.php` gestiona la lógica de negocio para los módulos y submódulos del menú de la aplicación. Al igual que la clase `Users`, extiende `versaWYS\kernel\RedBeanCnn`.

Interactúa con varias tablas/beans:
*   `'versamenu'`: Para los elementos principales del menú.
*   `'versasubmenu'`: Para los subelementos asociados a un elemento principal del menú.

### Operaciones con Módulos Principales (`versamenu`)

1.  **Crear o Editar un Módulo (`saveModule($data)`):**
    ```php
    // Dentro de app\models\Modules.php
    public function saveModule($data): int|string
    {
        if ($data['action'] === 'edit') {
            $module = R::load('versamenu', $data['id']);
        } else {
            $module = R::dispense('versamenu');
            $module->posicion = $this->getLastPositionBySeccion($data['seccion']);
        }
        $module->seccion = $data['seccion'];
        $module->nombre = $data['nombre'];
        $module->descripcion = $data['descripcion'];
        $module->icono = $this->scape($data['icono']); // Uso del método scape de RedBeanCnn
        $module->fill = $data['fill'];
        $module->estado = $data['estado'];
        $module->url = $data['url'];
        // created_at y updated_at se manejan también
        return R::store($module);
    }
    ```
    *   Carga un bean existente con `R::load()` si es una edición, o crea uno nuevo con `R::dispense()`.
    *   Nótese el uso de `$this->scape()` (heredado de `RedBeanCnn`) para la propiedad `icono`.

2.  **Obtener Todos los Módulos (`all()`):**
    ```php
    // Dentro de app\models\Modules.php
    public function all()
    {
        return R::getAll('SELECT * FROM versamenu');
    }
    ```
    *   Utiliza `R::getAll()` con una consulta SQL directa para obtener todos los módulos.

3.  **Cambiar Estado de un Módulo (`changeStatus($params)`):**
    ```php
    // Dentro de app\models\Modules.php
    public function changeStatus($params): mixed
    {
        $module = R::load('versamenu', $params['id']);
        $module->estado = $params['estado'];
        return R::store($module);
    }
    ```

### Operaciones con Submódulos (`versasubmenu`)

1.  **Crear o Editar un Submódulo (`saveSubModule($data)`):**
    ```php
    // Dentro de app\models\Modules.php
    public function saveSubModule($data): int|string
    {
        if ($data['action'] === 'edit') {
            $module = R::load('versasubmenu', $data['id']);
        } else {
            $module = R::dispense('versasubmenu');
        }
        $module->id_menu = $data['id_menu']; // Clave foránea al módulo principal
        $module->nombre = $data['nombre'];
        $module->descripcion = $data['descripcion'];
        $module->estado = $data['estado'];
        $module->url = $data['url'];
        // created_at y updated_at
        $result = R::store($module);

        $this->toggleSubMenuField($data['id_menu']); // Actualizar estado del menú padre

        return $result;
    }
    ```
    *   Similar al `saveModule`, pero para la tabla `versasubmenu`.
    *   Importante: Después de guardar, llama a `toggleSubMenuField()` para actualizar un campo en el bean `versamenu` padre.

### Lógica de Interdependencia: `toggleSubMenuField($idMenu)`

Este método privado es interesante porque actualiza un módulo principal (`versamenu`) basándose en el estado de sus submódulos (`versasubmenu`):

```php
// Dentro de app\models\Modules.php
private function toggleSubMenuField($idMenu): void
{
    // Busca todos los submódulos para el id_menu dado
    $subModules = R::find('versasubmenu', 'id_menu = ?', [$idMenu]);
    $allDisabled = true;
    foreach ($subModules as $subModule) {
        if ($subModule->estado === '1') { // Si al menos un submódulo está activo
            $allDisabled = false;
            break;
        }
    }

    $mainModule = R::load('versamenu', $idMenu); // Carga el módulo principal
    if ($allDisabled) {
        $mainModule->submenu = '0'; // Marca que no tiene submódulos activos
    } else {
        $mainModule->submenu = '1'; // Marca que tiene submódulos activos
    }
    R::store($mainModule); // Guarda el cambio en el módulo principal
}
```
*   Este método demuestra cómo se puede manejar lógica que cruza diferentes tipos de beans. Primero consulta los submódulos (`R::find()`), luego carga y actualiza el módulo principal (`R::load()` y `R::store()`).

### Nota sobre `movePosition()`
El método `movePosition($params)` en la clase `Modules` parece estar incompleto, ya que la línea que guardaría los cambios (`R::store($module)`) está comentada. Esta funcionalidad necesitaría ser completada para que el cambio de posición de los módulos surta efecto.

La clase `Modules` ofrece otro ejemplo de cómo organizar el acceso a datos con RedBeanPHP en una aplicación, combinando las funciones ORM de alto nivel con la flexibilidad del SQL directo cuando se requiere.

## Ejemplo Práctico: La Clase `app\models\Perfil`

La clase `app\models\Perfil.php` es responsable de gestionar los perfiles de usuario (roles) y los permisos detallados asociados a cada uno. Como otros modelos del proyecto, extiende `versaWYS\kernel\RedBeanCnn`.

Interactúa con varias tablas/beans:
*   `'versaperfil'`: Almacena la información básica de los perfiles (ID, nombre, estado, página de inicio).
*   `'versaperfildetalle'`: Tabla de enlace que define qué módulos (`menu_id`) y submódulos (`submenu_id`) son accesibles para un `perfil_id` dado.
*   `'versausers'`: Se consulta y actualiza para propagar cambios de permisos y página de inicio a los usuarios asociados a un perfil.
*   `'versamenu'` y `'versasubmenu'`: Se leen para construir la lista completa de permisos disponibles.

### Operaciones CRUD y Listado de Perfiles

1.  **Listar Perfiles (`all($estado = 'all')`):**
    ```php
    // Dentro de app\models\Perfil.php
    public function all($estado = 'all'): array
    {
        if ($estado === 'all') {
            return R::findAll(self::$table); // self::$table es 'versaperfil'
        }
        return R::getAll('SELECT * FROM versaperfil WHERE estado = ?', [$estado]);
    }
    ```
    *   Utiliza `R::findAll()` para obtener todos los perfiles o `R::getAll()` con una consulta SQL para filtrar por estado.

2.  **Guardar un Nuevo Perfil (`save(array $params)`):
    ```php
    // Dentro de app\models\Perfil.php
    public function save(array $params): int
    {
        $perfil = R::dispense(self::$table); // Crea un nuevo bean 'versaperfil'
        $perfil->nombre = $params['nombre'];
        $perfil->estado = $params['estado'];
        return R::store($perfil); // Guarda el bean en la base de datos
    }
    ```

### Gestión de Permisos

La gestión de permisos es una parte central de esta clase.

1.  **Obtener Permisos de un Perfil (`getPerfilPermisos(int $id)`):**
    ```php
    // Dentro de app\models\Perfil.php
    public function getPerfilPermisos(int $id): array
    {
        $sql = "SELECT
                        vm.seccion,
                        vm.id AS id_menu,
                        vm.nombre AS desc_menu,
                        // ... más campos ...
                        vsm.url AS url_submenu
                    FROM versamenu AS vm
                    LEFT JOIN versasubmenu AS vsm ON vm.id = vsm.id_menu AND vsm.estado = 1
                    WHERE vm.estado = 1
                    ORDER BY vm.seccion, vm.posicion ASC, vsm.posicion asc"
        return R::getAll($sql, [$id, $id]);
    }
    ```
    *   Este método utiliza una consulta SQL extensa y compleja con `R::getAll()` para obtener una lista de todos los módulos y submódulos del sistema.
    *   Para cada elemento, indica si el perfil (`$id`) tiene permiso asignado (`checked = 1`) o no (`checked = 0`).
    *   Esto es fundamental para presentar la interfaz de usuario donde se marcan/desmarcan los permisos.

2.  **Guardar Permisos de un Perfil (`savePerfilPermisos(array $params)`):**
    Este es un método multifacético:
    ```php
    // Dentro de app\models\Perfil.php
    public function savePerfilPermisos(array $params)
    {
        // 1. Eliminar permisos antiguos del perfil
        R::exec('DELETE FROM versaperfildetalle WHERE perfil_id = ?', [$params['id']]);

        // 2. Actualizar datos del perfil (nombre, página de inicio)
        $perfil = R::load(self::$table, $params['id']);
        $perfil->nombre = $params['nombre'];
        $perfil->pagina_inicio = $params['pagina_inicio'];
        $result = R::store($perfil);

        // 3. Insertar nuevos permisos en 'versaperfildetalle'
        $permisos = $params['data']; // Estructura con los permisos marcados
        if ($permisos !== '[]' && $permisos !== '') {
            foreach ($permisos as $value) { // Itera sobre secciones/menús/submenús
                // ... lógica para crear beans 'versaperfildetalle' y guardarlos ...
                // Ejemplo para un menú sin submódulo:
                // if ($value2['checked'] === true) {
                //     $perfilDetalle = R::dispense('versaperfildetalle');
                //     $perfilDetalle->perfil_id = $params['id'];
                //     $perfilDetalle->menu_id = $value2['id_menu'];
                //     $perfilDetalle->submenu_id = 0;
                //     R::store($perfilDetalle);
                // }
            }
        }

        // 4. Actualizar permisos y página de inicio para todos los usuarios con este perfil
        $userPerfil = R::getAll('SELECT id FROM versausers WHERE id_perfil = ?', [$params['id']]);
        foreach ($userPerfil as $key => $value) {
            // Eliminar permisos específicos antiguos del usuario
            R::exec('DELETE FROM versaperfildetalleuser WHERE id_user = ?', [$value['id']]);
            // Copiar los nuevos permisos del perfil al usuario
            R::exec(
                'INSERT INTO versaperfildetalleuser (id_user, menu_id, submenu_id) SELECT ?, menu_id, submenu_id FROM versaperfildetalle WHERE perfil_id = ?',
                [$value['id'], $params['id']]
            );
            // Actualizar página de inicio del usuario
            $id_user = (int)$value['id'];
            $userBean = R::load('versausers', $id_user);
            $userBean->pagina_inicio = $params['pagina_inicio'];
            R::store($userBean);
        }
        return $result;
    }
    ```
    *   **Paso 1**: Usa `R::exec()` para una eliminación masiva de los registros de `versaperfildetalle` asociados al perfil.
    *   **Paso 2**: Carga el bean `versaperfil` con `R::load()`, actualiza sus propiedades y lo guarda con `R::store()`.
    *   **Paso 3**: Itera sobre los datos de permisos recibidos, creando y guardando nuevos beans `versaperfildetalle` con `R::dispense()` y `R::store()` para cada permiso otorgado.
    *   **Paso 4**: Para cada usuario (`versausers`) asociado al perfil modificado:
        *   Elimina sus permisos individuales existentes en `versaperfildetalleuser` (`R::exec()`).
        *   Copia los nuevos permisos del perfil a `versaperfildetalleuser` usando `R::exec()` con un `INSERT INTO ... SELECT ...`.
        *   Actualiza la `pagina_inicio` del bean `versausers` (`R::load()` y `R::store()`).

La clase `Perfil` demuestra un manejo robusto de perfiles y permisos, combinando operaciones ORM de RedBeanPHP para beans individuales (`R::load`, `R::store`, `R::dispense`) con el poder de SQL directo a través de `R::getAll` y `R::exec` para consultas complejas y operaciones masivas.

## Ejemplo Práctico: La Clase `app\models\Dashboard`

La clase `app\models\Dashboard.php` tiene un rol especializado: construir las estructuras de menú dinámicas para la interfaz de usuario. Dependiendo de si el usuario es un administrador o un usuario estándar, el menú se genera con diferentes niveles de acceso y opciones.

Como los otros modelos, extiende `versaWYS\kernel\RedBeanCnn`.

### Métodos Principales y Uso de `R::getAll()`

Este modelo se distingue por su uso exclusivo de `R::getAll()` para ejecutar consultas SQL complejas, en lugar de manipular beans individuales para operaciones CRUD. Su propósito es leer y estructurar datos de múltiples tablas (`versamenu`, `versasubmenu`, `versaperfildetalle`, `versaperfildetalleuser`) para generar los menús.

1.  **Generar Menú de Administrador (`getMenuAdmin()`):**
    ```php
    // Dentro de app\models\Dashboard.php
    public function getMenuAdmin(): Cursor|array|int|null
    {
        return R::getAll(
            "SELECT
                        vm.seccion,
                        vm.id AS id_menu,
                        vm.nombre AS desc_menu,
                        // ... más campos ...
                        vsm.url AS url_submenu
                    FROM versamenu AS vm
                    LEFT JOIN versasubmenu AS vsm ON vm.id = vsm.id_menu AND vsm.estado = 1
                    WHERE vm.estado = 1
                    ORDER BY vm.seccion, vm.posicion ASC, vsm.posicion asc"
        );
    }
    ```
    *   Recupera todos los elementos de menú y submódulo activos para un administrador, que típicamente tiene acceso a todas las funcionalidades.

2.  **Generar Menú de Usuario (`getMenuUser(int $idUser, $idPerfil = 0)`):**
    ```php
    // Dentro de app\models\Dashboard.php
    public function getMenuUser(int $idUser, $idPerfil = 0): Cursor|array|int|null
    {
        return R::getAll(
            "SELECT * FROM
                (
                    SELECT // ... Parte 1: Menús con submódulos y permisos de perfil ...
                        am.id AS id_menu, ...
                    FROM versamenu AS am
                        LEFT JOIN versasubmenu AS asm ON asm.id_menu = am.id
                        LEFT JOIN versaperfildetalle AS ap ON asm.id_menu = ap.menu_id AND asm.id = ap.submenu_id AND ap.perfil_id = ?
                    WHERE am.submenu = 1 AND am.estado = 1
                    UNION
                    SELECT // ... Parte 2: Menús sin submódulos y permisos de perfil ...
                        am.id AS id_menu, ...
                    FROM versamenu AS am
                        LEFT JOIN versaperfildetalle AS ap ON am.id = ap.menu_id AND ap.perfil_id = ?
                    WHERE am.submenu = 0 AND am.estado = 1
                ) AS op
                INNER JOIN versaperfildetalleuser as vpu ON vpu.menu_id = op.id_menu AND vpu.submenu_id = op.id_submenu
                WHERE vpu.id_user = ?
                ORDER BY op.seccion, op.menu_posicion, op.submenu_posicion;",
            [$idPerfil, $idPerfil, $idUser]
        );
    }
    ```
    *   Esta consulta es más elaborada. Primero construye una lista de posibles elementos de menú basados en los permisos del perfil (`$idPerfil`) mediante una `UNION`.
    *   Luego, filtra esta lista basándose en los permisos específicos del usuario (`$idUser`) almacenados en `versaperfildetalleuser`.
    *   El resultado es un menú precisamente adaptado a lo que el usuario individual tiene permitido ver.

### Conclusión sobre `Dashboard.php`

La clase `Dashboard.php` es un excelente ejemplo de cómo RedBeanPHP puede ser utilizado para ejecutar consultas SQL directas y complejas cuando el objetivo principal es la lectura y estructuración de datos para vistas específicas, en lugar de la manipulación de beans individuales. Este enfoque es particularmente útil para la agregación de datos y la lógica de presentación que involucra múltiples tablas y condiciones.

## Ejemplo Práctico: La Clase `app\models\Pagination`

La clase `app\models\Pagination.php` ofrece una utilidad genérica y eficiente para paginar conjuntos de resultados de cualquier tabla de la base de datos. Su implementación aprovecha características específicas de MySQL/MariaDB para optimizar el proceso.

Como los otros modelos, extiende `versaWYS\kernel\RedBeanCnn`.

### Método Principal: `pagination()`

El corazón de esta clase es el método `pagination()`:

```php
// Dentro de app\models\Pagination.php
public function pagination(
    string $table,       // Tabla de la cual paginar
    array $fields = [],  // Campos a seleccionar (defecto: *)
    string $where = '',   // Condición WHERE (opcional)
    string $order = 'ORDER BY id ASC', // Cláusula ORDER BY (opcional)
    string $limit = 'LIMIT 0, 15'    // Cláusula LIMIT (opcional)
): array {
    $filter = trim($where) != '' ? "WHERE $where" : '';
    $fields_list = $fields ? implode(',', $fields) : '*';

    // 1. Obtener los datos de la página actual Y calcular el total de filas sin límite
    $result = R::getAll("SELECT SQL_CALC_FOUND_ROWS $fields_list FROM $table $filter $order $limit");

    // 2. Obtener el número total de filas calculado por SQL_CALC_FOUND_ROWS
    $total = R::getCell('SELECT FOUND_ROWS()');

    return ['total' => $total, 'data' => $result];
}
```

**Funcionamiento Detallado:**

1.  **Construcción Dinámica de la Consulta**: El método ensambla una consulta SQL basada en los parámetros proporcionados.
2.  **`SQL_CALC_FOUND_ROWS`**: La consulta principal que se ejecuta con `R::getAll()` incluye la opción `SQL_CALC_FOUND_ROWS` (específica de MySQL/MariaDB). Esta instrucción le indica a la base de datos que, además de devolver el conjunto de resultados limitado por la cláusula `LIMIT`, también calcule el número total de filas que la consulta habría arrojado si no se hubiera aplicado `LIMIT`.
3.  **`FOUND_ROWS()`**: Inmediatamente después de la consulta principal, se ejecuta `R::getCell('SELECT FOUND_ROWS()')`. La función `FOUND_ROWS()` de MySQL/MariaDB devuelve el conteo total de filas calculado en la consulta anterior por `SQL_CALC_FOUND_ROWS`.
4.  **Resultado**: El método devuelve un array asociativo que contiene:
    *   `'total'`: El número total de registros que coinciden con los criterios de filtrado (ignorando la paginación).
    *   `'data'`: Un array con los registros correspondientes a la página actual, según la cláusula `LIMIT`.

### Ventajas de este Enfoque

Este método de paginación es eficiente porque evita la necesidad de ejecutar dos consultas separadas: una para contar todos los registros (`SELECT COUNT(*)...`) y otra para obtener los datos de la página actual (`SELECT ... LIMIT ...`). En su lugar, `SQL_CALC_FOUND_ROWS` y `FOUND_ROWS()` permiten obtener ambos resultados con una sobrecarga mínima en una sola interacción principal con la base de datos para la consulta de datos.

La clase `Pagination` demuestra cómo RedBeanPHP puede ser utilizado para interactuar con funcionalidades específicas del motor de base de datos, permitiendo implementaciones optimizadas y elegantes.

## Manejo de Relaciones entre Beans

RedBeanPHP simplifica enormemente la gestión de relaciones entre diferentes tipos de beans (tablas). En lugar de tener que definir manualmente claves foráneas o tablas de enlace en muchos casos, RedBeanPHP puede inferir y gestionar estas relaciones automáticamente a través de convenciones en la nomenclatura de propiedades de los beans. Esto es especialmente útil cuando se trabaja con el esquema "fluido" de RedBeanPHP (antes de congelarlo).

Las relaciones más comunes son:
*   **Uno a Muchos (One-to-Many)**: Un bean "padre" se relaciona con muchos beans "hijos".
*   **Muchos a Muchos (Many-to-Many)**: Múltiples beans de un tipo se relacionan con múltiples beans de otro tipo, a través de una tabla de enlace intermedia.
*   **Uno a Uno (One-to-One)**: Un bean se relaciona con exactamente un bean de otro tipo (o ninguno). Esto a menudo se maneja de forma similar a las relaciones uno a muchos, pero con lógica adicional para asegurar la unicidad.

### Relaciones Uno a Muchos (One-to-Many)

Este es uno de los tipos de relación más comunes. Por ejemplo, un autor puede tener muchos libros, o en el contexto de versaWYS-PHP, un módulo de menú (`versamenu`) puede tener varios submódulos (`versasubmenu`).

**Cómo funciona en RedBeanPHP:**

RedBeanPHP utiliza una convención de nomenclatura para las propiedades de lista para gestionar estas relaciones. La lista debe tener el formato `own<TipoDeBeanHijo>List`.

1.  **Creación de la Relación:**
    Supongamos que tenemos un bean `$menu` (tipo `'versamenu'`) y un bean `$submenu` (tipo `'versasubmenu'`). Para indicar que `$submenu` pertenece a `$menu`, harías:
    ```php
    // Cargar o crear el bean padre (menú)
    $menu = R::load('versamenu', 1); // o R::dispense('versamenu');

    // Crear un nuevo bean hijo (submenú)
    $submenu1 = R::dispense('versasubmenu');
    $submenu1->nombre = 'Submenú Opción 1';
    $submenu1->url = '/admin/opcion1';

    $submenu2 = R::dispense('versasubmenu');
    $submenu2->nombre = 'Submenú Opción 2';
    $submenu2->url = '/admin/opcion2';

    // Asociar los hijos con el padre
    // RedBeanPHP buscará una propiedad con el formato own<TipoDelBeanHijo>List
    // En este caso, como $submenu1 es de tipo 'versasubmenu', la propiedad es 'ownVersasubmenuList'
    $menu->ownVersasubmenuList[] = $submenu1;
    $menu->ownVersasubmenuList[] = $submenu2;

    // Guardar el bean padre. RedBeanPHP guardará también los hijos asociados y establecerá la relación.
    R::store($menu);
    ```

2.  **Estructura de la Base de Datos Resultante:**
    *   RedBeanPHP (si el esquema no está congelado) creará automáticamente una columna en la tabla `versasubmenu` para la clave foránea. Por defecto, esta columna se llamará `versamenu_id` (siguiendo el patrón `<tipo_bean_padre>_id`).
    *   Cuando `R::store($menu)` se ejecuta, RedBeanPHP asignará el `id` del bean `$menu` a la columna `versamenu_id` de los beans `$submenu1` y `$submenu2`.

3.  **Acceso a los Beans Relacionados:**
    Para obtener todos los submódulos asociados a un menú:
    ```php
    $menuCargado = R::load('versamenu', 1);
    $susSubmenus = $menuCargado->ownVersasubmenuList;

    foreach ($susSubmenus as $sub) {
        echo $sub->nombre . "\n";
    }
    ```
    RedBeanPHP cargará automáticamente los beans hijos relacionados cuando accedas a la propiedad `ownVersasubmenuList`.

4.  **Eliminar un Bean Hijo de la Relación:**
    Para desasociar un submódulo de un menú (sin eliminar el submódulo en sí mismo):
    ```php
    $menu = R::load('versamenu', 1);
    // Supongamos que queremos desasociar $submenu1 (cuyo id es, por ejemplo, 5)
    unset($menu->ownVersasubmenuList[5]); // Se usa el ID del bean hijo como clave en la lista
    R::store($menu);
    ```
    Esto establecería la columna `versamenu_id` del submódulo correspondiente a `NULL` (si la columna lo permite).

**Comparación con el Código Existente en versaWYS-PHP:**

En `app/models/Modules.php`, la relación entre `versamenu` y `versasubmenu` se maneja más manualmente. Por ejemplo, en `saveSubModule()`, el `id_menu` (la clave foránea) se asigna explícitamente:
```php
// En app\models\Modules.php -> saveSubModule()
$module->id_menu = $data['id_menu']; // Asignación manual de la clave foránea
```
Y para leer los submódulos, como en `toggleSubMenuField()`, se usa `R::find()`:
```php
// En app\models\Modules.php -> toggleSubMenuField()
$subModules = R::find('versasubmenu', 'id_menu = ?', [$idMenu]);
```
Si bien este enfoque manual es perfectamente válido y a veces necesario para un control más granular o cuando se trabaja con esquemas de base de datos preexistentes, el método de RedBeanPHP con `own<Type>List` puede simplificar el código para relaciones directas, especialmente durante el desarrollo rápido o cuando se permite a RedBeanPHP gestionar el esquema.

### Relaciones Muchos a Muchos (Many-to-Many)

Las relaciones muchos a muchos ocurren cuando múltiples instancias de un tipo de bean pueden estar relacionadas con múltiples instancias de otro tipo de bean. Un ejemplo común es la relación entre usuarios y grupos: un usuario puede pertenecer a varios grupos, y un grupo puede tener muchos usuarios.

**Cómo funciona en RedBeanPHP:**

RedBeanPHP maneja esto elegantemente mediante el uso de una convención de nomenclatura `shared<TipoDeBean>List` y la creación automática de una tabla de enlace (o tabla de asociación).

1.  **Creación de la Relación:**
    Supongamos que tenemos beans de tipo `'user'` y beans de tipo `'group'`. Para asociarlos:
    ```php
    // Cargar o crear los beans
    $user1 = R::dispense('user');
    $user1->name = 'Juan Pérez';

    $user2 = R::dispense('user');
    $user2->name = 'Ana López';

    $groupA = R::dispense('group');
    $groupA->name = 'Editores';

    $groupB = R::dispense('group');
    $groupB->name = 'Validadores';

    // Asociar usuarios a grupos
    // RedBeanPHP buscará una propiedad con el formato shared<TipoDelOtroBean>List
    $user1->sharedGroupList[] = $groupA;
    $user1->sharedGroupList[] = $groupB; // Juan está en Editores y Validadores

    $user2->sharedGroupList[] = $groupA; // Ana solo está en Editores

    // También puedes hacerlo desde el otro lado de la relación:
    // $groupA->sharedUserList[] = $user1;
    // $groupA->sharedUserList[] = $user2;

    // Guardar los beans. RedBeanPHP se encarga de la tabla de enlace.
    R::store($user1);
    R::store($user2);
    // O R::store($groupA); si se asoció desde el grupo
    ```

2.  **Estructura de la Base de Datos Resultante:**
    *   RedBeanPHP creará automáticamente una tabla de enlace. El nombre de esta tabla se forma combinando los dos tipos de bean en orden alfabético. En este caso, como 'group' viene antes que 'user' alfabéticamente, la tabla de enlace se llamaría `group_user`.
    *   Esta tabla `group_user` contendrá dos columnas de clave foránea: `group_id` y `user_id`.
    *   Cuando guardas un bean con una relación `shared`, RedBeanPHP inserta automáticamente las filas correspondientes en esta tabla de enlace.

3.  **Acceso a los Beans Relacionados:**
    Para obtener todos los grupos a los que pertenece un usuario:
    ```php
    $usuarioCargado = R::load('user', $user1->id);
    $susGrupos = $usuarioCargado->sharedGroupList;

    foreach ($susGrupos as $grupo) {
        echo $usuarioCargado->name . " pertenece a " . $grupo->name . "\n";
    }
    ```
    De manera similar, para obtener todos los usuarios de un grupo:
    ```php
    $grupoCargado = R::load('group', $groupA->id);
    $susUsuarios = $grupoCargado->sharedUserList;
    // ...
    ```

4.  **Eliminar una Asociación:**
    Para quitar un usuario de un grupo (sin eliminar el usuario ni el grupo):
    ```php
    $user = R::load('user', 1);
    // Supongamos que $groupB tiene id 2
    unset($user->sharedGroupList[2]); // Se usa el ID del bean 'group' como clave
    R::store($user);
    ```
    Esto eliminará la fila correspondiente de la tabla de enlace `group_user`.

5.  **Añadir Campos Adicionales a la Tabla de Enlace:**
    Si necesitas almacenar información adicional sobre la relación en sí misma (por ejemplo, la fecha en que un usuario se unió a un grupo), puedes tratar la tabla de enlace como un bean propio. RedBeanPHP te permite acceder y manipular estos beans de enlace directamente.
    ```php
    $user = R::load('user', 1);
    $group = R::load('group', 1);

    // Crear el enlace como un bean
    $link = R::link($user, $group); // O R::dispense('group_user') y asignar manualmente group_id, user_id
    $link->joined_at = date('Y-m-d H:i:s');
    R::store($link);

    // Para acceder a los beans de enlace:
    $enlacesDelUsuario = $user->with('ORDER BY joined_at DESC')->ownGroupUserList;
    foreach($enlacesDelUsuario as $enlace) {
        echo 'Usuario se unió al grupo ID: ' . $enlace->group_id . ' en fecha: ' . $enlace->joined_at;
        // Para obtener el bean del grupo directamente a través del enlace:
        // $grupoDelEnlace = $enlace->group; (si el bean 'group_user' se obtuvo vía $user->ownGroupUserList)
    }
    ```
    Este es un tema más avanzado pero muy potente de RedBeanPHP.

**Comparación con el Código Existente en versaWYS-PHP:**

En `app/models/Perfil.php`, la tabla `versaperfildetalle` actúa como una tabla de enlace entre `versaperfil` y una combinación de `versamenu` y `versasubmenu` para definir permisos. De manera similar, `versaperfildetalleuser` enlaza `versausers` con `versaperfildetalle` (o más directamente con los menús/submódulos).

```php
// En app\models\Perfil.php -> savePerfilPermisos()
// Creación de un registro en 'versaperfildetalle'
$perfilDetalle = R::dispense('versaperfildetalle');
$perfilDetalle->perfil_id = $params['id'];
$perfilDetalle->menu_id = $value2['id_menu'];
$perfilDetalle->submenu_id = $value3['id_submenu']; // submenu_id puede ser 0 si es un menú principal
R::store($perfilDetalle);
```
Este manejo es manual: se crea explícitamente un bean para la tabla de enlace (`versaperfildetalle`) y se asignan las claves foráneas. El sistema `shared<Type>List` de RedBeanPHP automatiza la creación y gestión de esta tabla de enlace y sus registros para relaciones muchos-a-muchos puras.

La elección entre el manejo automático de RedBeanPHP y el manejo manual de tablas de enlace depende de la complejidad de la relación y de si se necesita almacenar metadatos adicionales en la propia relación.

## Próximamente
{{ ... }}

## Hooks de Modelo de RedBeanPHP

RedBeanPHP ofrece un sistema de "hooks" (ganchos) que te permite ejecutar código personalizado en momentos específicos del ciclo de vida de un bean. Estos hooks son métodos que puedes definir en una clase modelo asociada a tu tipo de bean.

Para que RedBeanPHP reconozca y utilice estos hooks automáticamente, tu clase modelo debe:
1.  Extender la clase base `RedBean_SimpleModel`.
2.  Seguir la convención de nomenclatura `Model_NombreDelTipoDeBean` (por ejemplo, si tu bean es de tipo `'user'`, la clase modelo se llamaría `Model_User`).

**Hooks Comunes del Ciclo de Vida:**

*   `public function open()`: Se ejecuta cuando un bean existente es cargado desde la base de datos usando `R::load()` o funciones similares.
*   `public function dispense()`: Se ejecuta cuando un nuevo bean es creado usando `R::dispense()`. Útil para establecer valores por defecto.
*   `public function update()`: Se ejecuta justo antes de que los datos de un bean sean guardados en la base de datos con `R::store()`, tanto para beans nuevos como para existentes. Puedes usarlo para validación o para modificar datos antes de guardar.
*   `public function after_update()`: Se ejecuta justo después de que los datos de un bean han sido guardados en la base de datos.
*   `public function delete()`: Se ejecuta justo antes de que un bean sea eliminado de la base de datos con `R::trash()`.
*   `public function after_delete()`: Se ejecuta justo después de que un bean ha sido eliminado.

**Ejemplo Teórico (si se usara `RedBean_SimpleModel`):**

```php
// Supongamos que tenemos un bean de tipo 'product'
// El modelo se llamaría Model_Product.php

class Model_Product extends RedBean_SimpleModel {

    public function dispense() {
        // Establecer un valor por defecto cuando se crea un nuevo producto
        $this->bean->is_active = true;
        $this->bean->created_at = date('Y-m-d H:i:s');
    }

    public function update() {
        // Validar antes de guardar
        if (empty($this->bean->name)) {
            throw new Exception('El nombre del producto no puede estar vacío.');
        }
        $this->bean->updated_at = date('Y-m-d H:i:s');
    }

    public function after_delete() {
        // Registrar que un producto fue eliminado
        error_log('Producto ID ' . $this->bean->id . ' fue eliminado.');
    }
}
```

**Situación en versaWYS-PHP:**

Los modelos actuales en el directorio `app/models/` de versaWYS-PHP (como `Users.php`, `Modules.php`, `Perfil.php`, etc.) extienden la clase `versaWYS\kernel\RedBeanCnn`. Esta clase personalizada gestiona la conexión y desconexión con la base de datos pero **no** extiende `RedBean_SimpleModel`.

Como resultado, los hooks del ciclo de vida descritos anteriormente **no se activarán automáticamente** para los beans manejados por estas clases de modelo en la forma estándar de RedBeanPHP.

Si se requiere una lógica similar a la de los hooks (por ejemplo, ejecutar código antes o después de guardar un usuario), esta lógica debe ser implementada explícitamente dentro de los métodos de las clases modelo existentes. Por ejemplo, en `app/models/Users.php`, cualquier lógica "pre-guardado" o "post-guardado" tendría que ser añadida directamente en los métodos como `save()`, `updatePerfil()`, etc.

Esto significa que el control sobre el ciclo de vida de los datos se maneja de forma más manual y explícita dentro de los métodos de los modelos del proyecto, en lugar de depender del sistema de FUSE (Frozen Unified Schema Entities) y los hooks automáticos de `RedBean_SimpleModel`.

## Próximamente
{{ ... }}

## Gestión de Esquema con Migraciones

Aunque RedBeanPHP puede gestionar automáticamente el esquema de la base de datos durante la fase de desarrollo (cuando `R::freeze(false)`), las migraciones proporcionan un mecanismo estructurado y versionado para realizar cambios en el esquema de tu base de datos. Son especialmente cruciales en entornos de producción donde el esquema está "congelado" (`R::freeze(true)`), y para cambios más complejos que RedBeanPHP no maneja por defecto.

### ¿Qué es una Migración?

Una migración es un archivo PHP que contiene código para aplicar cambios al esquema de la base de datos (método `up()`) y, opcionalmente, para revertir dichos cambios (método `down()`). Cada migración se ejecuta una sola vez. El sistema de migraciones de versaWYS-PHP lleva un registro de las migraciones que ya se han ejecutado.

### ¿Por qué usar Migraciones con RedBeanPHP?

-   **Control de Versiones del Esquema**: Mantén un historial de cómo ha evolucionado tu base de datos.
-   **Colaboración en Equipo**: Asegura que todos los desarrolladores tengan la misma estructura de base de datos.
-   **Despliegues Consistentes**: Aplica cambios de esquema de manera predecible en diferentes entornos (desarrollo, staging, producción).
-   **Cambios Complejos**: Realiza operaciones como renombrar tablas/columnas, añadir índices específicos, o modificar tipos de datos que RedBeanPHP no haría automáticamente o de la manera deseada.
-   **Trabajar con Esquema Congelado**: Una vez que `R::freeze(true)` está activado (recomendado para producción), RedBeanPHP ya no alterará el esquema. Las migraciones son la forma de gestionarlo.

### Estructura de un Archivo de Migración

Cuando creas una migración usando el comando CLI `php versaCLI migrate:make:NombreDeLaMigracion`, se genera un archivo en `app/database/migrations/` con un timestamp y el nombre que proporcionaste. Este archivo contendrá una clase que extiende `AbstractMigration` (o una clase base similar provista por el framework) y generalmente define dos métodos principales:

-   **`up()`**: Este método contiene el código para aplicar los cambios al esquema de la base de datos. Aquí puedes usar `R::exec()` para sentencias DDL (Data Definition Language) como `CREATE TABLE`, `ALTER TABLE`, etc., o incluso interactuar con RedBeanPHP para operaciones más complejas si es necesario (aunque para cambios de esquema puros, DDL suele ser más directo).
-   **`down()`**: Este método contiene el código para revertir los cambios realizados por el método `up()`. Es importante para poder retroceder si algo sale mal.

### Ejemplo Básico de Migración

*(Sección a completar con un ejemplo de código de un archivo de migración.)*

## Población de Datos con Seeders

Los "seeders" (sembradores) son clases PHP que se utilizan para poblar tu base de datos con datos iniciales o de prueba. Son muy útiles para tener un conjunto de datos consistente para el desarrollo, las pruebas, o para inicializar una aplicación con valores por defecto.

### ¿Qué es un Seeder?

Un seeder es un archivo que contiene un método (generalmente `run()`) donde defines la lógica para insertar datos en tus tablas. Puedes crear múltiples seeders para diferentes propósitos (ej. `UsuariosSeeder`, `ProductosSeeder`, `ConfiguracionInicialSeeder`).

### ¿Cuándo usar Seeders?

-   Después de ejecutar las migraciones para tener una base de datos funcional con datos.
-   Para configurar datos por defecto necesarios para el funcionamiento de la aplicación.
-   Para generar datos de prueba para el desarrollo y testing.

### Estructura de un Archivo Seeder

Cuando creas un seeder usando el comando CLI `php versaCLI seeder:make:NombreDelSeeder`, se genera un archivo en `app/database/seeders/`. Este archivo contendrá una clase que generalmente tiene un método `run()`.

-   **`run()`**: En este método, usarás RedBeanPHP para crear y almacenar beans. Por ejemplo, `R::dispense('nombretabla')`, asignar propiedades, y luego `R::store($bean)`.

### Ejemplo Básico de Seeder

*(Sección a completar con un ejemplo de código de un archivo seeder.)*

```

### Ejemplo Básico de Migración

Así se vería un archivo de migración para crear una tabla `products` con algunas columnas. El archivo se llamaría algo como `app/database/migrations/m20230515100000_CreateProductsTable.php`:

```php
<?php

declare(strict_types=1);

namespace app\migrations;

use RedBeanPHP\R;

class m20230515100000_CreateProductsTable { // El nombre de la clase coincide con el nombre del archivo
    public static function up() {
        try {
            R::exec("CREATE TABLE products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                stock INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            return ['message' => 'Tabla products creada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => 'Error al crear la tabla products: ' . $e->getMessage(), 'success' => false];
        }
    }

    public static function down() {
        try {
            R::exec("DROP TABLE IF EXISTS products");
            return ['message' => 'Tabla products eliminada con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => 'Error al eliminar la tabla products: ' . $e->getMessage(), 'success' => false];
        }
    }
}
```

**Consideraciones Adicionales para Migraciones:**

-   **Idempotencia**: Asegúrate de que tus migraciones sean idempotentes si es posible. Por ejemplo, usar `CREATE TABLE IF NOT EXISTS` o `DROP TABLE IF EXISTS` puede ayudar, aunque el sistema de migraciones de versaWYS-PHP ya controla que cada migración se ejecute una sola vez.
-   **Transacciones**: Para migraciones complejas que involucran múltiples sentencias DDL, considera si tu motor de base de datos soporta DDL transaccional. MySQL, por ejemplo, hace un commit implícito después de la mayoría de las sentencias DDL, por lo que agruparlas en una transacción explícita puede no tener el efecto deseado para rollback en caso de fallo de una sentencia intermedia.
-   **No Modificar Migraciones Antiguas**: Una vez que una migración ha sido ejecutada (especialmente en entornos compartidos o producción), no la modifiques. Crea una nueva migración para realizar cambios adicionales.

## Población de Datos con Seeders
{{ ... }}

```

### Ejemplo Básico de Seeder

A continuación, un ejemplo de un seeder que podría poblar la tabla `products` (creada en el ejemplo de migración anterior) con algunos datos iniciales. El archivo se llamaría `app/database/seeders/ProductsSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace app\seeders;

use RedBeanPHP\R;

class ProductsSeeder { // El nombre de la clase es el que se usó al crearlo con el comando
    public static function run() {
        try {
            $product1 = R::dispense('products');
            $product1->name = 'Laptop Modelo X';
            $product1->description = 'Una laptop potente para profesionales.';
            $product1->price = 1200.50;
            $product1->stock = 50;
            R::store($product1);

            $product2 = R::dispense('products');
            $product2->name = 'Teclado Mecánico K70';
            $product2->description = 'Teclado mecánico con retroiluminación RGB.';
            $product2->price = 150.75;
            $product2->stock = 120;
            R::store($product2);

            $product3 = R::dispense('products');
            $product3->name = 'Monitor UltraWide 34"';
            $product3->description = 'Monitor curvo para una experiencia inmersiva.';
            $product3->price = 499.99;
            $product3->stock = 30;
            R::store($product3);

            return ['message' => 'Seeder ProductsSeeder ejecutado con éxito.', 'success' => true];
        } catch (\Exception $e) {
            return ['message' => 'Error en ProductsSeeder: ' . $e->getMessage(), 'success' => false];
        }
    }
}
```

**Consideraciones Adicionales para Seeders:**

-   **Dependencias entre Seeders**: Si un seeder depende de datos creados por otro (por ejemplo, un `OrderItemsSeeder` que necesita productos y pedidos), asegúrate de que se ejecuten en el orden correcto. El comando `php versaCLI seeder:run:NombreDelSeeder` te permite ejecutar seeders individualmente.
-   **Datos Únicos**: Si estás insertando datos que deben ser únicos (como nombres de usuario o correos electrónicos), considera verificar primero si el dato ya existe para evitar errores o duplicados, o utiliza las capacidades de RedBeanPHP para manejar restricciones si tu esquema las define.
-   **Cantidad de Datos**: Para datos de prueba, puedes generar grandes cantidades de datos usando librerías como Faker (si está integrada o la integras al proyecto).

## Próximamente
{{ ... }}
