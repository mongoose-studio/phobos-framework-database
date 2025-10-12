<?php

namespace PhobosFramework\Database\Connection;

use Exception;
use PDO;
use PDOStatement;
use PDOException;
use PhobosFramework\Database\Drivers\DriverInterface;
use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\QueryException;

/**
 * Implementación de conexión PDO
 */
class PDOConnection implements ConnectionInterface {
    protected ?PDO $pdo = null;
    protected array $config;
    protected DriverInterface $driver;
    protected string $name;

    /**
     * Constructor
     *
     * @param string $name Nombre de la conexión
     * @param array $config Configuración de conexión
     * @param DriverInterface $driver Driver a utilizar
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
     */
    public function query(string $sql, array $params = []): array {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function queryFirst(string $sql, array $params = []): ?array {
        $stmt = $this->execute($sql, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * {@inheritdoc}
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
