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


namespace PhobosFramework\Database\Tests\Unit\Connection;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\Connection\TransactionManager;
use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Drivers\DriverInterface;
use PhobosFramework\Database\Exceptions\TransactionException;
use Mockery;

/**
 * Pruebas para la clase TransactionManager.
 *
 * Esta clase contiene pruebas unitarias para verificar el comportamiento del TransactionManager,
 * incluyendo la funcionalidad para gestionar transacciones de base de datos con puntos de
 * guardado (savepoints) y niveles de transacciones anidadas.
 *
 * Las pruebas verifican:
 * - Inicio y finalización de transacciones raíz
 * - Manejo de transacciones anidadas mediante savepoints
 * - Confirmación (commit) y reversión (rollback) de transacciones
 * - Control de niveles de anidamiento
 * - Manejo de errores y excepciones
 */
class TransactionManagerTest extends TestCase {
    private ConnectionInterface $connection;
    private DriverInterface $driver;
    private TransactionManager $manager;

    protected function setUp(): void {
        $this->driver = Mockery::mock(DriverInterface::class);
        $this->connection = Mockery::mock(ConnectionInterface::class);

        // Default behavior for driver
        $this->driver->shouldReceive('supportsSavepoints')->andReturn(true)->byDefault();
        $this->driver->shouldReceive('getSavepointSQL')
            ->andReturnUsing(fn($name) => "SAVEPOINT $name")
            ->byDefault();
        $this->driver->shouldReceive('getRollbackSavepointSQL')
            ->andReturnUsing(fn($name) => "ROLLBACK TO SAVEPOINT $name")
            ->byDefault();
        $this->driver->shouldReceive('getReleaseSavepointSQL')
            ->andReturnUsing(fn($name) => "RELEASE SAVEPOINT $name")
            ->byDefault();

        $this->connection->shouldReceive('getDriver')->andReturn($this->driver)->byDefault();

        $this->manager = new TransactionManager($this->connection);
    }

    protected function tearDown(): void {
        Mockery::close();
    }

    public function test_begin_starts_root_transaction(): void {
        $this->connection->shouldReceive('beginTransaction')->once();

        $result = $this->manager->begin();

        $this->assertNull($result);
        $this->assertTrue($this->manager->isActive());
        $this->assertEquals(1, $this->manager->getLevel());
    }

    public function test_begin_creates_savepoint_for_nested_transaction(): void {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('execute')->once()->with(Mockery::pattern('/SAVEPOINT/'));

        $this->manager->begin(); // Root transaction
        $savepoint = $this->manager->begin(); // Nested transaction

        $this->assertNotNull($savepoint);
        $this->assertEquals(2, $this->manager->getLevel());
    }

    public function test_commit_commits_root_transaction(): void {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('commit')->once();

        $this->manager->begin();
        $this->manager->commit();

        $this->assertFalse($this->manager->isActive());
        $this->assertEquals(0, $this->manager->getLevel());
    }

    public function test_commit_releases_savepoint_for_nested_transaction(): void {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('execute')->once()->with(Mockery::pattern('/SAVEPOINT/'));
        $this->connection->shouldReceive('execute')->once()->with(Mockery::pattern('/RELEASE SAVEPOINT/'));

        $this->manager->begin();
        $savepoint = $this->manager->begin();
        $this->manager->commit($savepoint);

        $this->assertTrue($this->manager->isActive());
        $this->assertEquals(1, $this->manager->getLevel());
    }

    public function test_commit_throws_exception_when_no_transaction_active(): void {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('No active transaction to commit');

        $this->manager->commit();
    }

    public function test_rollback_rolls_back_root_transaction(): void {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('rollback')->once();

        $this->manager->begin();
        $this->manager->rollback();

        $this->assertFalse($this->manager->isActive());
        $this->assertEquals(0, $this->manager->getLevel());
    }

    public function test_rollback_to_savepoint_for_nested_transaction(): void {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('execute')->once()->with(Mockery::pattern('/SAVEPOINT/'));
        $this->connection->shouldReceive('execute')->once()->with(Mockery::pattern('/ROLLBACK TO SAVEPOINT/'));

        $this->manager->begin();
        $savepoint = $this->manager->begin();
        $this->manager->rollback($savepoint);

        $this->assertTrue($this->manager->isActive());
        $this->assertEquals(2, $this->manager->getLevel());
    }

    public function test_rollback_throws_exception_for_invalid_savepoint(): void {
        $this->connection->shouldReceive('beginTransaction')->once();

        $this->manager->begin();

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('Savepoint \'invalid\' not found');

        $this->manager->rollback('invalid');
    }

    public function test_rollback_throws_exception_when_no_transaction_active(): void {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('No active transaction to rollback');

        $this->manager->rollback();
    }

    public function test_is_active_returns_false_initially(): void {
        $this->assertFalse($this->manager->isActive());
    }

    public function test_get_level_returns_zero_initially(): void {
        $this->assertEquals(0, $this->manager->getLevel());
    }

    public function test_nested_transactions_increment_level(): void {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('execute')->times(2)->with(Mockery::pattern('/SAVEPOINT/'));

        $this->manager->begin(); // Level 1
        $this->assertEquals(1, $this->manager->getLevel());

        $this->manager->begin(); // Level 2
        $this->assertEquals(2, $this->manager->getLevel());

        $this->manager->begin(); // Level 3
        $this->assertEquals(3, $this->manager->getLevel());
    }

    public function test_commit_all_nested_transactions(): void {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('execute')->times(2)->with(Mockery::pattern('/SAVEPOINT/'));
        $this->connection->shouldReceive('execute')->times(2)->with(Mockery::pattern('/RELEASE SAVEPOINT/'));
        $this->connection->shouldReceive('commit')->once();

        $this->manager->begin();
        $sp1 = $this->manager->begin();
        $sp2 = $this->manager->begin();

        $this->manager->commit($sp2);
        $this->manager->commit($sp1);
        $this->manager->commit();

        $this->assertFalse($this->manager->isActive());
    }

    public function test_rollback_nested_resets_to_correct_level(): void {
        $this->connection->shouldReceive('beginTransaction')->once();
        $this->connection->shouldReceive('execute')->times(2)->with(Mockery::pattern('/SAVEPOINT/'));
        $this->connection->shouldReceive('execute')->once()->with(Mockery::pattern('/ROLLBACK TO SAVEPOINT/'));

        $this->manager->begin();
        $sp1 = $this->manager->begin();
        $this->manager->begin();

        $this->manager->rollback($sp1);

        $this->assertEquals(2, $this->manager->getLevel());
    }
}
