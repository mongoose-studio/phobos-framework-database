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

namespace PhobosFramework\Database\Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use PhobosFramework\Database\Support\Uuid;

/**
 * Pruebas del generador UUIDv7 (RFC 9562).
 *
 * Verifica el formato canónico, los bits de versión/variante, la unicidad y el
 * ordenamiento temporal (monotonicidad) que hace a UUIDv7 apto para claves primarias.
 */
class UuidTest extends TestCase {

    public function test_has_canonical_36_char_format(): void {
        $uuid = Uuid::v7();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    public function test_version_nibble_is_7(): void {
        $uuid = Uuid::v7();

        // El primer carácter del tercer grupo es la versión.
        $this->assertSame('7', $uuid[14]);
    }

    public function test_variant_is_rfc_4122(): void {
        $uuid = Uuid::v7();

        // El primer carácter del cuarto grupo codifica la variante (10xx => 8,9,a,b).
        $this->assertContains(strtolower($uuid[19]), ['8', '9', 'a', 'b']);
    }

    public function test_generates_unique_values(): void {
        $count = 1000;
        $set = [];

        for ($i = 0; $i < $count; $i++) {
            $set[Uuid::v7()] = true;
        }

        $this->assertCount($count, $set);
    }

    public function test_is_time_ordered(): void {
        $previous = Uuid::v7();

        for ($i = 0; $i < 50; $i++) {
            // Pequeña pausa para asegurar avance del reloj en milisegundos.
            usleep(1200);
            $current = Uuid::v7();

            $this->assertGreaterThan(
                $previous,
                $current,
                'Cada UUIDv7 generado más tarde debe ordenar después del anterior'
            );

            $previous = $current;
        }
    }
}
