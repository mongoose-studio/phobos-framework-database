<?php

namespace PhobosFramework\Database\QueryBuilder;

use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\QueryBuilder\Clauses\SelectClause;
use PhobosFramework\Database\QueryBuilder\Clauses\FromClause;
use PhobosFramework\Database\QueryBuilder\Clauses\WhereClause;
use PhobosFramework\Database\QueryBuilder\Clauses\JoinClause;
use PhobosFramework\Database\QueryBuilder\Clauses\OrderByClause;
use PhobosFramework\Database\QueryBuilder\Clauses\GroupByClause;
use PhobosFramework\Database\QueryBuilder\Clauses\HavingClause;
use PhobosFramework\Database\QueryBuilder\Clauses\LimitClause;

/**
 * Query Builder principal para SELECT
 */
class QueryBuilder implements QueryBuilderInterface {
    protected ConnectionInterface $connection;
    protected SelectClause $selectClause;
    protected FromClause $fromClause;
    protected WhereClause $whereClause;
    protected JoinClause $joinClause;
    protected OrderByClause $orderByClause;
    protected GroupByClause $groupByClause;
    protected HavingClause $havingClause;
    protected LimitClause $limitClause;
    protected array $unions = [];

    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection ?? ConnectionManager::getInstance()->getConnection();
        $this->initializeClauses();
    }

    protected function initializeClauses(): void {
        $this->selectClause = new SelectClause();
        $this->fromClause = new FromClause();
        $this->whereClause = new WhereClause();
        $this->joinClause = new JoinClause();
        $this->orderByClause = new OrderByClause();
        $this->groupByClause = new GroupByClause();
        $this->havingClause = new HavingClause();
        $this->limitClause = new LimitClause();
    }

    public function select(string|array ...$columns): self {
        $this->selectClause->addColumns(...$columns);
        return $this;
    }

    public function from(string $table, ?string $alias = null): self {
        $this->fromClause->setTable($table, $alias);
        return $this;
    }

    public function where(array|string $conditions, mixed ...$params): self {
        $this->whereClause->addCondition($conditions, 'AND', ...$params);
        return $this;
    }

    public function orWhere(array|string $conditions, mixed ...$params): self {
        $this->whereClause->addCondition($conditions, 'OR', ...$params);
        return $this;
    }

    public function join(string $table, string $alias, string $condition, string $type = 'INNER'): self {
        $this->joinClause->addJoin($table, $alias, $condition, $type);
        return $this;
    }

    public function innerJoin(string $table, string $alias, string $condition): self {
        return $this->join($table, $alias, $condition, 'INNER');
    }

    public function leftJoin(string $table, string $alias, string $condition): self {
        return $this->join($table, $alias, $condition, 'LEFT');
    }

    public function rightJoin(string $table, string $alias, string $condition): self {
        return $this->join($table, $alias, $condition, 'RIGHT');
    }

    public function orderBy(string|array $columns): self {
        $this->orderByClause->addColumns($columns);
        return $this;
    }

    public function groupBy(string|array $columns): self {
        $this->groupByClause->addColumns($columns);
        return $this;
    }

    public function having(array|string $conditions, mixed ...$params): self {
        $this->havingClause->addCondition($conditions, 'AND', ...$params);
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self {
        $this->limitClause->setLimit($limit);
        if ($offset !== null) {
            $this->limitClause->setOffset($offset);
        }
        return $this;
    }

    public function offset(int $offset): self {
        $this->limitClause->setOffset($offset);
        return $this;
    }

    public function distinct(): self {
        $this->selectClause->setDistinct(true);
        return $this;
    }

    /**
     * Agrega un UNION
     */
    public function union(QueryBuilderInterface $query, bool $all = false): self {
        $this->unions[] = [
            'query' => $query,
            'all' => $all
        ];
        return $this;
    }

    /**
     * Agrega un UNION ALL
     */
    public function unionAll(QueryBuilderInterface $query): self {
        return $this->union($query, true);
    }

    /**
     * Convierte este QueryBuilder en una SubQuery
     */
    public function asSubQuery(?string $alias = null): SubQuery {
        return new SubQuery($this, $alias);
    }

    /**
     * FROM con subquery
     */
    public function fromSubQuery(SubQuery $subQuery): self {
        $this->fromClause->setTable($subQuery->toSQL(), null);
        return $this;
    }

    /**
     * WHERE con subquery
     * Ejemplo: whereSubQuery('id IN', $subQuery)
     */
    public function whereSubQuery(string $column, string $operator, SubQuery $subQuery): self {
        $sql = "{$column} {$operator} {$subQuery->toSQL()}";
        $this->whereClause->addCondition($sql, 'AND', ...$subQuery->getBindings());
        return $this;
    }

    /**
     * WHERE EXISTS con subquery
     */
    public function whereExists(SubQuery $subQuery): self {
        $sql = "EXISTS {$subQuery->toSQL()}";
        $this->whereClause->addCondition($sql, 'AND', ...$subQuery->getBindings());
        return $this;
    }

    /**
     * WHERE NOT EXISTS con subquery
     */
    public function whereNotExists(SubQuery $subQuery): self {
        $sql = "NOT EXISTS {$subQuery->toSQL()}";
        $this->whereClause->addCondition($sql, 'AND', ...$subQuery->getBindings());
        return $this;
    }

    public function fetch(): array {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();
        return $this->connection->query($sql, $bindings);
    }

    public function fetchFirst(): ?array {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();
        return $this->connection->queryFirst($sql, $bindings);
    }

    public function fetchColumn(string|int $column = 0): mixed {
        $result = $this->fetchFirst();
        if ($result === null) {
            return null;
        }
        if (is_int($column)) {
            $values = array_values($result);
            return $values[$column] ?? null;
        }
        return $result[$column] ?? null;
    }

    public function getQueryWithBindings(): array {
        return [
            'query' => $this->getQuery(),
            'bindings' => $this->getBindings()
        ];
    }

    public function getQuery(): string {
        $parts = [];

        // SELECT
        $parts[] = $this->selectClause->toSQL();

        // FROM
        if ($this->fromClause->hasTable()) {
            $parts[] = $this->fromClause->toSQL();
        }

        // JOINs
        if ($this->joinClause->hasJoins()) {
            $parts[] = $this->joinClause->toSQL();
        }

        // WHERE
        if ($this->whereClause->hasConditions()) {
            $parts[] = $this->whereClause->toSQL();
        }

        // GROUP BY
        if ($this->groupByClause->hasColumns()) {
            $parts[] = $this->groupByClause->toSQL();
        }

        // HAVING
        if ($this->havingClause->hasConditions()) {
            $parts[] = $this->havingClause->toSQL();
        }

        $mainQuery = implode(' ', array_filter($parts));

        // UNIONs
        if (!empty($this->unions)) {
            $unionParts = [$mainQuery];

            foreach ($this->unions as $union) {
                $unionType = $union['all'] ? 'UNION ALL' : 'UNION';
                $unionParts[] = $unionType;
                $unionParts[] = '(' . $union['query']->getQuery() . ')';
            }

            $mainQuery = implode(' ', $unionParts);
        }

        // ORDER BY y LIMIT después de UNIONs
        if ($this->orderByClause->hasColumns()) {
            $mainQuery .= ' ' . $this->orderByClause->toSQL();
        }

        if ($this->limitClause->hasLimit() || $this->limitClause->hasOffset()) {
            $mainQuery .= ' ' . $this->limitClause->toSQL();
        }

        return $mainQuery;
    }

    public function getBindings(): array {
        $bindings = [];
        $bindings = array_merge($bindings, $this->whereClause->getBindings());
        $bindings = array_merge($bindings, $this->havingClause->getBindings());

        // Bindings de UNIONs
        foreach ($this->unions as $union) {
            $bindings = array_merge($bindings, $union['query']->getBindings());
        }

        return $bindings;
    }

    public function setConnection(ConnectionInterface $connection): self {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection(): ConnectionInterface {
        return $this->connection;
    }

    public function reset(): self {
        $this->initializeClauses();
        $this->unions = [];
        return $this;
    }

    public function clone(): self {
        return clone $this;
    }

    public function toDebugString(): string {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();
        $debug = "SQL: {$sql}\n";
        if (!empty($bindings)) {
            $debug .= "Bindings: " . json_encode($bindings, JSON_PRETTY_PRINT);
        }
        return $debug;
    }

    public function __toString(): string {
        return $this->getQuery();
    }

    public function __clone() {
        $this->selectClause = clone $this->selectClause;
        $this->fromClause = clone $this->fromClause;
        $this->whereClause = clone $this->whereClause;
        $this->joinClause = clone $this->joinClause;
        $this->orderByClause = clone $this->orderByClause;
        $this->groupByClause = clone $this->groupByClause;
        $this->havingClause = clone $this->havingClause;
        $this->limitClause = clone $this->limitClause;
    }
}
