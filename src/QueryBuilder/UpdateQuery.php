<?php

namespace PhobosFramework\Database\QueryBuilder;

use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\QueryBuilder\Clauses\WhereClause;

/**
 * Query Builder para UPDATE
 */
class UpdateQuery {
    protected ConnectionInterface $connection;
    protected ?string $table = null;
    protected array $data = [];
    protected WhereClause $whereClause;

    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection ?? ConnectionManager::getInstance()->getConnection();
        $this->whereClause = new WhereClause();
    }

    /**
     * Establece la tabla
     */
    public function table(string $table): self {
        $this->table = $table;
        return $this;
    }

    /**
     * Establece los datos a actualizar
     */
    public function set(array $data): self {
        $this->data = $data;
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
     * Genera el SQL
     */
    public function getQuery(): string {
        $setParts = [];
        foreach (array_keys($this->data) as $column) {
            $setParts[] = "{$column} = ?";
        }
        $setStr = implode(', ', $setParts);

        /**
         * @noinspection SqlNoDataSourceInspection
         * @noinspection SqlWithoutWhere
         */
        $sql = "UPDATE {$this->table} SET {$setStr}";

        if ($this->whereClause->hasConditions()) {
            $sql .= ' ' . $this->whereClause->toSQL();
        }

        return $sql;
    }

    /**
     * Genera el SQL con los bindings
     */
    public function getQueryWithBindings(): array {
        return [
            'query' => $this->getQuery(),
            'bindings' => $this->getBindings()
        ];
    }

    /**
     * Obtiene los bindings
     */
    public function getBindings(): array {
        // Primero los valores del SET
        $bindings = array_values($this->data);

        // Luego los del WHERE
        $bindings = array_merge($bindings, $this->whereClause->getBindings());

        return $bindings;
    }

    /**
     * Ejecuta el UPDATE
     */
    public function execute(): int {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();

        $stmt = $this->connection->execute($sql, $bindings);
        return $stmt->rowCount();
    }
}
