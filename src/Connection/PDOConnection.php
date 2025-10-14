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

namespace PhobosFramework\Database\Connection;

use Exception;
use PDO;
use PDOStatement;
use PDOException;
use PhobosFramework\Database\Drivers\DriverInterface;
use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\QueryException;

/**
 * Implementación de conexión PDO para bases de datos.
 *
 * Esta clase proporciona una implementación de la interfaz ConnectionInterface utilizando PDO,
 * permitiendo una conexión y manipulación uniforme de diferentes bases de datos relacionales.
 * Gestiona conexiones, transacciones y consultas a la base de datos.
 */
class PDOConnection implements ConnectionInterface {
    /**
     * @var PDO|null Instancia de PDO para la conexión a la base de datos
     */
    protected ?PDO $pdo = null;

    /**
     * @var array Configuración de la conexión
     */
    protected array $config;

    /**
     * @var DriverInterface Driver utilizado para la conexión
     */
    protected DriverInterface $driver;

    /**
     * @var string Nombre identificador de la conexión
     */
    protected string $name;

    /**
     * Constructor de la clase PDOConnection.
     *
     * Inicializa una nueva instancia de conexión PDO con los parámetros especificados.
     * No establece la conexión inmediatamente, esta se realizará cuando sea necesaria.
     *
     * @param string $name Nombre identificador único de la conexión
     * @param array $config Arreglo con la configuración de la conexión (host, puerto, credenciales, etc.)
     * @param DriverInterface $driver Instancia del driver específico para el tipo de base de datos
     */
    public function __construct(string $name, array $config, DriverInterface $driver) {
        $this->name = $name;
        $this->config = $config;
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void {
        if ($this->isConnected()) {
            return;
        }

        try {
            $dsn = $this->driver->getDSN($this->config);
            $options = $this->driver->getPDOOptions($this->config);
            $username = $this->config['username'] ?? null;
            $password = $this->config['password'] ?? null;

            $this->pdo = new PDO($dsn, $username, $password, $options);
            $this->driver->configure($this->pdo, $this->config);
        } catch (PDOException $e) {
            throw new ConnectionException(
                "Failed to connect to database: {$e->getMessage()}",
                (int)$e->getCode(),
                $e,
                ['connection' => $this->name, 'driver' => $this->driver->getName()]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void {
        $this->pdo = null;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool {
        return $this->pdo !== null;
    }

    /**
     * {@inheritdoc}
     * @throws ConnectionException
     */
    public function getPDO(): PDO {
        if (!$this->isConnected()) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * {@inheritdoc}
     * @throws QueryException
     */
    public function execute(string $sql, array $params = []): PDOStatement {
        try {
            $pdo = $this->getPDO();
            $stmt = $pdo->prepare($sql);
            /** @noinspection PhpUnusedLocalVariableInspection */
            $_ = $stmt->execute($params);
            return $stmt;
        } catch (Exception $e) {
            throw new QueryException(
                "Query execution failed: {$e->getMessage()}",
                (int)$e->getCode(),
                $e,
                $sql,
                $params,
                ['connection' => $this->name]
            );
        }
    }

    /**
     * {@inheritdoc}
     * @throws QueryException
     */
    public function query(string $sql, array $params = []): array {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     * @throws QueryException
     */
    public function queryFirst(string $sql, array $params = []): ?array {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * {@inheritdoc}
     * @throws ConnectionException
     */
    public function beginTransaction(): bool {
        try {
            return $this->getPDO()->beginTransaction();
        } catch (PDOException $e) {
            throw new ConnectionException(
                "Failed to begin transaction: {$e->getMessage()}",
                (int)$e->getCode(),
                $e,
                ['connection' => $this->name]
            );
        }
    }

    /**
     * {@inheritdoc}
     * @throws ConnectionException
     */
    public function commit(): bool {
        try {
            return $this->getPDO()->commit();
        } catch (PDOException $e) {
            throw new ConnectionException(
                "Failed to commit transaction: {$e->getMessage()}",
                (int)$e->getCode(),
                $e,
                ['connection' => $this->name]
            );
        }
    }

    /**
     * {@inheritdoc}
     * @throws ConnectionException
     */
    public function rollback(): bool {
        try {
            return $this->getPDO()->rollBack();
        } catch (PDOException $e) {
            throw new ConnectionException(
                "Failed to rollback transaction: {$e->getMessage()}",
                (int)$e->getCode(),
                $e,
                ['connection' => $this->name]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction(): bool {
        return $this->isConnected() && $this->pdo->inTransaction();
    }

    /**
     * {@inheritdoc}
     * @throws ConnectionException
     */
    public function lastInsertId(?string $sequence = null): string|false {
        return $this->driver->getLastInsertId($this->getPDO(), $sequence);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverName(): string {
        return $this->driver->getName();
    }

    /**
     * Obtiene el driver utilizado
     *
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface {
        return $this->driver;
    }
}
