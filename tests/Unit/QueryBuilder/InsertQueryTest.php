<?php

/**
 * # Phobos Framework
 *
 * Para la información completa acerca del copyright y la licencia,
 * por favor vea el archivo LICENSE que va distribuido con el código fuente.
 *
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */

namespace PhobosFramework\Database\Tests\Unit\QueryBuilder;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\QueryBuilder\InsertQuery;
use PhobosFramework\Database\Exceptions\InvalidArgumentException;
use PhobosFramework\Database\Connection\ConnectionInterface;
use Mockery;

/**
 * Clase de pruebas unitarias para InsertQuery.
 *
 * Esta clase contiene pruebas exhaustivas para verificar el funcionamiento
 * del constructor de consultas INSERT. Prueba la construcción de consultas SQL
 * para inserción de datos, tanto para filas individuales como múltiples,
 * validación de columnas, manejo de valores nulos y tipos numéricos.
 *
 * Características probadas:
 * - Construcción de consultas INSERT básicas
 * - Inserción de múltiples filas
 * - Validación de consistencia de columnas
 * - Manejo de enlaces (bindings)
 * - Ejecución de consultas
 * - Encadenamiento de métodos
 * - Manejo de valores nulos y numéricos
 */
class InsertQueryTest extends TestCase {
    private ConnectionInterface $connection;
    private InsertQuery $query;

    protected function setUp(): void {
        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->query = new InsertQuery($this->connection);
    }

    protected function tearDown(): void {
        Mockery::close();
    }

    public function test_into_sets_table(): void {
        $result = $this->query->into('users');

        $this->assertInstanceOf(InsertQuery::class, $result);
    }

    public function test_values_sets_single_row(): void {
        $result = $this->query->values(['name' => 'John', 'email' => 'john@example.com']);

        $this->assertInstanceOf(InsertQuery::class, $result);
    }

    public function test_get_query_generates_valid_sql_for_single_row(): void {
        $this->query
            ->into('users')
            ->values(['name' => 'John', 'email' => 'john@example.com']);

        $sql = $this->query->getQuery();

        $this->assertStringContainsString('INSERT INTO users', $sql);
        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('email', $sql);
        $this->assertStringContainsString('?', $sql);
    }

    public function test_get_query_generates_valid_sql_for_multiple_rows(): void {
        $this->query
            ->into('users')
            ->values([
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'jane@example.com']
            ]);

        $sql = $this->query->getQuery();

        $this->assertStringContainsString('INSERT INTO users', $sql);
        // Should have two sets of value placeholders
        $this->assertEquals(2, substr_count($sql, '(?, ?)'));
    }

    public function test_values_throws_exception_for_inconsistent_columns(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('different columns');

        $this->query
            ->into('users')
            ->values([
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane'] // Missing 'email' column
            ]);

        $this->query->getQuery(); // Validation happens during query building
    }

    public function test_values_shows_missing_columns_in_error(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing columns: email');

        $this->query
            ->into('users')
            ->values([
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane']
            ]);

        $this->query->getQuery();
    }

    public function test_values_shows_extra_columns_in_error(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Extra columns: phone');

        $this->query
            ->into('users')
            ->values([
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'phone' => '123456']
            ]);

        $this->query->getQuery();
    }

    public function test_get_bindings_returns_correct_values_for_single_row(): void {
        $this->query
            ->into('users')
            ->values(['name' => 'John', 'email' => 'john@example.com']);

        $bindings = $this->query->getBindings();

        $this->assertEquals(['John', 'john@example.com'], $bindings);
    }

    public function test_get_bindings_returns_correct_values_for_multiple_rows(): void {
        $this->query
            ->into('users')
            ->values([
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'jane@example.com']
            ]);

        $bindings = $this->query->getBindings();

        $this->assertEquals([
            'John', 'john@example.com',
            'Jane', 'jane@example.com'
        ], $bindings);
    }

    public function test_get_query_with_bindings_returns_array(): void {
        $this->query
            ->into('users')
            ->values(['name' => 'John', 'email' => 'john@example.com']);

        $result = $this->query->getQueryWithBindings();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('bindings', $result);
    }

    public function test_execute_calls_connection_execute(): void {
        $stmt = Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $this->connection
            ->shouldReceive('execute')
            ->once()
            ->andReturn($stmt);

        $this->query
            ->into('users')
            ->values(['name' => 'John', 'email' => 'john@example.com']);

        $affected = $this->query->executeAndCount();

        $this->assertEquals(1, $affected);
    }

    public function test_method_chaining_works(): void {
        $result = $this->query
            ->into('users')
            ->values(['name' => 'John', 'email' => 'john@example.com']);

        $this->assertInstanceOf(InsertQuery::class, $result);
    }

    public function test_insert_with_null_values(): void {
        $this->query
            ->into('users')
            ->values(['name' => 'John', 'email' => null]);

        $bindings = $this->query->getBindings();

        $this->assertEquals(['John', null], $bindings);
    }

    public function test_insert_with_numeric_values(): void {
        $this->query
            ->into('products')
            ->values(['name' => 'Widget', 'price' => 19.99, 'stock' => 100]);

        $bindings = $this->query->getBindings();

        $this->assertEquals(['Widget', 19.99, 100], $bindings);
    }
}
