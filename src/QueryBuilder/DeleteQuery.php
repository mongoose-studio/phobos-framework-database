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
use PhobosFramework\Database\Exceptions\InvalidArgumentException;
use PhobosFramework\Database\Exceptions\UnsupportedDriverException;
use PhobosFramework\Database\QueryBuilder\Clauses\WhereClause;

/**
 * Query Builder para operaciones DELETE en SQL.
 * Esta clase permite construir y ejecutar consultas DELETE de manera programática,
 * proporcionando una interfaz fluida para definir condiciones WHERE y límites.
 * @noinspection PhpUnused
 */
class DeleteQuery {
    /**
     * Conexión a la base de datos
     * @var ConnectionInterface
     */
    protected ConnectionInterface $connection;

    /**
     * Nombre de la tabla objetivo para la eliminación
     * @var string|null
     */
    protected ?string $table = null;

    /**
     * Cláusula WHERE para filtrar los registros a eliminar
     * @var WhereClause
     */
    protected WhereClause $whereClause;

    /**
     * Límite de registros a eliminar
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * Constructor para la clase DeleteQuery.
     * Inicializa una nueva instancia para construir consultas DELETE.
     * Si no se proporciona una conexión, utiliza la conexión predeterminada del ConnectionManager.
     *
     * @param ConnectionInterface|null $connection Conexión a la base de datos (opcional)
     * @throws UnsupportedDriverException Si el driver de base de datos no es compatible
     * @throws ConnectionException Si hay un error al establecer la conexión
     */
    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection ?? ConnectionManager::getInstance()->getConnection();
        $this->whereClause = new WhereClause();
    }

    /**
     * Establece la tabla de la cual se eliminarán los registros
     *
     * @param string $table Nombre de la tabla objetivo
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function from(string $table): self {
        $this->table = $table;
        return $this;
    }

    /**
     * Agrega condiciones WHERE para filtrar los registros a eliminar
     *
     * @param array|string $conditions Condiciones WHERE en formato string o array
     * @param mixed ...$params Parámetros adicionales para las condiciones
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function where(array|string $conditions, mixed ...$params): self {
        $this->whereClause->addCondition($conditions, 'AND', ...$params);
        return $this;
    }

    /**
     * Establece el límite máximo de registros a eliminar
     *
     * @param int $limit Número máximo de registros a eliminar
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @throws InvalidArgumentException Si el límite es un número negativo
     * @noinspection PhpUnused
     */
    public function limit(int $limit): self {
        if ($limit < 0) {
            throw new InvalidArgumentException('LIMIT must be a non-negative integer');
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Genera la consulta SQL DELETE completa
     *
     * @return string Consulta SQL generada
     */
    public function getQuery(): string {
        /** @noinspection SqlNoDataSourceInspection */
        /** @noinspection SqlWithoutWhere */
        $sql = "DELETE FROM $this->table";

        if ($this->whereClause->hasConditions()) {
            $sql .= ' ' . $this->whereClause->toSQL();
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT $this->limit";
        }

        return $sql;
    }

    /**
     * Genera la consulta SQL junto con sus parámetros vinculados
     *
     * @return array Array asociativo con la consulta y sus bindings
     * @noinspection PhpUnused
     */
    public function getQueryWithBindings(): array {
        return [
            'query' => $this->getQuery(),
            'bindings' => $this->getBindings()
        ];
    }

    /**
     * Obtiene los parámetros vinculados de la consulta
     *
     * @return array Array con los valores de los parámetros vinculados
     */
    public function getBindings(): array {
        return $this->whereClause->getBindings();
    }

    /**
     * Ejecuta la consulta DELETE en la base de datos
     *
     * @return int Número de registros afectados por la eliminación
     * @noinspection PhpUnused
     */
    public function execute(): int {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();

        $stmt = $this->connection->execute($sql, $bindings);
        return $stmt->rowCount();
    }
}
