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

namespace PhobosFramework\Database\QueryBuilder\Clauses;

use PhobosFramework\Database\Exceptions\InvalidArgumentException;
use PhobosFramework\Database\QueryBuilder\Grammar\Grammar;

/**
 * Representa cláusulas JOIN en consultas SQL
 *
 * Esta clase maneja la construcción de cláusulas JOIN para consultas SQL,
 * permitiendo agregar diferentes tipos de JOIN (INNER, LEFT, RIGHT, etc.)
 * con sus respectivas condiciones y alias.
 */
class JoinClause {
    /**
     * Tipos de JOIN válidos soportados
     */
    private const array VALID_JOIN_TYPES = [
        'INNER',
        'LEFT',
        'RIGHT',
        'FULL',
        'CROSS',
        'LEFT OUTER',
        'RIGHT OUTER',
        'FULL OUTER'
    ];
    protected array $joins = [];

    /**
     * Agrega una cláusula JOIN a la consulta
     *
     * @param string $table Nombre de la tabla que se va a unir
     * @param string $alias Alias para referenciar la tabla en la consulta
     * @param string $condition Condición que define cómo se relacionan las tablas
     * @param string $type Tipo de JOIN (INNER, LEFT, RIGHT, FULL, etc.)
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @throws InvalidArgumentException Si el tipo de JOIN no es válido
     */
    public function addJoin(string $table, string $alias, string $condition, string $type = 'INNER'): self {
        $normalizedType = strtoupper(trim($type));

        if (!in_array($normalizedType, self::VALID_JOIN_TYPES, true)) {
            throw new InvalidArgumentException(
                "Invalid JOIN type '$type'. Valid types are: " .
                implode(', ', self::VALID_JOIN_TYPES)
            );
        }

        $this->joins[] = [
            'table' => $table,
            'alias' => $alias,
            'condition' => $condition,
            'type' => $normalizedType
        ];

        return $this;
    }

    /**
     * Verifica si existen cláusulas JOIN definidas
     *
     * @return bool Retorna true si hay JOINs agregados, false en caso contrario
     */
    public function hasJoins(): bool {
        return !empty($this->joins);
    }

    /**
     * Obtiene todas las cláusulas JOIN definidas
     *
     * @return array Retorna un array con todos los JOINs configurados
     * @noinspection PhpUnused
     */
    public function getJoins(): array {
        return $this->joins;
    }

    /**
     * Genera la sentencia SQL correspondiente a todas las cláusulas JOIN
     *
     * @param Grammar $grammar Gramática del dialecto para citar identificadores
     * @return string Retorna la cadena SQL con todos los JOINs concatenados
     */
    public function toSQL(Grammar $grammar): string {
        if (empty($this->joins)) {
            return '';
        }

        $parts = [];

        foreach ($this->joins as $join) {
            // La tabla y el alias se citan; la condición ON es SQL crudo del desarrollador.
            $table = $grammar->wrapTable($join['table'], $join['alias'] ?: null);

            $parts[] = "{$join['type']} JOIN $table ON {$join['condition']}";
        }

        return implode(' ', $parts);
    }

    /**
     * Elimina todas las cláusulas JOIN definidas
     *
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function reset(): self {
        $this->joins = [];
        return $this;
    }
}
