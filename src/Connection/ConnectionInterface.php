<?php

/**
 * # Phobos Framework
 *
 * Para la información completa acerca del copyright y la licencia,
 * por favor vea el archivo LICENSE que se distribuye con este código fuente.
 *
 * @author      Marcel Rojas <marcelrojas16@gmail.com>
 * @copyright   Copyright (c) 2012-2025, Marcel Rojas <marcelrojas16@gmail.com>
 */

namespace PhobosFramework\Database\Connection;

use PDO;
use PDOException;
use PDOStatement;
use PhobosFramework\Database\Drivers\DriverInterface;
use PhobosFramework\Database\Exceptions\ConnectionException;

/**
 * Interface que define los métodos necesarios para gestionar conexiones a bases de datos.
 *
 * Esta interfaz proporciona los métodos esenciales para establecer y administrar
 * conexiones a bases de datos, ejecutar consultas y gestionar transacciones.
 */
interface ConnectionInterface {
    /**
     * Establece la conexión con la base de datos.
     *
     * @return void
     * @throws ConnectionException Si la conexión falla
     */
    public function connect(): void;

    /**
     * Cierra la conexión con la base de datos.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Verifica si hay una conexión activa con la base de datos.
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Obtiene la instancia PDO de la conexión actual.
     *
     * @return PDO
     */
    public function getPDO(): PDO;

    /**
     * Ejecuta una consulta SQL preparada y retorna el objeto PDOStatement resultante.
     *
     * @param string $sql Consulta SQL a ejecutar
     * @param array $params Arreglo de parámetros para la consulta preparada
     * @return PDOStatement
     * @throws PDOException Si ocurre un error durante la ejecución
     */
    public function execute(string $sql, array $params = []): PDOStatement;

    /**
     * Ejecuta una consulta SQL y retorna todas las filas del resultado.
     *
     * @param string $sql Consulta SQL a ejecutar
     * @param array $params Parámetros para la consulta preparada
     * @return array
     */
    public function query(string $sql, array $params = []): array;

    /**
     * Ejecuta una consulta SQL y retorna la primera fila del resultado.
     *
     * @param string $sql Consulta SQL a ejecutar
     * @param array $params Parámetros para la consulta preparada
     * @return array|null
     */
    public function queryFirst(string $sql, array $params = []): array|object|null;

    /**
     * Inicia una nueva transacción en la base de datos.
     *
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * Confirma la transacción actual en la base de datos.
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * Revierte la transacción actual en la base de datos.
     *
     * @return bool
     */
    public function rollback(): bool;

    /**
     * Verifica si hay una transacción activa en la base de datos.
     *
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * Retorna el ID del último registro insertado en la base de datos.
     *
     * @param string|null $sequence Nombre de la secuencia (requerido para PostgreSQL)
     * @return string|false
     */
    public function lastInsertId(?string $sequence = null): string|false;

    /**
     * Retorna el nombre identificador de la conexión actual.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Retorna el nombre del controlador de base de datos utilizado.
     *
     * @return string
     */
    public function getDriverName(): string;

    /**
     * Retorna la instancia del controlador de base de datos.
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface;
}
