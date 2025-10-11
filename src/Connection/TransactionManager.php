<?php

namespace PhobosFramework\Database\Connection;

use PhobosFramework\Database\Exceptions\TransactionException;

/**
 * Maneja transacciones anidadas con savepoints
 */
class TransactionManager {
    protected ConnectionInterface $connection;
    protected int $transactionLevel = 0;
    protected array $savepoints = [];

    /**
     * Constructor
     *
     * @param ConnectionInterface $connection Conexión a usar
     */
    public function __construct(ConnectionInterface $connection) {
        $this->connection = $connection;
    }

    /**
     * Inicia una transacción o crea un savepoint
     *
     * @return string|null Nombre del savepoint creado (null si es transacción raíz)
     */
    public function begin(): ?string {
        $this->transactionLevel++;

        if ($this->transactionLevel === 1) {
            $this->connection->beginTransaction();
            return null;
        }

        // Transacción anidada: crear savepoint
        $savepointName = $this->generateSavepointName();
        $this->createSavepoint($savepointName);
        return $savepointName;
    }

    /**
     * Commitea la transacción o libera el savepoint
     *
     * @param string|null $savepoint Nombre del savepoint (null para transacción raíz)
     * @return void
     * @throws TransactionException
     */
    public function commit(?string $savepoint = null): void {
        if ($this->transactionLevel === 0) {
            throw new TransactionException('No active transaction to commit');
        }

        if ($this->transactionLevel === 1) {
            $this->connection->commit();
        } elseif ($savepoint !== null) {
            $this->releaseSavepoint($savepoint);
        }

        $this->transactionLevel--;
    }

    /**
     * Hace rollback de la transacción o vuelve a un savepoint
     *
     * @param string|null $savepoint Nombre del savepoint (null para rollback completo)
     * @return void
     */
    public function rollback(?string $savepoint = null): void {
        if ($this->transactionLevel === 0) {
            throw new TransactionException('No active transaction to rollback');
        }

        if ($savepoint !== null) {
            $this->rollbackToSavepoint($savepoint);
            $this->transactionLevel = array_search($savepoint, $this->savepoints) + 2;
        } else {
            $this->connection->rollback();
            $this->transactionLevel = 0;
            $this->savepoints = [];
        }
    }

    /**
     * Obtiene el nivel actual de transacción
     *
     * @return int
     */
    public function getLevel(): int {
        return $this->transactionLevel;
    }

    /**
     * Verifica si hay una transacción activa
     *
     * @return bool
     */
    public function isActive(): bool {
        return $this->transactionLevel > 0;
    }

    /**
     * Crea un savepoint
     *
     * @param string $name Nombre del savepoint
     * @return void
     */
    protected function createSavepoint(string $name): void {
        $driver = $this->connection->getDriver();

        if (!$driver->supportsSavepoints()) {
            throw new TransactionException(
                "Driver {$driver->getName()} does not support savepoints"
            );
        }

        $sql = $driver->getSavepointSQL($name);
        $this->connection->execute($sql);
        $this->savepoints[] = $name;
    }

    /**
     * Hace rollback a un savepoint
     *
     * @param string $name Nombre del savepoint
     * @return void
     */
    protected function rollbackToSavepoint(string $name): void {
        $driver = $this->connection->getDriver();
        $sql = $driver->getRollbackSavepointSQL($name);
        $this->connection->execute($sql);

        // Remover savepoints posteriores al que hicimos rollback
        $index = array_search($name, $this->savepoints);
        if ($index !== false) {
            $this->savepoints = array_slice($this->savepoints, 0, $index + 1);
        }
    }

    /**
     * Libera un savepoint
     *
     * @param string $name Nombre del savepoint
     * @return void
     */
    protected function releaseSavepoint(string $name): void {
        $driver = $this->connection->getDriver();
        $sql = $driver->getReleaseSavepointSQL($name);
        $this->connection->execute($sql);

        // Remover el savepoint liberado
        $index = array_search($name, $this->savepoints);
        if ($index !== false) {
            array_splice($this->savepoints, $index, 1);
        }
    }

    /**
     * Genera un nombre único para un savepoint
     *
     * @return string
     */
    protected function generateSavepointName(): string {
        return 'sp_' . $this->transactionLevel . '_' . uniqid();
    }

    /**
     * Obtiene todos los savepoints activos
     *
     * @return array
     */
    public function getSavepoints(): array {
        return $this->savepoints;
    }
}
