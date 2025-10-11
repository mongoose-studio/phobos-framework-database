<?php

namespace PhobosFramework\Database\QueryBuilder\Clauses;

/**
 * Representa cláusulas JOIN
 */
class JoinClause {
    protected array $joins = [];

    /**
     * Agrega un JOIN
     *
     * @param string $table Tabla a joinear
     * @param string $alias Alias de la tabla
     * @param string $condition Condición del join
     * @param string $type Tipo de join
     * @return self
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
     * Verifica si tiene joins
     *
     * @return bool
     */
    public function hasJoins(): bool {
        return !empty($this->joins);
    }

    /**
     * Obtiene los joins
     *
     * @return array
     */
    public function getJoins(): array {
        return $this->joins;
    }

    /**
     * Genera el SQL de las cláusulas JOIN
     *
     * @return string
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
     * Resetea la cláusula
     *
     * @return self
     */
    public function reset(): self {
        $this->joins = [];
        return $this;
    }
}
