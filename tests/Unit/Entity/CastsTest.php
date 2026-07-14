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

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\Entity\TableEntity;

/**
 * Pruebas del casteo de atributos (`$casts`) en EntityManager.
 *
 * Verifica la conversión bidireccional entre la representación en la base de datos
 * y el tipo nativo en PHP para json/bool/int/float/datetime, incluyendo los formatos
 * booleanos propios de PostgreSQL (`t`/`f`).
 */
class CastsTest extends TestCase {

    public function test_casts_json_from_database_to_array(): void {
        $entity = CastTestEntity::fromDatabase(['meta' => '{"role":"admin","level":3}']);

        $this->assertSame(['role' => 'admin', 'level' => 3], $entity->meta);
    }

    public function test_casts_json_to_database_as_string(): void {
        $entity = CastTestEntity::fromDatabase([]);
        $entity->meta = ['role' => 'admin'];

        $this->assertSame('{"role":"admin"}', $entity->toArray()['meta']);
    }

    public function test_casts_postgres_boolean_true(): void {
        $entity = CastTestEntity::fromDatabase(['active' => 't']);

        $this->assertTrue($entity->active);
    }

    public function test_casts_postgres_boolean_false(): void {
        $entity = CastTestEntity::fromDatabase(['active' => 'f']);

        $this->assertFalse($entity->active);
    }

    public function test_casts_boolean_to_database_as_int(): void {
        // Se persiste como 1/0 (no como bool nativo): PDO bindearía `false` como cadena
        // vacía, que PostgreSQL rechaza. 1/0 es válido en MySQL, SQLite y PostgreSQL.
        $entity = CastTestEntity::fromDatabase([]);

        $entity->active = true;
        $this->assertSame(1, $entity->toArray()['active']);

        $entity->active = false;
        $this->assertSame(0, $entity->toArray()['active']);
    }

    public function test_casts_numeric_boolean(): void {
        $this->assertTrue(CastTestEntity::fromDatabase(['active' => '1'])->active);
        $this->assertFalse(CastTestEntity::fromDatabase(['active' => '0'])->active);
    }

    public function test_casts_int_and_float(): void {
        $entity = CastTestEntity::fromDatabase(['qty' => '42', 'price' => '19.99']);

        $this->assertSame(42, $entity->qty);
        $this->assertSame(19.99, $entity->price);
    }

    public function test_casts_datetime_from_database(): void {
        $entity = CastTestEntity::fromDatabase(['created_at' => '2024-01-15 10:30:00']);

        $this->assertInstanceOf(DateTimeImmutable::class, $entity->created_at);
        $this->assertSame('2024-01-15 10:30:00', $entity->created_at->format('Y-m-d H:i:s'));
    }

    public function test_casts_datetime_to_database_as_string(): void {
        $entity = CastTestEntity::fromDatabase([]);
        $entity->created_at = new DateTimeImmutable('2024-01-15 10:30:00');

        $this->assertSame('2024-01-15 10:30:00', $entity->toArray()['created_at']);
    }

    public function test_null_values_are_not_casted(): void {
        $entity = CastTestEntity::fromDatabase(['meta' => null, 'active' => null]);

        $this->assertNull($entity->meta);
        $this->assertNull($entity->active);
    }

    public function test_uncasted_columns_pass_through(): void {
        $entity = CastTestEntity::fromDatabase(['name' => 'Widget']);

        $this->assertSame('Widget', $entity->name);
    }

    public function test_hydrated_cast_entity_is_not_dirty_without_changes(): void {
        // Regresión: con casts, una entidad recién cargada marcaba como sucios los
        // campos JSONB/boolean porque _original guardaba la fila cruda ('t', json del
        // motor) y toArray() reserializaba a otra forma. Ahora _original queda en forma
        // canónica y una carga sin cambios no debe reportar nada sucio.
        $entity = CastTestEntity::fromDatabase([
            'id' => 1,
            'meta' => '{"role":"admin","level":3}',
            'active' => 't',
            'qty' => '5',
            'price' => '1.5',
            'created_at' => '2024-01-15 10:30:00',
        ]);

        $entity->detectChanges();

        $this->assertFalse($entity->isDirty());
    }

    public function test_real_change_on_cast_field_is_detected(): void {
        $entity = CastTestEntity::fromDatabase([
            'id' => 1,
            'meta' => '{"role":"admin"}',
            'active' => 't',
        ]);

        $entity->meta = ['role' => 'user'];
        $entity->detectChanges();

        $this->assertTrue($entity->isDirty());
        $this->assertContains('meta', $entity->getDirtyFields());
    }
}

// Test fixture

class CastTestEntity extends TableEntity {
    protected static ?string $schema = null;
    protected static string $entity = 'items';
    protected static array $pk = ['id'];
    protected static array $casts = [
        'meta' => 'json',
        'active' => 'bool',
        'qty' => 'int',
        'price' => 'float',
        'created_at' => 'datetime',
    ];

    public ?int $id = null;
    public mixed $meta = null;
    public mixed $active = null;
    public mixed $qty = null;
    public mixed $price = null;
    public mixed $created_at = null;
    public mixed $name = null;

    public static function fromDatabase(array $data): static {
        return static::hydrate($data);
    }
}
