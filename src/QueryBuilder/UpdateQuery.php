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
use PhobosFramework\Database\QueryBuilder\Clauses\WhereClause;

/**
 * Constructor de consultas para operaciones UPDATE en bases de datos
 *
 * Esta clase permite construir y ejecutar consultas SQL UPDATE de manera
 * programática y segura, facilitando la construcción de consultas de actualización
 * con condiciones WHERE y parámetros vinculados.
 *
 * @noinspection PhpUnused
 */
class UpdateQuery {
    /** @var ConnectionInterface Conexión a la base de datos */
    protected ConnectionInterface $connection;

    /** @var string|null Nombre de la tabla objetivo */
    protected ?string $table = null;

    /** @var array Datos a actualizar en formato columna => valor */
    protected array $data = [];

    /** @var WhereClause Cláusula WHERE para filtrar registros */
    protected WhereClause $whereClause;

    /**
     * Constructor de la clase UpdateQuery
     *
     * Inicializa una nueva instancia para construir consultas UPDATE.
     * Si no se proporciona una conexión, utiliza la conexión predeterminada
     * del ConnectionManager.
     *
     * @param ConnectionInterface|null $connection Conexión a la base de datos (opcional)
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection ?? ConnectionManager::getInstance()->getConnection();
        $this->whereClause = new WhereClause();
    }

    /**
     * Establece la tabla objetivo para la actualización
     *
     * Define el nombre de la tabla en la que se realizará la operación UPDATE.
     * Este método es obligatorio antes de ejecutar la consulta.
     *
     * @param string $table Nombre de la tabla a actualizar
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function table(string $table): self {
        $this->table = $table;
        return $this;
    }

    /**
     * Establece los datos que se actualizarán en la tabla
     *
     * Define los valores que se actualizarán en la operación UPDATE.
     * El array debe tener el formato ['columna' => 'valor'] donde las claves
     * son los nombres de las columnas y los valores son los nuevos datos.
     *
     * @param array $data Array asociativo con formato columna => valor
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function set(array $data): self {
        $this->data = $data;
        return $this;
    }

    /**
     * Agrega condiciones WHERE a la consulta
     *
     * Permite especificar condiciones para filtrar los registros que serán actualizados.
     * Las condiciones pueden proporcionarse como string (SQL raw) o como array asociativo.
     * Los parámetros adicionales se utilizan para vincular valores de manera segura.
     *
     * @param array|string $conditions Condiciones en formato string o array
     * @param mixed ...$params Parámetros adicionales para las condiciones
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function where(array|string $conditions, mixed ...$params): self {
        $this->whereClause->addCondition($conditions, 'AND', ...$params);
        return $this;
    }

    /**
     * Genera la consulta SQL UPDATE
     *
     * Construye y retorna la consulta SQL UPDATE completa basada en la tabla,
     * datos y condiciones WHERE establecidas previamente. La consulta incluye
     * marcadores de posición (?) para los valores que serán vinculados.
     *
     * @return string Consulta SQL generada con marcadores de posición
     */
    public function getQuery(): string {
        $setParts = [];
        foreach (array_keys($this->data) as $column) {
            $setParts[] = "$column = ?";
        }
        $setStr = implode(', ', $setParts);

        /**
         * @noinspection SqlNoDataSourceInspection
         * @noinspection SqlWithoutWhere
         */
        $sql = "UPDATE $this->table SET $setStr";

        if ($this->whereClause->hasConditions()) {
            $sql .= ' ' . $this->whereClause->toSQL();
        }

        return $sql;
    }

    /**
     * Genera la consulta SQL junto con sus parámetros vinculados
     *
     * Retorna un array asociativo que contiene tanto la consulta SQL generada
     * como los valores que deben vincularse a los marcadores de posición.
     * Útil para depuración o cuando se necesita la consulta y sus parámetros por separado.
     *
     * @return array Array con la consulta y sus bindings ['query' => string, 'bindings' => array]
     * @noinspection PhpUnused
     */
    public function getQueryWithBindings(): array {
        return [
            'query' => $this->getQuery(),
            'bindings' => $this->getBindings()
        ];
    }

    /**
     * Obtiene los parámetros vinculados para la consulta
     *
     * Recopila y retorna todos los valores que deben vincularse a la consulta SQL,
     * incluyendo tanto los valores del SET como los de las condiciones WHERE.
     * Los valores se retornan en el orden correcto para su vinculación.
     *
     * @return array Array de valores para los parámetros preparados
     */
    public function getBindings(): array {
        // Primero los valores del SET
        $bindings = array_values($this->data);

        // Luego los del WHERE
        return array_merge($bindings, $this->whereClause->getBindings());
    }

    /**
     * Ejecuta la consulta UPDATE en la base de datos
     *
     * Prepara y ejecuta la consulta SQL UPDATE con todos sus parámetros vinculados.
     * La ejecución se realiza de manera segura utilizando consultas preparadas.
     *
     * @return int Número de filas afectadas por la actualización
     * @noinspection PhpUnused
     */
    public function execute(): int {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();

        $stmt = $this->connection->execute($sql, $bindings);
        return $stmt->rowCount();
    }
}
