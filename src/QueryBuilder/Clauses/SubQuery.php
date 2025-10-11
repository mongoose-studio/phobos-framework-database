<?php

namespace PhobosFramework\Database\QueryBuilder;

/**
 * Representa una subquery para usar dentro de otra query
 */
class SubQuery {
    protected QueryBuilder $query;
    protected ?string $alias = null;

    /**
     * Constructor
     */
    public function __construct(QueryBuilder $query, ?string $alias = null) {
        $this->query = $query;
        $this->alias = $alias;
    }

    /**
     * Obtiene el SQL de la subquery
     */
    public function toSQL(): string {
        $sql = '(' . $this->query->getQuery() . ')';

        if ($this->alias !== null) {
            $sql .= ' AS ' . $this->alias;
        }

        return $sql;
    }

    /**
     * Obtiene los bindings de la subquery
     */
    public function getBindings(): array {
        return $this->query->getBindings();
    }

    /**
     * Obtiene el alias
     */
    public function getAlias(): ?string {
        return $this->alias;
    }

    /**
     * Obtiene el QueryBuilder subyacente
     */
    public function getQuery(): QueryBuilder {
        return $this->query;
    }

    /**
     * Para usar como string
     */
    public function __toString(): string {
        return $this->toSQL();
    }
}
