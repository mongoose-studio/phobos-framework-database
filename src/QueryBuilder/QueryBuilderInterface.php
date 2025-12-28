<?php

/**
 * # Phobos Framework
 *
 * Para la información completa acerca del copyright y la licencia,
 * por favor vea el archivo LICENSE que va distribuido con el código fuente.
 *
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */

namespace PhobosFramework\Database\QueryBuilder;

use PhobosFramework\Database\Connection\ConnectionInterface;

/**
 * Interface para Query Builders (Constructores de consultas)
 *
 * Esta interfaz define los métodos necesarios para construir consultas SQL
 * de forma fluida y segura utilizando un patrón builder.
 */
interface QueryBuilderInterface {
    /**
     * Establece las columnas a seleccionar en la consulta SELECT
     *
     * @param string|array ...$columns Columnas a seleccionar. Puede ser un string con el nombre
     *                                de la columna o un array con múltiples columnas
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function select(string|array ...$columns): self;

    /**
     * Establece la tabla principal en la cláusula FROM de la consulta
     *
     * @param string $table Nombre de la tabla de la base de datos
     * @param string|null $alias Alias opcional para referenciar la tabla en la consulta
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function from(string $table, ?string $alias = null): self;

    /**
     * Agrega una condición WHERE a la consulta
     *
     * @param array|string $conditions Condiciones para filtrar los resultados. Puede ser un string con la condición
     *                                o un array asociativo de columna => valor
     * @param mixed ...$params Parámetros adicionales para bindear de forma segura a la consulta
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function where(array|string $conditions, mixed ...$params): self;

    /**
     * Agrega una condición WHERE con operador OR a la consulta
     *
     * @param array|string $conditions Condiciones para filtrar los resultados unidas por OR. Puede ser un string
     *                                con la condición o un array asociativo de columna => valor
     * @param mixed ...$params Parámetros adicionales para bindear de forma segura a la consulta
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function orWhere(array|string $conditions, mixed ...$params): self;

    /**
     * Agrega una cláusula JOIN a la consulta
     *
     * @param string $table Nombre de la tabla con la que se realizará el join
     * @param string $alias Alias para referenciar la tabla unida
     * @param string $condition Condición que define la relación entre las tablas
     * @param string $type Tipo de JOIN (INNER, LEFT, RIGHT, etc)
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function join(string $table, string $alias, string $condition, string $type = 'INNER'): self;

    /**
     * Agrega una cláusula INNER JOIN a la consulta
     *
     * @param string $table Nombre de la tabla con la que se realizará el join
     * @param string $alias Alias para referenciar la tabla unida
     * @param string $condition Condición que define la relación entre las tablas
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function innerJoin(string $table, string $alias, string $condition): self;

    /**
     * Agrega una cláusula LEFT JOIN a la consulta
     *
     * @param string $table Nombre de la tabla con la que se realizará el join
     * @param string $alias Alias para referenciar la tabla unida
     * @param string $condition Condición que define la relación entre las tablas
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function leftJoin(string $table, string $alias, string $condition): self;

    /**
     * Agrega una cláusula RIGHT JOIN a la consulta
     *
     * @param string $table Nombre de la tabla con la que se realizará el join
     * @param string $alias Alias para referenciar la tabla unida
     * @param string $condition Condición que define la relación entre las tablas
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function rightJoin(string $table, string $alias, string $condition): self;

    /**
     * Agrega una cláusula ORDER BY para ordenar los resultados
     *
     * @param string|array $columns Columnas por las que se ordenarán los resultados. Puede ser un string
     *                             con el nombre de la columna o un array con múltiples columnas
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function orderBy(string|array $columns): self;

    /**
     * Agrega una cláusula GROUP BY para agrupar los resultados
     *
     * @param string|array $columns Columnas por las que se agruparán los resultados. Puede ser un string
     *                             con el nombre de la columna o un array con múltiples columnas
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function groupBy(string|array $columns): self;

    /**
     * Agrega una cláusula HAVING para filtrar grupos de resultados
     *
     * @param array|string $conditions Condiciones para filtrar los grupos. Puede ser un string con la condición
     *                                o un array asociativo de columna => valor
     * @param mixed ...$params Parámetros adicionales para bindear de forma segura a la consulta
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function having(array|string $conditions, mixed ...$params): self;

    /**
     * Agrega una cláusula LIMIT para limitar el número de resultados
     *
     * @param int $limit Número máximo de filas a retornar
     * @param int|null $offset Número de filas a saltar antes de comenzar a retornar resultados
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function limit(int $limit, ?int $offset = null): self;

    /**
     * Agrega una cláusula OFFSET para paginación de resultados
     *
     * @param int $offset Número de filas a saltar antes de comenzar a retornar resultados
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function offset(int $offset): self;

    /**
     * Establece la cláusula DISTINCT para eliminar duplicados en los resultados
     *
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function distinct(): self;

    /**
     * Agrega una cláusula UNION para combinar resultados de múltiples consultas
     *
     * @param QueryBuilderInterface $query Consulta que se combinará con la actual
     * @param bool $all Si es true, utiliza UNION ALL que incluye duplicados
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function union(QueryBuilderInterface $query, bool $all = false): self;

    /**
     * Agrega una cláusula UNION ALL para combinar resultados incluyendo duplicados
     *
     * @param QueryBuilderInterface $query Consulta que se combinará con la actual
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function unionAll(QueryBuilderInterface $query): self;

    /**
     * Ejecuta la consulta y retorna todos los resultados como un array
     *
     * @return array Array con todos los registros encontrados
     */
    public function fetch(): array;

    /**
     * Ejecuta la consulta y retorna solo el primer resultado
     *
     * @return array|null Array con el primer registro encontrado o null si no hay resultados
     */
    public function fetchFirst(): array|object|null;

    /**
     * Ejecuta la consulta y retorna el valor de una columna específica del primer resultado
     *
     * @param string|int $column Nombre o índice numérico de la columna a retornar
     * @return mixed Valor de la columna especificada o null si no hay resultados
     */
    public function fetchColumn(string|int $column = 0): mixed;

    /**
     * Obtiene la consulta SQL generada (útil para depuración)
     *
     * @return string Consulta SQL completa con los parámetros reemplazados
     */
    public function getQuery(): string;

    /**
     * Obtiene los parámetros vinculados a la consulta
     *
     * @return array Array asociativo con los parámetros y sus valores
     */
    public function getBindings(): array;

    /**
     * Establece la conexión a la base de datos que se utilizará
     *
     * @param ConnectionInterface $connection Objeto de conexión a la base de datos
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function setConnection(ConnectionInterface $connection): self;

    /**
     * Obtiene la conexión a la base de datos que se está utilizando
     *
     * @return ConnectionInterface Objeto de conexión actual
     */
    public function getConnection(): ConnectionInterface;
}
