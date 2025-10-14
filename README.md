# Phobos Database Layer

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.3-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.txt)
[![Phobos Framework](https://img.shields.io/badge/Phobos-Framework-orange)](https://github.com/mongoose-studio/phobos-framework)

La capa de base de datos de **Phobos Framework** está diseñada como un componente standalone, desacoplado del núcleo del framework (a excepción del `DatabaseServiceProvider`). Proporciona herramientas de abstracción de datos mediante un **Query Builder** de sintaxis fluida y un **ORM** que implementa el patrón **Active Record**, facilitando la definición de modelos persistentes. Admite la gestión de múltiples conexiones simultáneas, la ejecución de transacciones anidadas y el uso de adaptadores personalizados, ofreciendo un equilibrio entre rendimiento, legibilidad y flexibilidad arquitectónica.

## Características

- 🔍 **Query Builder Fluido** - Interfaz expresiva para SELECT, INSERT, UPDATE, DELETE
- 🏗️ **ORM con Active Record** - Entidades que combinan datos y operaciones de BD
- 🔄 **Gestión de Transacciones** - Soporte para transacciones anidadas con savepoints
- 🎯 **Change Tracking** - Las entidades rastrean cambios para optimizar UPDATEs
- 🔌 **Múltiples Conexiones** - Manejo de múltiples bases de datos simultáneamente
- 🗂️ **Schema Aliasing** - Mapeo de aliases para multi-tenant o multi-ambiente
- 🛡️ **Prepared Statements** - Todas las queries usan parameter binding para seguridad
- 💉 **Integración con DI** - Compatible con el Container de Phobos Framework

## Instalación

```bash
composer require mongoose-studio/phobos-database
```

## Configuración

### 1. Configuración de Base de Datos

Crea `config/database.php`:

```php
<?php

return [
    'default' => 'mysql',
    
    'drivers' => [
        'mysql' => PhobosFramework\Database\Drivers\MySQLDriver::class,
        'pgsql' => PhobosFramework\Database\Drivers\PostgreSQLDriver::class,
    ],
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'myapp'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
        
        'analytics' => [
            'driver' => 'mysql',
            'host' => env('ANALYTICS_DB_HOST', 'localhost'),
            'database' => env('ANALYTICS_DB_DATABASE', 'analytics'),
            'username' => env('ANALYTICS_DB_USERNAME', 'root'),
            'password' => env('ANALYTICS_DB_PASSWORD', ''),
        ],
    ],
];
```

### 2. Registro del Service Provider (con Phobos Framework)

```php
<?php

namespace App;

use PhobosFramework\Database\DatabaseServiceProvider;

class AppModule implements ModuleInterface
{
    public function providers(): array
    {
        return [
            DatabaseServiceProvider::class,
            // ... otros providers
        ];
    }
}
```

### 3. Schema Aliasing (Opcional)

Para ambientes multi-tenant o multi-entorno:

```php
// En bootstrap o service provider
schemaAlias('app', 'myapp_production');
schemaAlias('analytics', 'myapp_analytics');

// O múltiples a la vez
schemaBulkAlias([
    'app' => 'myapp_production',
    'analytics' => 'myapp_analytics',
    'tenant1' => 'tenant_abc_2024',
]);
```

## Uso

### Query Builder

#### SELECT Queries

```php
// Query simple
$users = query()
    ->select('id', 'username', 'email')
    ->from('users')
    ->where(['active = ?' => 1])
    ->fetch();

// Con JOINs
$posts = query()
    ->select('p.id', 'p.title', 'u.username', 'u.email')
    ->from('posts', 'p')
    ->innerJoin('users', 'u', 'u.id = p.user_id')
    ->where(['p.published = ?' => true])
    ->orderBy('p.created_at DESC')
    ->limit(10)
    ->fetch();

// Con múltiples condiciones
$results = query()
    ->select('*')
    ->from('products')
    ->where(['category = ?' => 'electronics'])
    ->where(['price > ?' => 100])
    ->where(['stock > ?' => 0])
    ->fetch();

// LIKE y operadores
$search = query()
    ->select('*')
    ->from('articles')
    ->where(['title LIKE ?' => '%PHP%'])
    ->orWhere(['content LIKE ?' => '%framework%'])
    ->fetch();

// Subqueries
$subquery = query()
    ->select('user_id')
    ->from('orders')
    ->where(['total > ?' => 1000]);

$vipUsers = query()
    ->select('*')
    ->from('users')
    ->where(['id IN' => $subquery])
    ->fetch();

// GROUP BY y HAVING
$stats = query()
    ->select('category', 'COUNT(*) as total', 'AVG(price) as avg_price')
    ->from('products')
    ->groupBy('category')
    ->having(['COUNT(*) > ?' => 5])
    ->fetch();

// UNION
$query1 = query()->select('name')->from('customers');
$query2 = query()->select('name')->from('suppliers');

$all = $query1->union($query2)->fetch();

// Fetch único resultado
$user = query()
    ->select('*')
    ->from('users')
    ->where(['id = ?' => 5])
    ->fetchOne();

// Fetch columna específica
$emails = query()
    ->select('email')
    ->from('users')
    ->fetchColumn('email');

// Contar resultados
$count = query()
    ->select('COUNT(*) as total')
    ->from('users')
    ->where(['active = ?' => 1])
    ->fetchOne()['total'];
```

#### INSERT Queries

```php
// Insert simple
insert()
    ->into('users')
    ->values([
        'username' => 'john_doe',
        'email' => 'john@example.com',
        'created_at' => date('Y-m-d H:i:s'),
    ])
    ->execute();

// Insert múltiple
insert()
    ->into('tags')
    ->values([
        ['name' => 'PHP'],
        ['name' => 'JavaScript'],
        ['name' => 'Python'],
    ])
    ->execute();

// Con conexión específica
insert('analytics')
    ->into('events')
    ->values(['event' => 'page_view', 'timestamp' => time()])
    ->execute();
```

#### UPDATE Queries

```php
// Update simple
update()
    ->table('users')
    ->set([
        'email' => 'newemail@example.com',
        'updated_at' => date('Y-m-d H:i:s'),
    ])
    ->where(['id = ?' => 5])
    ->execute();

// Update con límite
update()
    ->table('posts')
    ->set(['views' => 'views + 1']) // Expresión SQL
    ->where(['category = ?' => 'news'])
    ->limit(10)
    ->execute();

// Update múltiples condiciones
update()
    ->table('products')
    ->set(['price' => 'price * 0.9']) // 10% descuento
    ->where(['category = ?' => 'electronics'])
    ->where(['stock > ?' => 0])
    ->execute();
```

#### DELETE Queries

```php
// Delete simple
delete()
    ->from('sessions')
    ->where(['expires_at < ?' => date('Y-m-d H:i:s')])
    ->execute();

// Delete con límite
delete()
    ->from('logs')
    ->where(['level = ?' => 'debug'])
    ->limit(1000)
    ->execute();

// Delete con ORDER BY
delete()
    ->from('notifications')
    ->where(['user_id = ?' => 123])
    ->orderBy('created_at ASC')
    ->limit(50)
    ->execute();
```

### Entidades (Active Record)

#### Definir una Entidad

```php
<?php

namespace App\Entities;

use PhobosFramework\Database\Entity\TableEntity;

class User extends TableEntity
{
    // Configuración de la tabla
    protected static string $schema = 'app';  // Alias del schema
    protected static string $entity = 'users'; // Nombre de la tabla
    protected static array $pk = ['id'];       // Primary key(s)
    protected static ?string $connection = null; // null = default connection

    // Propiedades (mapean a columnas)
    public int $id;
    public string $username;
    public string $email;
    public string $password;
    public bool $active;
    public ?string $created_at;
    public ?string $updated_at;
}
```

#### Operaciones CRUD

**CREATE:**

```php
$user = new User();
$user->username = 'juan_perez';
$user->email = 'juanperez@ejemplo.cl';
$user->password = password_hash('secret', PASSWORD_BCRYPT);
$user->active = true;
$user->created_at = date('Y-m-d H:i:s');

$user->save(); // INSERT en la BD
echo $user->id; // ID auto-incrementado disponible
```

**READ:**

```php
// Buscar por primary key
$user = User::findByPk(5);

// Buscar múltiples registros
$activeUsers = User::find(
    ['active = ?' => true],
    'username ASC',
    0,
    10
);

// Buscar primer resultado
$admin = User::findFirst(['username = ?' => 'admin']);

// Contar registros
$totalUsers = User::count(['active = ?' => true]);

// Verificar existencia
$exists = User::exists(['email = ?' => 'test@example.com']);
```

**UPDATE:**

```php
$user = User::findByPk(5);
$user->email = 'newemail@example.com';
$user->updated_at = date('Y-m-d H:i:s');

$user->save(); // UPDATE automático (solo campos modificados)

// Verificar si hay cambios
if ($user->isDirty()) {
    $changes = $user->getDirtyFields(); // ['email', 'updated_at']
}
```

**DELETE:**

```php
// Delete instancia
$user = User::findByPk(5);
$user->remove();

// Delete estático
User::delete(['active = ?' => false], 100);
```

#### Change Tracking

```php
$user = User::findByPk(5);

// Estado original
$original = $user->getOriginalData();

// Modificar
$user->email = 'new@example.com';
$user->username = 'new_username';

// Verificar cambios
if ($user->isDirty()) {
    $dirty = $user->getDirtyFields(); // ['email', 'username']
    
    // Solo campos modificados en UPDATE
    $changes = $user->toArray(true); // ['email' => 'new@...', 'username' => 'new_...']
}

$user->save(); // Solo actualiza campos modificados
```

#### Modo Dry-Run (Debug)

```php
// Ver SQL sin ejecutar
$dryRun = User::find(['active = ?' => true], 'id ASC', 0, 10, true);
// Returns: ['query' => 'SELECT ...', 'bindings' => [1]]

$user = new User();
$user->username = 'test';
$dryRun = $user->save(true);
// Returns: ['query' => 'INSERT INTO ...', 'bindings' => ['test']]
```

### Entidades de Vista (Read-Only)

```php
<?php

namespace App\Entities;

use PhobosFramework\Database\Entity\ViewEntity;

class UserStats extends ViewEntity
{
    protected static string $schema = 'app';
    protected static string $entity = 'v_user_statistics';
    
    public int $user_id;
    public string $username;
    public int $total_posts;
    public int $total_comments;
    public float $avg_rating;
}

// Uso (solo lectura)
$stats = UserStats::find();
$userStat = UserStats::findFirst(['user_id = ?' => 5]);

// save() y remove() lanzarán LogicException
```

### Stored Procedures

```php
<?php

namespace App\Entities;

use PhobosFramework\Database\Entity\StoredProcedureEntity;

class GenerateReport extends StoredProcedureEntity
{
    protected static string $schema = 'app';
    protected static string $entity = 'sp_generate_monthly_report';
    
    // Parámetros del SP
    public int $month;
    public int $year;
    public string $report_type;
}

// Ejecutar
$sp = new GenerateReport();
$sp->month = 10;
$sp->year = 2024;
$sp->report_type = 'sales';

$results = $sp->execute();
```

### Transacciones

#### Transacciones Manuales

```php
try {
    beginTransaction();
    
    $user = new User();
    $user->username = 'john';
    $user->save();
    
    $profile = new Profile();
    $profile->user_id = $user->id;
    $profile->save();
    
    commit();
} catch (\Exception $e) {
    rollback();
    throw $e;
}
```

#### Transacciones con Helper

```php
$result = transaction(function() {
    $user = new User();
    $user->username = 'john';
    $user->save();
    
    $profile = new Profile();
    $profile->user_id = $user->id;
    $profile->save();
    
    return $user;
});

// Rollback automático si se lanza excepción
```

#### Transacciones Anidadas (Savepoints)

```php
beginTransaction(); // Transacción real

try {
    $user = new User();
    $user->save();
    
    beginTransaction(); // Savepoint sp_1
    
    try {
        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->save();
        
        commit('sp_1'); // Commit savepoint
    } catch (\Exception $e) {
        rollback('sp_1'); // Rollback solo el savepoint
    }
    
    commit(); // Commit transacción principal
} catch (\Exception $e) {
    rollback(); // Rollback todo
}

// Verificar estado
if (inTransaction()) {
    $level = getTransactionLevel(); // Nivel de anidamiento
}
```

### Múltiples Conexiones

```php
// Query con conexión específica
$analyticsData = query('analytics')
    ->select('*')
    ->from('events')
    ->where(['date = ?' => date('Y-m-d')])
    ->fetch();

// Entidad con conexión específica
class AnalyticsEvent extends TableEntity {
    protected static string $connection = 'analytics';
    protected static string $entity = 'events';
    // ...
}

// Transacción en conexión específica
transaction(function() {
    // Operaciones en analytics
}, 'analytics');

// Obtener conexión directamente
$pdo = db('analytics')->getPdo();
```

## Helpers Globales

```php
// Conexión
$connection = db(?string $connection = null);

// Query Builders
$query = query(?string $connection = null);
$insert = insert(?string $connection = null);
$update = update(?string $connection = null);
$delete = delete(?string $connection = null);

// Transacciones
beginTransaction(?string $connection = null);
commit(?string $savepoint = null, ?string $connection = null);
rollback(?string $savepoint = null, ?string $connection = null);
transaction(callable $callback, ?string $connection = null);
inTransaction(?string $connection = null): bool;
getTransactionLevel(?string $connection = null): int;

// Schema Registry
schemaAlias(string $alias, string $realSchema);
schemaBulkAlias(array $aliases);
```

## Arquitectura

### Componentes Principales

**Connection Layer**
- `ConnectionManager`: Singleton que gestiona múltiples conexiones
- `PDOConnection`: Implementación basada en PDO
- `TransactionManager`: Manejo de transacciones con savepoints
- __Lazy loading__: conexiones se crean solo cuando se usan

**Query Builder**
- `QueryBuilder`: Interfaz fluida para SELECT con joins, subqueries, unions
- `InsertQuery`, `UpdateQuery`, `DeleteQuery`: Builders especializados
- `Clauses/`: Implementaciones individuales (WHERE, JOIN, ORDER BY, etc.)
- Prepared statements automáticos para seguridad

**Entity System**
- `EntityManager`: Clase base con hydration y change tracking
- `TableEntity`: Active Record para tablas con CRUD
- `ViewEntity`: Entidades read-only para vistas
- `StoredProcedureEntity`: Ejecución de stored procedures
- State tracking: new vs persisted, dirty fields, valores originales

**Schema Registry**
- `SchemaRegistry`: Singleton para mapeo de aliases
- Permite cambiar schemas sin modificar código de entidades

## Integración con Phobos Framework

Cuando se usa con Phobos Framework:

1. Registra `DatabaseServiceProvider` en tu módulo
2. El provider lee `config('database')` para configuración
3. Servicios registrados en el Container:
    - `db` → ConnectionInterface por defecto
    - `db.manager` → ConnectionManager
    - `db.transaction` → TransactionManager
4. Acceso vía inyección de dependencias o helpers

```php
// En un controller
class UserController {
    public function __construct(
        private ConnectionInterface $db,
        private TransactionManager $transactions
    ) {}
    
    public function index() {
        $users = User::find(['active = ?' => true]);
        return Response::json($users);
    }
}
```

## Notas Importantes

- **PHP 8.3+ requerido** - Usa typed properties, union types, named arguments
- **Propiedades reservadas** - No uses: `_isNew`, `_original`, `_dirty`, `_reserved`, `schema`, `entity`, `pk`
- **Change tracking automático** - `__set()` marca campos como dirty automáticamente
- **Prepared statements siempre** - Todas las queries usan parameter binding
- **Lazy loading** - Conexiones se crean solo cuando se usan por primera vez
- **Sin ORM completo** - Es Active Record, no un ORM completo como Doctrine
- **Schema aliasing** - Útil para multi-tenant, multi-ambiente, o testing

## Testing

Para testing de desarrollo:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../phobos-database"
    }
  ],
  "require": {
    "mongoose-studio/phobos-database": "*"
  }
}
```

## Licencia

MIT License - ver el archivo [LICENSE](LICENSE.txt) para más detalles.

## Autor

**Marcel Rojas**  
[marcelrojas16@gmail.com](mailto:marcelrojas16@gmail.com)  
__Mongoose Studio__

## Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/amazing-feature`)
3. Commit tus cambios (`git commit -m 'Add amazing feature'`)
4. Push a la rama (`git push origin feature/amazing-feature`)
5. Abre un Pull Request

---

**Phobos Framework** by Mongoose Studio
