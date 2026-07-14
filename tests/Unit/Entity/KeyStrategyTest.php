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

namespace PhobosFramework\Database\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Entity\TableEntity;
use PhobosFramework\Database\QueryBuilder\Grammar\AnsiGrammar;
use Mockery;

/**
 * Pruebas de la estrategia de clave primaria (`$keyStrategy`) en performInsert().
 *
 * Cubre las tres estrategias (auto/uuidv7/manual) y la regla de override:
 * un PK ya seteado siempre gana, sin generar ni releer.
 */
class KeyStrategyTest extends TestCase {

    protected function tearDown(): void {
        Mockery::close();
    }

    private function mockConnection(?string $lastInsertId = null): ConnectionInterface {
        $conn = Mockery::mock(ConnectionInterface::class);
        $conn->shouldReceive('getDriver->getGrammar')->andReturn(new AnsiGrammar());
        $conn->shouldReceive('execute')->andReturn(Mockery::mock(\PDOStatement::class));

        if ($lastInsertId !== null) {
            $conn->shouldReceive('lastInsertId')->andReturn($lastInsertId);
        }

        return $conn;
    }

    public function test_auto_strategy_reads_generated_id_via_last_insert_id(): void {
        AutoKeyEntity::$conn = $this->mockConnection('77');

        $entity = new AutoKeyEntity();
        $entity->name = 'Widget';
        $entity->save();

        $this->assertSame('77', $entity->id);
    }

    public function test_uuidv7_strategy_generates_id_in_php(): void {
        UuidKeyEntity::$conn = $this->mockConnection();

        $entity = new UuidKeyEntity();
        $entity->name = 'Widget';
        $entity->save();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $entity->id
        );
    }

    public function test_manual_strategy_keeps_assigned_id(): void {
        ManualKeyEntity::$conn = $this->mockConnection();

        $entity = new ManualKeyEntity();
        $entity->id = 'custom-1';
        $entity->name = 'Widget';
        $entity->save();

        $this->assertSame('custom-1', $entity->id);
    }

    public function test_preset_pk_always_wins_over_uuidv7(): void {
        UuidKeyEntity::$conn = $this->mockConnection();

        $entity = new UuidKeyEntity();
        $entity->id = 'preset-id';
        $entity->name = 'Widget';
        $entity->save();

        $this->assertSame('preset-id', $entity->id);
    }
}

// Test fixtures

class AutoKeyEntity extends TableEntity {
    protected static ?string $schema = null;
    protected static string $entity = 'items';
    protected static array $pk = ['id'];
    protected static string $keyStrategy = 'auto';

    public mixed $id = null;
    public mixed $name = null;

    public static ?ConnectionInterface $conn = null;

    protected static function getConnection(): ConnectionInterface {
        return static::$conn;
    }
}

class UuidKeyEntity extends TableEntity {
    protected static ?string $schema = null;
    protected static string $entity = 'items';
    protected static array $pk = ['id'];
    protected static string $keyStrategy = 'uuidv7';

    public mixed $id = null;
    public mixed $name = null;

    public static ?ConnectionInterface $conn = null;

    protected static function getConnection(): ConnectionInterface {
        return static::$conn;
    }
}

class ManualKeyEntity extends TableEntity {
    protected static ?string $schema = null;
    protected static string $entity = 'items';
    protected static array $pk = ['id'];
    protected static string $keyStrategy = 'manual';

    public mixed $id = null;
    public mixed $name = null;

    public static ?ConnectionInterface $conn = null;

    protected static function getConnection(): ConnectionInterface {
        return static::$conn;
    }
}
