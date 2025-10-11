<?php

namespace PhobosFramework\Database\QueryBuilder\Clauses;

/**
 * Representa la cláusula WHERE
 */
class WhereClause {
    protected array $conditions = [];
    protected array $bindings = [];

    /**
     * Agrega una condición WHERE
     *
     * @param array|string $conditions Condiciones
     * @param string $operator Operador lógico (AND/OR)
     * @param mixed ...$params Parámetros adicionales
     * @return self
     */
    public function addCondition(array|string $conditions, string $operator = 'AND', mixed ...$params): self {
        if (is_array($conditions)) {
            $this->addArrayConditions($conditions, $operator);
        } else {
            $this->addStringCondition($conditions, $operator, $params);
        }

        return $this;
    }

    /**
     * Agrega condiciones desde un array
     *
     * Soporta formato:
     * ['column = ?' => value]
     * ['column > ?' => value]
     * ['column IN (?)' => [1,2,3]]
     *
     * @param array $conditions
     * @param string $operator
     * @return void
     */
    protected function addArrayConditions(array $conditions, string $operator): void {
        foreach ($conditions as $condition => $value) {
            // Si la key es numérica, asumir que es una condición raw
            if (is_numeric($condition)) {
                $this->conditions[] = [
                    'sql' => $value,
                    'operator' => $operator,
                    'bindings' => []
                ];
                continue;
            }

            // Procesar condición con placeholder
            if (strpos($condition, '?') !== false) {
                // Manejar IN clause con arrays
                if (is_array($value) && stripos($condition, 'IN') !== false) {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $sql = str_replace('?', $placeholders, $condition);
                    $bindings = $value;
                } else {
                    $sql = $condition;
                    $bindings = is_array($value) ? $value : [$value];
                }

                $this->conditions[] = [
                    'sql' => $sql,
                    'operator' => $operator,
                    'bindings' => $bindings
                ];

                $this->bindings = array_merge($this->bindings, $bindings);
            } else {
                // Condición sin placeholder, agregar = ?
                $this->conditions[] = [
                    'sql' => $condition . ' = ?',
                    'operator' => $operator,
                    'bindings' => [$value]
                ];

                $this->bindings[] = $value;
            }
        }
    }

    /**
     * Agrega una condición string
     *
     * @param string $condition
     * @param string $operator
     * @param array $params
     * @return void
     */
    protected function addStringCondition(string $condition, string $operator, array $params): void {
        $this->conditions[] = [
            'sql' => $condition,
            'operator' => $operator,
            'bindings' => $params
        ];

        $this->bindings = array_merge($this->bindings, $params);
    }

    /**
     * Verifica si tiene condiciones
     *
     * @return bool
     */
    public function hasConditions(): bool {
        return !empty($this->conditions);
    }

    /**
     * Obtiene los bindings
     *
     * @return array
     */
    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Genera el SQL de la cláusula WHERE
     *
     * @return string
     */
    public function toSQL(): string {
        if (empty($this->conditions)) {
            return '';
        }

        $sql = 'WHERE ';
        $parts = [];

        foreach ($this->conditions as $index => $condition) {
            if ($index > 0) {
                $parts[] = $condition['operator'];
            }
            $parts[] = $condition['sql'];
        }

        $sql .= implode(' ', $parts);

        return $sql;
    }

    /**
     * Resetea la cláusula
     *
     * @return self
     */
    public function reset(): self {
        $this->conditions = [];
        $this->bindings = [];
        return $this;
    }
}
