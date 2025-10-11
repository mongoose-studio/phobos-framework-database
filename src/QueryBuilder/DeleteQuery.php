<?php

namespace PhobosFramework\Database\QueryBuilder;

use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\QueryBuilder\Clauses\WhereClause;

/**
 * Query Builder para DELETE
 */
class DeleteQuery {
    protected ConnectionInterface $connection;
    protected ?string $table = null;
    protected WhereClause $whereClause;
    protected ?int $limit = null;

    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection ?? ConnectionManager::getInstance()->getConnection();
        $this->whereClause = new WhereClause();
    }

    /**
     * Establece la tabla
     */
    public function from(string $table): self {
        $this->table = $table;
        return $this;
    }

    /**
     * Agrega condiciones WHERE
     */
    public function where(array|string $conditions, mixed ...$params): self {
        $this->whereClause->addCondition($conditions, 'AND', ...$params);
        return $this;
    }

    /**
     * Establece LIMIT
     */
    public function limit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Genera el SQL
     */
    public function getQuery(): string {
        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlWithoutWhere */
        $sql = "DELETE FROM {$this->table}";

        if ($this->whereClause->hasConditions()) {
            $sql .= ' ' . $this->whereClause->toSQL();
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        return $sql;
    }

    /**
     * Obtiene los bindings
     */
    public function getBindings(): array {
        return $this->whereClause->getBindings();
    }

    /**
     * Ejecuta el DELETE
     */
    public function execute(): int {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();

        $stmt = $this->connection->execute($sql, $bindings);
        return $stmt->rowCount();
    }
}
