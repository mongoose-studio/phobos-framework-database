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

/**
 * Representa cláusulas JOIN en consultas SQL
 *
 * Esta clase maneja la construcción de cláusulas JOIN para consultas SQL,
 * permitiendo agregar diferentes tipos de JOIN (INNER, LEFT, RIGHT, etc.)
 * con sus respectivas condiciones y alias.
 */
class JoinClause {
    protected array $joins = [];

    /**
     * Agrega una cláusula JOIN a la consulta
     *
     * @param string $table Nombre de la tabla que se va a unir
     * @param string $alias Alias para referenciar la tabla en la consulta
     * @param string $condition Condición que define cómo se relacionan las tablas
     * @param string $type Tipo de JOIN (INNER, LEFT, RIGHT, FULL, etc.)
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function addJoin(string $table, string $alias, string $condition, string $type = 'INNER'): self {
        $this->joins[] = [
            'table' => $table,
            'alias' => $alias,
            'condition' => $condition,
            'type' => strtoupper($type)
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
     * @return string Retorna la cadena SQL con todos los JOINs concatenados
     */
    public function toSQL(): string {
        if (empty($this->joins)) {
            return '';
        }

        $parts = [];

        foreach ($this->joins as $join) {
            $sql = "{$join['type']} JOIN {$join['table']}";

            if (!empty($join['alias'])) {
                $sql .= " AS {$join['alias']}";
            }

            $sql .= " ON {$join['condition']}";

            $parts[] = $sql;
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
