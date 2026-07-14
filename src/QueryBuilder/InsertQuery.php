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
use PhobosFramework\Database\Exceptions\LogicException;
use PhobosFramework\Database\Exceptions\UnsupportedDriverException;

/**
 * Constructor de consultas SQL para operaciones INSERT
 *
 * Esta clase proporciona una interfaz fluida para construir y ejecutar consultas INSERT.
 * Soporta tanto inserciones simples como múltiples y maneja automáticamente los bindings
 * de parámetros para prevenir inyección SQL.
 * @noinspection PhpUnused
 */
class InsertQuery {
    /** @var ConnectionInterface Conexión a la base de datos utilizada para ejecutar las consultas */
    protected ConnectionInterface $connection;

    /** @var string|null Nombre de la tabla donde se insertarán los datos */
    protected ?string $table = null;

    /** @var array Datos a insertar en la tabla */
    protected array $data = [];

    /** @var bool Indica si es una inserción múltiple */
    protected bool $multiple = false;

    /**
     * Constructor de la clase InsertQuery
     *
     * Inicializa una nueva instancia del constructor de consultas INSERT.
     * Si no se proporciona una conexión, utiliza la conexión predeterminada
     * del ConnectionManager.
     *
     * @param ConnectionInterface|null $connection Conexión a la base de datos (opcional)
     * @throws UnsupportedDriverException Si el driver de base de datos no es soportado
     * @throws ConnectionException Si hay un error al establecer la conexión
     */
    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection ?? ConnectionManager::getInstance()->getConnection();
    }

    /**
     * Establece la tabla donde se insertarán los datos
     *
     * Este método define la tabla objetivo para la operación INSERT.
     *
     * @param string $table Nombre de la tabla donde se insertarán los datos
     * @return self Retorna la instancia actual para permitir el encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function into(string $table): self {
        $this->table = $table;
        return $this;
    }

    /**
     * Establece los datos a insertar en la tabla
     *
     * Configura los valores que serán insertados en la tabla. Soporta tanto
     * inserciones individuales como múltiples, detectando automáticamente
     * el tipo de inserción basado en la estructura de los datos proporcionados.
     *
     * @param array $data Datos a insertar. Puede ser:
     *                    - Array asociativo simple ['columna' => 'valor'] para inserción única
     *                    - Array de arrays [['columna' => 'valor1'], ['columna' => 'valor2']] para inserción múltiple
     * @return self Retorna la instancia actual para permitir el encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function values(array $data): self {
        // Detectar si es insert múltiple
        if ($this->isMultipleInsert($data)) {
            $this->multiple = true;
        } else {
            $this->multiple = false;
        }
        $this->data = $data;

        return $this;
    }

    /**
     * Detecta si los datos proporcionados corresponden a una inserción múltiple
     *
     * Analiza la estructura de los datos para determinar si se trata de una
     * inserción múltiple (array de arrays) o una inserción simple.
     *
     * @param array $data Datos a analizar
     * @return bool TRUE si es una inserción múltiple, FALSE si es una inserción simple
     */
    protected function isMultipleInsert(array $data): bool {
        if (empty($data)) {
            return false;
        }

        $firstElement = reset($data);
        return is_array($firstElement) && !empty($firstElement);
    }

    /**
     * Genera la consulta SQL basada en la tabla y datos configurados
     *
     * Construye la sentencia SQL INSERT completa utilizando la tabla y los datos
     * previamente configurados. Maneja automáticamente tanto inserciones simples
     * como múltiples.
     *
     * @return string Consulta SQL generada lista para su ejecución
     * @throws InvalidArgumentException Si no se han proporcionado datos válidos o si la configuración es incorrecta
     */
    public function getQuery(): string {
        if ($this->multiple) {
            return $this->getMultipleInsertQuery();
        }

        return $this->getSingleInsertQuery();
    }

    /**
     * Genera la consulta SQL junto con sus parámetros vinculados (bindings)
     *
     * Retorna tanto la consulta SQL como sus valores correspondientes en un formato
     * que permite una ejecución segura de la consulta, previniendo inyección SQL.
     *
     * @return array Array asociativo con la consulta SQL y sus bindings ['query' => string, 'bindings' => array]
     * @throws InvalidArgumentException Si no se han proporcionado datos válidos o si la configuración es incorrecta
     * @noinspection PhpUnused
     */
    public function getQueryWithBindings(): array {
        return [
            'query' => $this->getQuery(),
            'bindings' => $this->getBindings()
        ];
    }

    /**
     * Genera la consulta SQL para una inserción simple
     *
     * Construye una sentencia INSERT para un único registro, utilizando
     * marcadores de posición (?) para los valores que serán vinculados
     * posteriormente.
     *
     * @return string Consulta SQL generada para inserción simple
     */
    protected function getSingleInsertQuery(): string {
        $grammar = $this->connection->getDriver()->getGrammar();
        $columns = array_keys($this->data);
        $placeholders = array_fill(0, count($columns), '?');

        $columnsStr = $grammar->columnize($columns);
        $placeholdersStr = implode(', ', $placeholders);
        $table = $grammar->wrapTable($this->table);

        /** @noinspection SqlNoDataSourceInspection */
        return "INSERT INTO $table ($columnsStr) VALUES ($placeholdersStr)";
    }

    /**
     * Genera la consulta SQL para una inserción múltiple
     *
     * Construye una sentencia INSERT que permite insertar múltiples registros
     * en una sola operación. Valida que todas las filas tengan la misma
     * estructura de columnas.
     *
     * @return string Consulta SQL generada para inserción múltiple
     * @throws InvalidArgumentException Si no hay datos o si las filas tienen diferentes columnas
     */
    protected function getMultipleInsertQuery(): string {
        if (empty($this->data)) {
            throw new InvalidArgumentException('No data provided for insert');
        }

        // Usar las columnas del primer registro
        $columns = array_keys($this->data[0]);

        // Validar que todas las filas tengan las mismas columnas
        foreach ($this->data as $index => $row) {
            $rowColumns = array_keys($row);

            if ($rowColumns !== $columns) {
                // Comparar arrays para dar mejor mensaje de error
                $missing = array_diff($columns, $rowColumns);
                $extra = array_diff($rowColumns, $columns);

                $errorMsg = "Row $index has different columns than the first row.";

                if (!empty($missing)) {
                    $errorMsg .= " Missing columns: " . implode(', ', $missing) . ".";
                }

                if (!empty($extra)) {
                    $errorMsg .= " Extra columns: " . implode(', ', $extra) . ".";
                }

                throw new InvalidArgumentException($errorMsg);
            }
        }

        // Crear placeholders para cada fila
        $valueSets = [];
        foreach ($this->data as $ignored) {
            $placeholders = array_fill(0, count($columns), '?');
            $valueSets[] = '(' . implode(', ', $placeholders) . ')';
        }

        $valuesStr = implode(', ', $valueSets);
        $grammar = $this->connection->getDriver()->getGrammar();
        $columnsStr = $grammar->columnize($columns);
        $table = $grammar->wrapTable($this->table);

        /** @noinspection SqlNoDataSourceInspection */
        return "INSERT INTO $table ($columnsStr) VALUES $valuesStr";
    }

    /**
     * Obtiene los valores de los parámetros vinculados (bindings) para la consulta
     *
     * Prepara y retorna un array con todos los valores que serán vinculados
     * a los marcadores de posición en la consulta SQL, manteniendo el orden
     * correcto tanto para inserciones simples como múltiples.
     *
     * @return array Array con los valores de los bindings en el orden correspondiente
     */
    public function getBindings(): array {
        if ($this->multiple) {
            $bindings = [];
            $columns = array_keys($this->data[0]);

            foreach ($this->data as $row) {
                foreach ($columns as $col) {
                    $bindings[] = $row[$col] ?? null;
                }
            }

            return $bindings;
        }

        return array_values($this->data);
    }

    /**
     * Ejecuta la consulta INSERT
     *
     * Ejecuta la sentencia INSERT configurada en la base de datos,
     * utilizando los valores vinculados para una ejecución segura.
     *
     * @return bool TRUE si la ejecución fue exitosa
     */
    public function execute(): bool {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();

        $this->connection->execute($sql, $bindings);
        return true;
    }

    /**
     * Ejecuta la consulta INSERT y retorna el ID del último registro insertado
     *
     * Ejecuta la inserción y recupera el ID autogenerado del último registro
     * insertado. Este método solo funciona con inserciones simples y requiere
     * que la tabla tenga una columna de autoincremento.
     *
     * En motores con soporte de `RETURNING` (ej. PostgreSQL) se usa `INSERT ... RETURNING`
     * para leer el valor generado, lo que funciona con cualquier tipo de clave (serial,
     * identity, uuid). En el resto se cae a `lastInsertId()`.
     *
     * @param string|null $primaryKey Columna de clave primaria a devolver (RETURNING) o
     *                                nombre de secuencia para lastInsertId
     * @return string|false ID del registro insertado o FALSE en caso de error
     * @throws LogicException Si se intenta obtener el ID en una inserción múltiple
     * @noinspection PhpUnused
     */
    public function executeAndGetId(?string $primaryKey = null): string|false {
        if ($this->multiple) {
            throw new LogicException('Cannot get last insert ID for multiple inserts');
        }

        $grammar = $this->connection->getDriver()->getGrammar();

        if ($primaryKey !== null && $grammar->supportsReturning()) {
            $sql = $this->getQuery() . ' ' . $grammar->compileReturning([$primaryKey]);
            $row = $this->connection->queryFirst($sql, $this->getBindings());

            if ($row === null) {
                return false;
            }

            $row = (array)$row;
            return isset($row[$primaryKey]) ? (string)$row[$primaryKey] : false;
        }

        $this->execute();
        return $this->connection->lastInsertId($primaryKey);
    }

    /**
     * Ejecuta la consulta INSERT y retorna el número de filas afectadas
     *
     * Ejecuta la sentencia INSERT y retorna el número total de registros
     * que fueron insertados exitosamente en la base de datos.
     *
     * @return int Número total de filas insertadas
     * @noinspection PhpUnused
     */
    public function executeAndCount(): int {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();

        $stmt = $this->connection->execute($sql, $bindings);
        return $stmt->rowCount();
    }
}
