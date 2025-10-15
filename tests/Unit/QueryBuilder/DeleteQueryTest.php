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
use PhobosFramework\Database\QueryBuilder\DeleteQuery;
use PhobosFramework\Database\Exceptions\InvalidArgumentException;
use PhobosFramework\Database\Connection\ConnectionInterface;
use Mockery;

/**
 * Clase de prueba para DeleteQuery que verifica la funcionalidad del constructor de consultas DELETE.
 *
 * Esta clase contiene pruebas unitarias que verifican:
 * - La configuración correcta de la tabla objetivo (FROM)
 * - La adición de condiciones WHERE
 * - La aplicación de límites en las consultas
 * - La generación de consultas SQL válidas
 * - La gestión de parámetros vinculados
 * - La ejecución de consultas a través de la conexión
 * - El encadenamiento de métodos
 * - El manejo de múltiples condiciones WHERE
 *
 * @package PhobosFramework\Database\Tests\Unit\QueryBuilder
 */
class DeleteQueryTest extends TestCase {
    private ConnectionInterface $connection;
    private DeleteQuery $query;

    protected function setUp(): void {
        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->query = new DeleteQuery($this->connection);
    }

    protected function tearDown(): void {
        Mockery::close();
    }

    public function test_from_sets_table(): void {
        $result = $this->query->from('users');

        $this->assertInstanceOf(DeleteQuery::class, $result);
        $sql = $this->query->getQuery();
        $this->assertStringContainsString('DELETE FROM users', $sql);
    }

    public function test_where_adds_condition(): void {
        $this->query->from('users')->where('id = ?', 1);

        $sql = $this->query->getQuery();
        $bindings = $this->query->getBindings();

        $this->assertStringContainsString('WHERE', $sql);
        $this->assertEquals([1], $bindings);
    }

    public function test_limit_sets_limit(): void {
        $this->query->from('users')->limit(10);

        $sql = $this->query->getQuery();

        $this->assertStringContainsString('LIMIT 10', $sql);
    }

    public function test_limit_throws_exception_for_negative_value(): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('LIMIT must be a non-negative integer');

        $this->query->from('users')->limit(-1);
    }

    public function test_limit_accepts_zero(): void {
        $this->query->from('users')->limit(0);

        $sql = $this->query->getQuery();

        $this->assertStringContainsString('LIMIT 0', $sql);
    }

    public function test_get_query_generates_valid_sql(): void {
        $this->query->from('users')->where('status = ?', 'inactive')->limit(5);

        $sql = $this->query->getQuery();

        $this->assertStringContainsString('DELETE FROM users', $sql);
        $this->assertStringContainsString('WHERE', $sql);
        $this->assertStringContainsString('LIMIT 5', $sql);
    }

    public function test_get_bindings_returns_correct_values(): void {
        $this->query
            ->from('users')
            ->where('status = ?', 'inactive')
            ->where('created_at < ?', '2023-01-01');

        $bindings = $this->query->getBindings();

        $this->assertEquals(['inactive', '2023-01-01'], $bindings);
    }

    public function test_get_query_with_bindings_returns_array(): void {
        $this->query->from('users')->where('id = ?', 1);

        $result = $this->query->getQueryWithBindings();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('query', $result);
        $this->assertArrayHasKey('bindings', $result);
        $this->assertEquals([1], $result['bindings']);
    }

    public function test_execute_calls_connection_execute(): void {
        $stmt = Mockery::mock(\PDOStatement::class);
        $stmt->shouldReceive('rowCount')->once()->andReturn(1);

        $this->connection
            ->shouldReceive('execute')
            ->once()
            ->with('DELETE FROM users WHERE id = ? LIMIT 1', [1])
            ->andReturn($stmt);

        $this->query->from('users')->where('id = ?', 1)->limit(1);

        $affected = $this->query->execute();

        $this->assertEquals(1, $affected);
    }

    public function test_method_chaining_works(): void {
        $result = $this->query
            ->from('users')
            ->where('status = ?', 'inactive')
            ->limit(10);

        $this->assertInstanceOf(DeleteQuery::class, $result);
    }

    public function test_multiple_where_conditions(): void {
        $this->query
            ->from('users')
            ->where('status = ?', 'inactive')
            ->where('deleted_at IS NOT NULL');

        $sql = $this->query->getQuery();
        $bindings = $this->query->getBindings();

        $this->assertStringContainsString('AND', $sql);
        $this->assertCount(1, $bindings);
    }
}
