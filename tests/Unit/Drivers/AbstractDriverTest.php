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

namespace PhobosFramework\Database\Tests\Unit\Drivers;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\Drivers\AbstractDriver;
use PhobosFramework\Database\Exceptions\ConfigurationException;
use Mockery;
use PDO;

/**
 * Pruebas unitarias para la clase AbstractDriver.
 *
 * Esta clase de prueba verifica el comportamiento y la funcionalidad de la implementación
 * de AbstractDriver mediante la prueba de varios métodos y asegurando que cumplan con los
 * comportamientos esperados de interacción con la base de datos, incluyendo el manejo de
 * opciones PDO, puntos de guardado, niveles de aislamiento y recuperación del último ID insertado.
 */
class AbstractDriverTest extends TestCase {
    private AbstractDriver $driver;
    private PDO $pdo;

    protected function setUp(): void {
        // Create anonymous class to test abstract driver
        $this->driver = new class extends AbstractDriver {
            public function getDSN(array $config): string {
                return 'test:host=localhost';
            }

            public function quoteIdentifier(string $identifier): string {
                return "`$identifier`";
            }

            public function getName(): string {
                return 'test';
            }
        };

        $this->pdo = Mockery::mock(PDO::class);
    }

    protected function tearDown(): void {
        Mockery::close();
    }

    public function test_get_pdo_options_returns_default_options(): void {
        $options = $this->driver->getPDOOptions([]);

        $this->assertIsArray($options);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $options[PDO::ATTR_ERRMODE]);
        $this->assertEquals(PDO::FETCH_ASSOC, $options[PDO::ATTR_DEFAULT_FETCH_MODE]);
        $this->assertFalse($options[PDO::ATTR_EMULATE_PREPARES]);
        $this->assertFalse($options[PDO::ATTR_STRINGIFY_FETCHES]);
    }

    public function test_get_pdo_options_merges_custom_options(): void {
        $config = [
            'options' => [
                PDO::ATTR_TIMEOUT => 10,
                PDO::ATTR_PERSISTENT => true
            ]
        ];

        $options = $this->driver->getPDOOptions($config);

        $this->assertEquals(10, $options[PDO::ATTR_TIMEOUT]);
        $this->assertTrue($options[PDO::ATTR_PERSISTENT]);
        // Default options should still be present
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $options[PDO::ATTR_ERRMODE]);
    }

    public function test_configure_throws_exception_for_invalid_charset(): void {
        $config = ['charset' => 'utf8; DROP TABLE users'];

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid charset');

        $this->driver->configure($this->pdo, $config);
    }

    public function test_supports_savepoints_returns_true(): void {
        $this->assertTrue($this->driver->supportsSavepoints());
    }

    public function test_get_savepoint_sql_returns_correct_syntax(): void {
        $sql = $this->driver->getSavepointSQL('sp1');

        $this->assertEquals('SAVEPOINT `sp1`', $sql);
    }

    public function test_get_rollback_savepoint_sql_returns_correct_syntax(): void {
        $sql = $this->driver->getRollbackSavepointSQL('sp1');

        $this->assertEquals('ROLLBACK TO SAVEPOINT `sp1`', $sql);
    }

    public function test_get_release_savepoint_sql_returns_correct_syntax(): void {
        $sql = $this->driver->getReleaseSavepointSQL('sp1');

        $this->assertEquals('RELEASE SAVEPOINT `sp1`', $sql);
    }

    public function test_get_set_isolation_level_sql_returns_correct_syntax(): void {
        $sql = $this->driver->getSetIsolationLevelSQL('READ COMMITTED');

        $this->assertEquals('SET TRANSACTION ISOLATION LEVEL READ COMMITTED', $sql);
    }

    public function test_get_last_insert_id_calls_pdo_method(): void {
        $this->pdo
            ->shouldReceive('lastInsertId')
            ->once()
            ->with(null)
            ->andReturn('123');

        $id = $this->driver->getLastInsertId($this->pdo);

        $this->assertEquals('123', $id);
    }

    public function test_get_last_insert_id_with_sequence(): void {
        $this->pdo
            ->shouldReceive('lastInsertId')
            ->once()
            ->with('user_id_seq')
            ->andReturn('456');

        $id = $this->driver->getLastInsertId($this->pdo, 'user_id_seq');

        $this->assertEquals('456', $id);
    }

    public function test_isolation_level_constants_are_defined(): void {
        $this->assertEquals('READ UNCOMMITTED', AbstractDriver::ISOLATION_READ_UNCOMMITTED);
        $this->assertEquals('READ COMMITTED', AbstractDriver::ISOLATION_READ_COMMITTED);
        $this->assertEquals('REPEATABLE READ', AbstractDriver::ISOLATION_REPEATABLE_READ);
        $this->assertEquals('SERIALIZABLE', AbstractDriver::ISOLATION_SERIALIZABLE);
    }
}
