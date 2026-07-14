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
 * Pruebas de la semántica de paginación de TableEntity::find().
 *
 * Regresión: los parámetros se llamaban $limitFrom / $limitTo (que sugieren un rango
 * "FROM x TO y"), pero se pasaban tal cual a QueryBuilder::limit(int $limit, ?int $offset).
 * Es decir, el primero era el LIMIT y el segundo el OFFSET: la semántica opuesta a la
 * que anunciaban los nombres y los docblocks.
 *
 * Consecuencia: `find([], null, 0, 20)` ("dame los primeros 20") generaba
 * `LIMIT 0 OFFSET 20` y devolvía un array vacío, sin error, con la tabla llena.
 */
class TableEntityFindTest extends TestCase {

    protected function setUp(): void {
        $conn = Mockery::mock(ConnectionInterface::class);
        $conn->shouldReceive('getDriver->getGrammar')->andReturn(new AnsiGrammar());
        FindTestEntity::$conn = $conn;
    }

    protected function tearDown(): void {
        Mockery::close();
    }

    public function test_find_applies_first_argument_as_limit(): void {
        $result = FindTestEntity::find([], null, 20, null, true);

        $this->assertStringContainsString('LIMIT 20', $result['query']);
        $this->assertStringNotContainsString('OFFSET', $result['query']);
    }

    public function test_find_applies_second_argument_as_offset(): void {
        $result = FindTestEntity::find([], null, 20, 40, true);

        $this->assertStringContainsString('LIMIT 20', $result['query']);
        $this->assertStringContainsString('OFFSET 40', $result['query']);
    }

    /**
     * El caso exacto del informe: "quiero los primeros 20".
     */
    public function test_find_first_page_does_not_invert_limit_and_offset(): void {
        $result = FindTestEntity::find([], 'created_at DESC', 20, 0, true);

        $this->assertStringContainsString('LIMIT 20', $result['query']);
        $this->assertStringContainsString('OFFSET 0', $result['query']);
        $this->assertStringNotContainsString('LIMIT 0', $result['query']);
    }

    public function test_find_without_limit_emits_no_limit_clause(): void {
        $result = FindTestEntity::find([], null, null, null, true);

        $this->assertStringNotContainsString('LIMIT', $result['query']);
        $this->assertStringNotContainsString('OFFSET', $result['query']);
    }

    /**
     * Un offset sin límite no es SQL válido, así que se ignora: LIMIT es el que manda.
     */
    public function test_offset_without_limit_is_ignored(): void {
        $result = FindTestEntity::find([], null, null, 40, true);

        $this->assertStringNotContainsString('OFFSET', $result['query']);
    }
}

// Test fixtures

class FindTestEntity extends TableEntity {
    protected static ?string $schema = 'testdb';
    protected static string $entity = 'pedidos';
    protected static array $pk = ['id'];

    public static ?ConnectionInterface $conn = null;

    protected static function getConnection(): ConnectionInterface {
        return static::$conn;
    }
}