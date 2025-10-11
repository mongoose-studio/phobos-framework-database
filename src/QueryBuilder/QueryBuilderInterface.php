<?php

namespace PhobosFramework\Database\QueryBuilder;

use PhobosFramework\Database\Connection\ConnectionInterface;

/**
 * Interface para Query Builders
 */
interface QueryBuilderInterface {
    /**
     * Establece las columnas a seleccionar
     *
     * @param string|array ...$columns Columnas a seleccionar
     * @return self
     */
    public function select(string|array ...$columns): self;

    /**
     * Establece la tabla FROM
     *
     * @param string $table Nombre de la tabla
     * @param string|null $alias Alias de la tabla
     * @return self
     */
    public function from(string $table, ?string $alias = null): self;

    /**
     * Agrega una condición WHERE
     *
     * @param array|string $conditions Condiciones
     * @param mixed ...$params Parámetros adicionales
     * @return self
     */
    public function where(array|string $conditions, mixed ...$params): self;

    /**
     * Agrega una condición WHERE con OR
     *
     * @param array|string $conditions Condiciones
     * @param mixed ...$params Parámetros adicionales
     * @return self
     */
    public function orWhere(array|string $conditions, mixed ...$params): self;

    /**
     * Agrega un JOIN
     *
     * @param string $table Tabla a joinear
     * @param string $alias Alias de la tabla
     * @param string $condition Condición del join
     * @param string $type Tipo de join (INNER, LEFT, RIGHT, etc)
     * @return self
     */
    public function join(string $table, string $alias, string $condition, string $type = 'INNER'): self;

    /**
     * Agrega un INNER JOIN
     *
     * @param string $table Tabla a joinear
     * @param string $alias Alias de la tabla
     * @param string $condition Condición del join
     * @return self
     */
    public function innerJoin(string $table, string $alias, string $condition): self;

    /**
     * Agrega un LEFT JOIN
     *
     * @param string $table Tabla a joinear
     * @param string $alias Alias de la tabla
     * @param string $condition Condición del join
     * @return self
     */
    public function leftJoin(string $table, string $alias, string $condition): self;

    /**
     * Agrega un RIGHT JOIN
     *
     * @param string $table Tabla a joinear
     * @param string $alias Alias de la tabla
     * @param string $condition Condición del join
     * @return self
     */
    public function rightJoin(string $table, string $alias, string $condition): self;

    /**
     * Agrega ORDER BY
     *
     * @param string|array $columns Columnas para ordenar
     * @return self
     */
    public function orderBy(string|array $columns): self;

    /**
     * Agrega GROUP BY
     *
     * @param string|array $columns Columnas para agrupar
     * @return self
     */
    public function groupBy(string|array $columns): self;

    /**
     * Agrega HAVING
     *
     * @param array|string $conditions Condiciones
     * @param mixed ...$params Parámetros adicionales
     * @return self
     */
    public function having(array|string $conditions, mixed ...$params): self;

    /**
     * Agrega LIMIT
     *
     * @param int $limit Límite de filas
     * @param int|null $offset Offset (para paginación)
     * @return self
     */
    public function limit(int $limit, ?int $offset = null): self;

    /**
     * Agrega OFFSET (alias de limit con offset)
     *
     * @param int $offset Offset
     * @return self
     */
    public function offset(int $offset): self;

    /**
     * Establece DISTINCT
     *
     * @return self
     */
    public function distinct(): self;

    /**
     * Agrega un UNION
     *
     * @param QueryBuilderInterface $query Query a unir
     * @param bool $all Si es UNION ALL
     * @return self
     */
    public function union(QueryBuilderInterface $query, bool $all = false): self;

    /**
     * Agrega un UNION ALL
     *
     * @param QueryBuilderInterface $query Query a unir
     * @return self
     */
    public function unionAll(QueryBuilderInterface $query): self;

    /**
     * Ejecuta la query y retorna todos los resultados
     *
     * @return array
     */
    public function fetch(): array;

    /**
     * Ejecuta la query y retorna el primer resultado
     *
     * @return array|null
     */
    public function fetchFirst(): ?array;

    /**
     * Ejecuta la query y retorna una sola columna del primer resultado
     *
     * @param string|int $column Nombre o índice de la columna
     * @return mixed
     */
    public function fetchColumn(string|int $column = 0): mixed;

    /**
     * Obtiene la query SQL generada (para debug)
     *
     * @return string
     */
    public function getQuery(): string;

    /**
     * Obtiene los parámetros bindeados
     *
     * @return array
     */
    public function getBindings(): array;

    /**
     * Establece la conexión a usar
     *
     * @param ConnectionInterface $connection
     * @return self
     */
    public function setConnection(ConnectionInterface $connection): self;

    /**
     * Obtiene la conexión utilizada
     *
     * @return ConnectionInterface
     */
    public function getConnection(): ConnectionInterface;
}
