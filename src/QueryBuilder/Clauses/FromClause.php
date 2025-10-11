<?php

namespace PhobosFramework\Database\QueryBuilder\Clauses;

/**
 * Representa la cláusula FROM
 */
class FromClause {
    protected ?string $table = null;
    protected ?string $alias = null;

    /**
     * Establece la tabla
     *
     * @param string $table Nombre de la tabla
     * @param string|null $alias Alias de la tabla
     * @return self
     */
    public function setTable(string $table, ?string $alias = null): self {
        $this->table = $table;
        $this->alias = $alias;
        return $this;
    }

    /**
     * Obtiene la tabla
     *
     * @return string|null
     */
    public function getTable(): ?string {
        return $this->table;
    }

    /**
     * Obtiene el alias
     *
     * @return string|null
     */
    public function getAlias(): ?string {
        return $this->alias;
    }

    /**
     * Verifica si tiene tabla
     *
     * @return bool
     */
    public function hasTable(): bool {
        return $this->table !== null;
    }

    /**
     * Genera el SQL de la cláusula FROM
     *
     * @return string
     */
    public function toSQL(): string {
        if ($this->table === null) {
            return '';
        }

        $sql = 'FROM ' . $this->table;

        if ($this->alias !== null) {
            $sql .= ' AS ' . $this->alias;
        }

        return $sql;
    }

    /**
     * Resetea la cláusula
     *
     * @return self
     */
    public function reset(): self {
        $this->table = null;
        $this->alias = null;
        return $this;
    }
}
