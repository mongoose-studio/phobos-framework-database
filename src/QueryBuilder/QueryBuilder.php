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
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\UnsupportedDriverException;
use PhobosFramework\Database\QueryBuilder\Clauses\SelectClause;
use PhobosFramework\Database\QueryBuilder\Clauses\FromClause;
use PhobosFramework\Database\QueryBuilder\Clauses\SubQuery;
use PhobosFramework\Database\QueryBuilder\Clauses\WhereClause;
use PhobosFramework\Database\QueryBuilder\Clauses\JoinClause;
use PhobosFramework\Database\QueryBuilder\Clauses\OrderByClause;
use PhobosFramework\Database\QueryBuilder\Clauses\GroupByClause;
use PhobosFramework\Database\QueryBuilder\Clauses\HavingClause;
use PhobosFramework\Database\QueryBuilder\Clauses\LimitClause;

/**
 * Generador de consultas SQL SELECT con interfaz fluida
 *
 * Esta clase permite construir consultas SQL SELECT de manera programática y segura,
 * proporcionando una interfaz fluida que facilita su uso. Incluye soporte para:
 *
 * - SELECT: Selección de columnas específicas o todas (*)
 * - FROM: Especificación de tablas origen con alias opcional
 * - WHERE: Condiciones de filtrado con soporte para AND/OR y parámetros vinculados
 * - JOIN: Soporte para INNER JOIN, LEFT JOIN y RIGHT JOIN con alias
 * - ORDER BY: Ordenamiento por múltiples columnas
 * - GROUP BY: Agrupamiento de resultados
 * - HAVING: Filtrado de grupos
 * - LIMIT/OFFSET: Paginación de resultados
 * - UNION/UNION ALL: Combinación de múltiples consultas
 * - Subconsultas: Soportadas en FROM, WHERE y EXISTS
 *
 * Características principales:
 * - Protección contra inyección SQL mediante parámetros vinculados
 * - Interfaz fluida para encadenar métodos
 * - Soporte para consultas complejas y anidadas
 * - Gestión automática de conexiones a base de datos
 *
 * Ejemplo básico de uso:
 * ```
 * $query = new QueryBuilder();
 * $result = $query->select('id', 'nombre', 'email')
 *                 ->from('usuarios')
 *                 ->where('activo = ?', 1)
 *                 ->orderBy('nombre')
 *                 ->fetch();
 * ```
 *
 * Ejemplo avanzado con joins y agrupamiento:
 * ```
 * $query = new QueryBuilder();
 * $result = $query->select('u.id', 'u.nombre', 'p.total')
 *                 ->from('usuarios', 'u')
 *                 ->leftJoin('pedidos', 'p', 'p.usuario_id = u.id')
 *                 ->where('u.activo = ?', 1)
 *                 ->groupBy('u.id')
 *                 ->having('COUNT(p.id) > ?', 5)
 *                 ->orderBy(['u.nombre ASC', 'p.total DESC'])
 *                 ->limit(10)
 *                 ->fetch();
 * ```
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

    /**
     * @param ConnectionInterface|null $connection
     * @throws ConnectionException
     * @throws UnsupportedDriverException
     */
    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection ?? ConnectionManager::getInstance()->getConnection();
        $this->initializeClauses();
    }

    /**
     * 
     * @return void
     */
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

    /**
     * Especifica las columnas que se seleccionarán en la consulta SQL
     *
     * Este método permite definir qué columnas se incluirán en la cláusula SELECT
     * de la consulta SQL. Acepta múltiples argumentos que pueden ser nombres de
     * columnas individuales o arrays de columnas.
     *
     * Las columnas pueden incluir:
     * - Nombres simples de columnas ('id', 'nombre')
     * - Columnas con alias ('nombre AS nombreCompleto')
     * - Nombres de columnas calificados con tabla ('usuarios.nombre')
     * - Expresiones SQL ('COUNT(*)', 'MAX(precio)')
     *
     * Ejemplo:
     * ```
     * $query->select('id', 'nombre', 'email');
     * $query->select(['usuarios.id', 'usuarios.nombre AS nombreCompleto']);
     * ```
     *
     * @param string|array ...$columns Una o más columnas a seleccionar
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function select(string|array ...$columns): self {
        $this->selectClause->addColumns(...$columns);
        return $this;
    }

    /**
     * Especifica la tabla principal de la consulta SQL
     *
     * Este método define la tabla desde la cual se seleccionarán los datos en la
     * cláusula FROM de la consulta SQL. Opcionalmente permite especificar un alias
     * para la tabla, lo cual es útil en consultas con múltiples tablas o joins.
     *
     * Ejemplo:
     * ```
     * $query->from('usuarios'); // FROM usuarios
     * $query->from('usuarios', 'u'); // FROM usuarios u
     * ```
     *
     * @param string $table Nombre de la tabla
     * @param string|null $alias Alias opcional para la tabla
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function from(string $table, ?string $alias = null): self {
        $this->fromClause->setTable($table, $alias);
        return $this;
    }

    /**
     * Agrega una condición WHERE a la consulta SQL
     *
     * Este método permite agregar condiciones de filtrado a la consulta mediante
     * la cláusula WHERE. Las condiciones se combinan con AND si se llama múltiples veces.
     * Soporta tanto condiciones simples como arrays de condiciones.
     *
     * Ejemplos:
     * ```
     * $query->where('edad > ?', 18);
     * $query->where(['activo' => 1, 'tipo' => 'usuario']);
     * ```
     *
     * @param array|string $conditions Condiciones WHERE como string o array
     * @param mixed ...$params Parámetros para vincular a la condición
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function where(array|string $conditions, mixed ...$params): self {
        $this->whereClause->addCondition($conditions, 'AND', ...$params);
        return $this;
    }

    /**
     * Agrega una condición WHERE con operador OR a la consulta SQL
     *
     * Similar al método where(), pero las condiciones se combinan con OR en lugar de AND.
     * Útil para especificar condiciones alternativas en la consulta.
     *
     * Ejemplo:
     * ```
     * $query->where('tipo = ?', 'admin')
     *       ->orWhere('permisos = ?', 'super');
     * ```
     *
     * @param array|string $conditions Condiciones WHERE como string o array
     * @param mixed ...$params Parámetros para vincular a la condición
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function orWhere(array|string $conditions, mixed ...$params): self {
        $this->whereClause->addCondition($conditions, 'OR', ...$params);
        return $this;
    }

    /**
     * Agrega una cláusula JOIN a la consulta SQL
     *
     * Permite realizar uniones entre tablas especificando el tipo de JOIN,
     * la tabla a unir, su alias y la condición de unión.
     *
     * Ejemplo:
     * ```
     * $query->join('categorias', 'c', 'c.id = productos.categoria_id', 'LEFT');
     * ```
     *
     * @param string $table Nombre de la tabla a unir
     * @param string $alias Alias para la tabla
     * @param string $condition Condición de unión
     * @param string $type Tipo de JOIN (INNER, LEFT, RIGHT)
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function join(string $table, string $alias, string $condition, string $type = 'INNER'): self {
        $this->joinClause->addJoin($table, $alias, $condition, $type);
        return $this;
    }

    /**
     * Agrega una cláusula INNER JOIN a la consulta SQL
     *
     * Realiza una unión interna entre tablas, devolviendo solo los registros
     * que tienen coincidencias en ambas tablas.
     *
     * Ejemplo:
     * ```
     * $query->innerJoin('pedidos', 'p', 'p.usuario_id = u.id');
     * ```
     *
     * @param string $table Nombre de la tabla a unir
     * @param string $alias Alias para la tabla
     * @param string $condition Condición de unión
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function innerJoin(string $table, string $alias, string $condition): self {
        return $this->join($table, $alias, $condition);
    }

    /**
     * Agrega una cláusula LEFT JOIN a la consulta SQL
     *
     * Realiza una unión izquierda, incluyendo todos los registros de la tabla
     * principal aunque no tengan coincidencias en la tabla unida.
     *
     * Ejemplo:
     * ```
     * $query->leftJoin('comentarios', 'c', 'c.post_id = p.id');
     * ```
     *
     * @param string $table Nombre de la tabla a unir
     * @param string $alias Alias para la tabla
     * @param string $condition Condición de unión
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function leftJoin(string $table, string $alias, string $condition): self {
        return $this->join($table, $alias, $condition, 'LEFT');
    }

    /**
     * Agrega una cláusula RIGHT JOIN a la consulta SQL
     *
     * Realiza una unión derecha, incluyendo todos los registros de la tabla
     * unida aunque no tengan coincidencias en la tabla principal.
     *
     * Ejemplo:
     * ```
     * $query->rightJoin('departamentos', 'd', 'd.id = e.departamento_id');
     * ```
     *
     * @param string $table Nombre de la tabla a unir
     * @param string $alias Alias para la tabla
     * @param string $condition Condición de unión
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function rightJoin(string $table, string $alias, string $condition): self {
        return $this->join($table, $alias, $condition, 'RIGHT');
    }

    /**
     * Agrega una cláusula ORDER BY a la consulta SQL
     *
     * Permite especificar el orden de los resultados por una o más columnas.
     * Acepta tanto una cadena simple como un array de columnas.
     *
     * Ejemplo:
     * ```
     * $query->orderBy('nombre ASC');
     * $query->orderBy(['nombre ASC', 'fecha DESC']);
     * ```
     *
     * @param string|array $columns Columna(s) para ordenar
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function orderBy(string|array $columns): self {
        $this->orderByClause->addColumns($columns);
        return $this;
    }

    /**
     * Agrega una cláusula GROUP BY a la consulta SQL
     *
     * Permite agrupar los resultados por una o más columnas.
     * Útil en conjunto con funciones de agregación.
     *
     * Ejemplo:
     * ```
     * $query->groupBy('categoria_id');
     * $query->groupBy(['departamento', 'cargo']);
     * ```
     *
     * @param string|array $columns Columna(s) para agrupar
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function groupBy(string|array $columns): self {
        $this->groupByClause->addColumns($columns);
        return $this;
    }

    /**
     * Agrega una cláusula HAVING a la consulta SQL
     *
     * Permite filtrar grupos creados por GROUP BY. Similar a WHERE pero
     * se aplica después de la agrupación y puede usar funciones de agregación.
     *
     * Ejemplo:
     * ```
     * $query->having('COUNT(*) > ?', 5);
     * $query->having(['SUM(total) > ?' => 1000]);
     * ```
     *
     * @param array|string $conditions Condiciones HAVING como string o array
     * @param mixed ...$params Parámetros para vincular a la condición
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function having(array|string $conditions, mixed ...$params): self {
        $this->havingClause->addCondition($conditions, 'AND', ...$params);
        return $this;
    }

    /**
     * Agrega una cláusula LIMIT y opcionalmente OFFSET a la consulta SQL
     *
     * Permite limitar el número de resultados devueltos y especificar
     * un punto de inicio (offset) para la paginación.
     *
     * Ejemplo:
     * ```
     * $query->limit(10); // Primeros 10 resultados
     * $query->limit(10, 20); // 10 resultados, comenzando desde el 20
     * ```
     *
     * @param int $limit Número máximo de resultados a devolver
     * @param int|null $offset Número de filas a saltar (opcional)
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function limit(int $limit, ?int $offset = null): self {
        $this->limitClause->setLimit($limit);
        if ($offset !== null) {
            $this->limitClause->setOffset($offset);
        }
        return $this;
    }

    /**
     * Establece el desplazamiento (offset) para la paginación de resultados
     *
     * Este método permite especificar cuántas filas se deben saltar antes de
     * comenzar a devolver resultados. Se utiliza comúnmente junto con limit()
     * para implementar la paginación de resultados.
     *
     * Ejemplo:
     * ```
     * // Obtiene 10 registros, saltando los primeros 20
     * $query->limit(10)->offset(20);
     * ```
     *
     * @param int $offset Número de filas a saltar
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function offset(int $offset): self {
        $this->limitClause->setOffset($offset);
        return $this;
    }

    /**
     * Establece que la consulta debe devolver resultados únicos/distintos
     *
     * Este método modifica la consulta SQL para incluir la cláusula DISTINCT,
     * lo que elimina duplicados del conjunto de resultados. Es útil cuando
     * se necesitan obtener valores únicos de una o más columnas.
     *
     * Ejemplo:
     * ```
     * $query->distinct()->select('categoria')->from('productos');
     * // Selecciona todas las categorías únicas de productos
     * ```
     *
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function distinct(): self {
        $this->selectClause->setDistinct();
        return $this;
    }

    /**
     * Agrega una cláusula UNION a la consulta
     *
     * @param QueryBuilderInterface $query Consulta a unir
     * @param bool $all Si es true, usa UNION ALL en lugar de UNION
     * @return self Retorna la instancia actual para encadenamiento
     */
    public function union(QueryBuilderInterface $query, bool $all = false): self {
        $this->unions[] = [
            'query' => $query,
            'all' => $all
        ];
        return $this;
    }

    /**
     * Agrega una cláusula UNION ALL a la consulta
     *
     * @param QueryBuilderInterface $query Consulta a unir
     * @return self Retorna la instancia actual para encadenamiento
     */
    public function unionAll(QueryBuilderInterface $query): self {
        return $this->union($query, true);
    }

    /**
     * Convierte este QueryBuilder en una subconsulta
     *
     * @param string|null $alias Alias opcional para la subconsulta
     * @return SubQuery Objeto SubQuery que representa la subconsulta
     * @noinspection PhpUnused
     */
    public function asSubQuery(?string $alias = null): SubQuery {
        return new SubQuery($this, $alias);
    }

    /**
     * Define una subconsulta como origen en la cláusula FROM
     *
     * @param SubQuery $subQuery Subconsulta a utilizar
     * @return self Retorna la instancia actual para encadenamiento
     * @noinspection PhpUnused
     */
    public function fromSubQuery(SubQuery $subQuery): self {
        $this->fromClause->setTable($subQuery->toSQL());
        return $this;
    }

    /**
     * Agrega una condición WHERE que utiliza una subconsulta
     *
     * @param string $column Columna o expresión a comparar
     * @param string $operator Operador de comparación (IN, =, >, etc.)
     * @param SubQuery $subQuery Subconsulta a utilizar
     * @return self Retorna la instancia actual para encadenamiento
     *
     * Ejemplo: whereSubQuery('id', 'IN', $subQuery)
     * @noinspection PhpUnused
     */
    public function whereSubQuery(string $column, string $operator, SubQuery $subQuery): self {
        $sql = "$column $operator {$subQuery->toSQL()}";
        $this->whereClause->addCondition($sql, 'AND', ...$subQuery->getBindings());
        return $this;
    }

    /**
     * Agrega una condición WHERE EXISTS con una subconsulta
     *
     * @param SubQuery $subQuery Subconsulta a evaluar
     * @return self Retorna la instancia actual para encadenamiento
     * @noinspection PhpUnused
     */
    public function whereExists(SubQuery $subQuery): self {
        $sql = "EXISTS {$subQuery->toSQL()}";
        $this->whereClause->addCondition($sql, 'AND', ...$subQuery->getBindings());
        return $this;
    }

    /**
     * Agrega una condición WHERE NOT EXISTS con una subconsulta
     *
     * @param SubQuery $subQuery Subconsulta a evaluar
     * @return self Retorna la instancia actual para encadenamiento
     * @noinspection PhpUnused
     */
    public function whereNotExists(SubQuery $subQuery): self {
        $sql = "NOT EXISTS {$subQuery->toSQL()}";
        $this->whereClause->addCondition($sql, 'AND', ...$subQuery->getBindings());
        return $this;
    }

    /**
     * Ejecuta la consulta SQL y obtiene todos los resultados
     *
     * Este método ejecuta la consulta SQL construida y devuelve todos los registros
     * que coinciden con los criterios especificados. Los resultados se devuelven
     * como un array de arrays asociativos, donde cada elemento representa una fila
     * de la base de datos.
     *
     * Los parámetros vinculados (bindings) se manejan automáticamente para prevenir
     * inyecciones SQL y garantizar la seguridad de la consulta.
     *
     * @return array Array de arrays asociativos con los resultados de la consulta.
     *               Retorna un array vacío si no se encuentran resultados.
     */
    public function fetch(): array {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();
        return $this->connection->query($sql, $bindings);
    }

    /**
     * Obtiene la primera fila del resultado de la consulta
     *
     * Este método ejecuta la consulta y devuelve solo el primer registro del resultado.
     * Es útil cuando se espera obtener un único registro o cuando solo se necesita
     * el primer resultado de una consulta que podría devolver múltiples filas.
     *
     * @return array|object|null Retorna un array asociativo con los datos de la primera fila,
     *                    o null si no se encontraron resultados
     */
    public function fetchFirst(): array|object|null {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();
        return $this->connection->queryFirst($sql, $bindings);
    }

    /**
     * Obtiene un único valor de la primera fila del resultado de la consulta
     *
     * Este método ejecuta la consulta y devuelve un único valor de la primera fila del resultado.
     * Puede especificar la columna ya sea por su nombre o por su posición numérica (comenzando desde 0).
     *
     * @param string|int $column Nombre de la columna o índice numérico (0 basado) de la columna
     * @return mixed El valor de la columna especificada, o null si no hay resultados o la columna no existe
     */
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

    /**
     * Obtiene la consulta junto con sus bindings
     *
     * @return array Retorna un array asociativo con las claves 'query' y 'bindings',
     *               donde 'query' contiene la consulta generada y 'bindings' contiene los valores vinculados
     * @noinspection PhpUnused
     */
    public function getQueryWithBindings(): array {
        return [
            'query' => $this->getQuery(),
            'bindings' => $this->getBindings()
        ];
    }

    /**
     * Obtiene y construye la consulta SQL completa
     *
     * Este método combina todas las cláusulas SQL definidas (SELECT, FROM, WHERE, etc.)
     * en una única consulta SQL. El proceso de construcción sigue este orden:
     * 1. Cláusula SELECT (incluyendo DISTINCT si está establecido)
     * 2. Cláusula FROM con la tabla principal
     * 3. Cláusulas JOIN (INNER, LEFT, RIGHT)
     * 4. Cláusula WHERE con condiciones de filtrado
     * 5. Cláusula GROUP BY para agrupamiento
     * 6. Cláusula HAVING para filtrado de grupos
     * 7. Cláusulas UNION/UNION ALL si existen
     * 8. Cláusula ORDER BY para ordenamiento
     * 9. Cláusula LIMIT/OFFSET para paginación
     *
     * @return string Retorna la consulta SQL completa como una cadena
     */
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

    /**
     * Obtiene los bindings de la consulta
     * @return array
     */
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

    /**
     * Establece la conexión a la base de datos para este QueryBuilder
     *
     * Este método permite cambiar la conexión a la base de datos que utiliza
     * el QueryBuilder para ejecutar las consultas. La conexión debe implementar
     * la interfaz ConnectionInterface.
     *
     * @param ConnectionInterface $connection Nueva conexión a establecer
     * @return $this Retorna la instancia actual para permitir encadenamiento
     */
    public function setConnection(ConnectionInterface $connection): self {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Obtiene la conexión a la base de datos actual utilizada por el QueryBuilder
     *
     * Este método devuelve la instancia de ConnectionInterface que está siendo
     * utilizada actualmente por el QueryBuilder para ejecutar las consultas SQL.
     * La conexión puede haber sido proporcionada en el constructor o establecida
     * posteriormente mediante setConnection().
     *
     * @return ConnectionInterface La conexión actual a la base de datos
     */
    public function getConnection(): ConnectionInterface {
        return $this->connection;
    }

    /**
     * Reinicia el estado del QueryBuilder a su estado inicial
     * @return self
     * @noinspection PhpUnused
     */
    public function reset(): self {
        $this->initializeClauses();
        $this->unions = [];
        return $this;
    }

    /**
     * Crea una copia de este QueryBuilder
     * @return self
     */
    public function clone(): self {
        return clone $this;
    }

    /**
     * Genera una cadena de depuración que incluye la consulta SQL y sus parámetros vinculados
     *
     * Este método es útil para depurar y verificar las consultas SQL generadas junto con
     * sus parámetros vinculados (bindings). La cadena resultante incluye:
     * - La consulta SQL completa
     * - Los valores de los parámetros vinculados en formato JSON (si existen)
     *
     * @return string Cadena formateada con la consulta SQL y sus parámetros
     * @noinspection PhpUnused
     */
    public function toDebugString(): string {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();
        $debug = "SQL: $sql\n";
        if (!empty($bindings)) {
            $debug .= "Bindings: " . json_encode($bindings, JSON_PRETTY_PRINT);
        }
        return $debug;
    }

    /**
     * Convierte el objeto QueryBuilder en un string
     * @return string
     */
    public function __toString(): string {
        return $this->getQuery();
    }

    /**
     * Crea una copia profunda del objeto actual clonando todas sus cláusulas.
     *
     * @return void
     */
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
