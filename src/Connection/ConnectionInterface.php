<?php

namespace PhobosFramework\Database\Connection;

use PDO;
use PDOStatement;
use PhobosFramework\Database\Drivers\DriverInterface;

/**
 * Interface para manejar conexiones a base de datos
 */
interface ConnectionInterface {
    /**
     * Conecta a la base de datos
     *
     * @return void
     */
    public function connect(): void;

    /**
     * Desconecta de la base de datos
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Verifica si está conectado
     *
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Obtiene la instancia PDO
     *
     * @return PDO
     */
    public function getPDO(): PDO;

    /**
     * Ejecuta una query y retorna el statement
     *
     * @param string $sql SQL a ejecutar
     * @param array $params Parámetros para binding
     * @return PDOStatement
     */
    public function execute(string $sql, array $params = []): PDOStatement;

    /**
     * Ejecuta una query y retorna todas las filas
     *
     * @param string $sql SQL a ejecutar
     * @param array $params Parámetros para binding
     * @return array
     */
    public function query(string $sql, array $params = []): array;

    /**
     * Ejecuta una query y retorna la primera fila
     *
     * @param string $sql SQL a ejecutar
     * @param array $params Parámetros para binding
     * @return array|null
     */
    public function queryFirst(string $sql, array $params = []): ?array;

    /**
     * Inicia una transacción
     *
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * Commitea una transacción
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * Hace rollback de una transacción
     *
     * @return bool
     */
    public function rollback(): bool;

    /**
     * Verifica si está en una transacción
     *
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * Obtiene el último ID insertado
     *
     * @param string|null $sequence Nombre de secuencia (para PostgreSQL)
     * @return string|false
     */
    public function lastInsertId(?string $sequence = null): string|false;

    /**
     * Obtiene el nombre de la conexión
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Obtiene el driver utilizado
     *
     * @return string
     */
    public function getDriverName(): string;

    /**
     * Obtiene la instancia del driver
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface;
}
