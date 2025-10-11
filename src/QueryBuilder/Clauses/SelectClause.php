<?php

namespace PhobosFramework\Database\QueryBuilder\Clauses;

/**
 * Representa la cláusula SELECT
 */
class SelectClause {
    protected array $columns = [];
    protected bool $distinct = false;

    /**
     * Agrega columnas al SELECT
     *
     * @param string|array ...$columns
     * @return self
     */
    public function addColumns(string|array ...$columns): self {
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->columns = array_merge($this->columns, $column);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Establece DISTINCT
     *
     * @param bool $distinct
     * @return self
     */
    public function setDistinct(bool $distinct = true): self {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Verifica si tiene columnas
     *
     * @return bool
     */
    public function hasColumns(): bool {
        return !empty($this->columns);
    }

    /**
     * Obtiene las columnas
     *
     * @return array
     */
    public function getColumns(): array {
        return $this->columns;
    }

    /**
     * Genera el SQL de la cláusula SELECT
     *
     * @return string
     */
    public function toSQL(): string {
        if (empty($this->columns)) {
            $columns = '*';
        } else {
            $columns = implode(', ', $this->columns);
        }

        $sql = 'SELECT';

        if ($this->distinct) {
            $sql .= ' DISTINCT';
        }

        $sql .= ' ' . $columns;

        return $sql;
    }

    /**
     * Resetea la cláusula
     *
     * @return self
     */
    public function reset(): self {
        $this->columns = [];
        $this->distinct = false;
        return $this;
    }
}
