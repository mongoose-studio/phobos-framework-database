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

namespace PhobosFramework\Database\Support;

use Random\RandomException;

/**
 * Generador de UUID versión 7 (RFC 9562), sin dependencias externas.
 *
 * UUIDv7 antepone un timestamp Unix en milisegundos (48 bits) al azar, de modo que los
 * identificadores quedan ordenados por tiempo de creación. Eso mantiene los índices
 * contiguos (a diferencia de un UUIDv4 puramente aleatorio) y funciona idéntico en
 * MySQL, PostgreSQL y SQLite guardándolo como texto/uuid nativo.
 *
 * Layout (128 bits):
 * - 48 bits: unix_ts_ms
 * - 4 bits:  versión (0111 = 7)
 * - 12 bits: rand_a
 * - 2 bits:  variante (10)
 * - 62 bits: rand_b
 */
final class Uuid {

    /**
     * Genera un UUIDv7 en su representación canónica de 36 caracteres.
     *
     * @return string UUID en formato `xxxxxxxx-xxxx-7xxx-yxxx-xxxxxxxxxxxx`
     * @throws RandomException Si no hay una fuente de aleatoriedad disponible
     */
    public static function v7(): string {
        // 48 bits de timestamp Unix en milisegundos, big-endian (los 6 bytes bajos de J)
        $timeMs = (int)(microtime(true) * 1000);
        $bytes = substr(pack('J', $timeMs), 2, 6) . random_bytes(10);

        // Versión 7 en el nibble alto del byte 6
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x70);
        // Variante RFC 4122 (10xx) en los 2 bits altos del byte 8
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
