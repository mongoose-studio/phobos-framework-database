<?php

namespace PhobosFramework\Database\QueryBuilder\Clauses;

/**
 * Representa la cláusula ORDER BY
 */
class OrderByClause {
    protected array $columns = [];

    /**
     * Agrega columnas para ordenar
     *
     * @param string|array $columns Columnas (ej: 'name ASC' o ['name ASC', 'id DESC'])
     * @return self
     */
    public function addColumns(string|array $columns): self {
        if (is_array($columns)) {
            $this->columns = array_merge($this->columns, $columns);
        } else {
            $this->columns[] = $columns;
        }

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
     * Genera el SQL de la cláusula ORDER BY
     *
     * @return string
     */
    public function toSQL(): string {
        if (empty($this->columns)) {
            return '';
        }

        return 'ORDER BY ' . implode(', ', $this->columns);
    }

    /**
     * Resetea la cláusula
     *
     * @return self
     */
    public function reset(): self {
        $this->columns = [];
        return $this;
    }
}
