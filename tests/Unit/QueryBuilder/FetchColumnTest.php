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

use Mockery;
use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\QueryBuilder\Grammar\AnsiGrammar;
use PhobosFramework\Database\QueryBuilder\QueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * fetchColumn() debe funcionar tanto si la fila llega como array (FETCH_ASSOC/BOTH) como
 * si llega como objeto (FETCH_OBJ).
 *
 * El skeleton recomendado configura las conexiones con PDO::FETCH_OBJ, así que las filas
 * son stdClass. count() y exists() se apoyan en fetchColumn(); antes del fix, con FETCH_OBJ
 * reventaban con "Cannot use object of type stdClass as array" contra MySQL y PostgreSQL.
 */
class FetchColumnTest extends TestCase {

    private function builder(array|object|null $row): QueryBuilder {
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('getDriver->getGrammar')->andReturn(new AnsiGrammar());
        $connection->shouldReceive('queryFirst')->andReturn($row);

        return new QueryBuilder($connection)->select('COUNT(*) as total')->from('pedidos');
    }

    protected function tearDown(): void {
        Mockery::close();
    }

    public function test_fetch_column_por_nombre_desde_objeto(): void {
        $qb = $this->builder((object)['total' => 7]);

        $this->assertSame(7, $qb->fetchColumn('total'));
    }

    public function test_fetch_column_por_nombre_desde_array(): void {
        $qb = $this->builder(['total' => 7]);

        $this->assertSame(7, $qb->fetchColumn('total'));
    }

    public function test_fetch_column_por_posicion_desde_objeto(): void {
        $qb = $this->builder((object)['total' => 7]);

        $this->assertSame(7, $qb->fetchColumn(0));
    }

    public function test_fetch_column_sin_filas_devuelve_null(): void {
        $qb = $this->builder(null);

        $this->assertNull($qb->fetchColumn('total'));
    }

    public function test_columna_inexistente_devuelve_null(): void {
        $qb = $this->builder((object)['total' => 7]);

        $this->assertNull($qb->fetchColumn('no_existe'));
    }
}