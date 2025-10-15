# PhobosFramework Database Tests

Esta suite de tests proporciona cobertura completa para el PhobosFramework Database Layer.

## Estructura de Tests

```
tests/
├── Unit/                           # Tests unitarios
│   ├── QueryBuilder/
│   │   ├── Clauses/               # Tests para cláusulas SQL
│   │   │   ├── LimitClauseTest.php
│   │   │   ├── JoinClauseTest.php
│   │   │   └── WhereClauseTest.php
│   │   ├── DeleteQueryTest.php
│   │   └── InsertQueryTest.php
│   ├── Connection/
│   │   └── TransactionManagerTest.php
│   └── Drivers/
│       └── AbstractDriverTest.php
└── Integration/                    # Tests de integración
    └── EntityCRUDTest.php
```

## Ejecutar Tests

### Instalar Dependencias

```bash
composer install
```

### Ejecutar Todos los Tests

```bash
composer test
# o directamente:
./vendor/bin/phpunit
```

### Ejecutar Solo Tests Unitarios

```bash
./vendor/bin/phpunit --testsuite=Unit
```

### Ejecutar Solo Tests de Integración

```bash
./vendor/bin/phpunit --testsuite=Integration
```

### Ejecutar Tests con Cobertura

```bash
composer test-coverage
```

Esto generará un reporte HTML en `coverage/index.html`.

### Ejecutar Tests Específicos

```bash
# Un archivo específico
./vendor/bin/phpunit tests/Unit/QueryBuilder/Clauses/LimitClauseTest.php

# Una clase específica
./vendor/bin/phpunit --filter LimitClauseTest

# Un método específico
./vendor/bin/phpunit --filter test_set_limit_accepts_valid_value
```

## Cobertura de Tests

### Tests Unitarios

#### Clauses
- **LimitClauseTest**: Valida LIMIT y OFFSET con validaciones de valores negativos
- **JoinClauseTest**: Valida tipos de JOIN y normalización de sintaxis
- **WhereClauseTest**: Valida condiciones WHERE, IN clauses y arrays vacíos

#### Query Builders
- **DeleteQueryTest**: Valida construcción de DELETE queries con LIMIT
- **InsertQueryTest**: Valida INSERT simple y múltiple con validación de columnas

#### Connection
- **TransactionManagerTest**: Valida transacciones anidadas, savepoints, commit y rollback

#### Drivers
- **AbstractDriverTest**: Valida configuración de drivers, charset sanitization y savepoints

### Tests de Integración

- **EntityCRUDTest**: Valida operaciones completas de CRUD, change tracking, clonación y transacciones en un ambiente SQLite real

## Características Probadas

### Validaciones Implementadas 
1. Recursión infinita en EntityManager::__set()
2. SQL injection en AbstractDriver::configure()
3. Validación de LIMIT negativo en DeleteQuery
4. Validación de savepoints en TransactionManager
5. Validación de columnas consistentes en InsertQuery
6. Validación de arrays vacíos en IN clauses
7. Validación de PK en TableEntity::performUpdate()
8. Implementación de __clone() en EntityManager
9. Protección de singletons
10. Validación de LIMIT/OFFSET negativos en LimitClause
11. Validación de tipos de JOIN en JoinClause
12. Consistencia en excepciones custom del framework

## Mocks y Stubs

Los tests utilizan **Mockery** para crear mocks de:
- `ConnectionInterface`: Para simular conexiones sin BD real
- `PDO`: Para simular operaciones de base de datos

## Configuración

La configuración de PHPUnit está en `phpunit.xml`:

```xml
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

Los tests de integración usan SQLite en memoria para ejecutarse rápidamente sin necesidad de configuración externa.

## Escribir Nuevos Tests

### Test Unitario Ejemplo

```php
<?php
namespace PhobosFramework\Database\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyNewTest extends TestCase {
    public function test_something(): void {
        // Arrange
        $expected = 'value';

        // Act
        $actual = someFunction();

        // Assert
        $this->assertEquals($expected, $actual);
    }
}
```

### Test de Integración Ejemplo

```php
<?php
namespace PhobosFramework\Database\Tests\Integration;

use PHPUnit\Framework\TestCase;

class MyIntegrationTest extends TestCase {
    protected function setUp(): void {
        // Setup database connection
    }

    protected function tearDown(): void {
        // Cleanup
    }

    public function test_integration_scenario(): void {
        // Test end-to-end functionality
    }
}
```

## CI/CD

Para integrar estos tests en tu pipeline de CI/CD:

```yaml
# Ejemplo para GitHub Actions
- name: Run tests
  run: composer test

- name: Generate coverage
  run: composer test-coverage
```

## Notas

- Los tests están diseñados para ser independientes y pueden ejecutarse en cualquier orden
- Los tests de integración limpian el estado después de cada ejecución
- Se usa SQLite en memoria para tests rápidos y sin dependencias externas
- Los mocks permiten probar lógica sin conexiones reales a BD
