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

use PhobosFramework\Database\Exceptions\TransactionException;

/**
 * Administrador de transacciones de base de datos que soporta transacciones anidadas mediante savepoints.
 *
 * Esta clase proporciona una interfaz para manejar transacciones de base de datos,
 * permitiendo anidar múltiples niveles de transacciones usando savepoints cuando
 * el driver de base de datos lo soporta. Mantiene un registro del nivel de anidamiento
 * y los savepoints activos.
 */
class TransactionManager {
    /**
     * @var ConnectionInterface Conexión a la base de datos
     */
    protected ConnectionInterface $connection;

    /**
     * @var int Nivel actual de anidamiento de transacciones
     */
    protected int $transactionLevel = 0;

    /**
     * @var array Lista de savepoints activos
     */
    protected array $savepoints = [];

    /**
     * Crea una nueva instancia del administrador de transacciones
     *
     * @param ConnectionInterface $connection Instancia de la conexión a la base de datos
     */
    public function __construct(ConnectionInterface $connection) {
        $this->connection = $connection;
    }

    /**
     * Inicia una nueva transacción o crea un savepoint si ya existe una transacción activa
     *
     * Este método maneja automáticamente el anidamiento de transacciones. Si no hay una
     * transacción activa, inicia una nueva. Si ya existe una transacción, crea un savepoint.
     *
     * @return string|null Nombre del savepoint creado, o null si se inició una transacción raíz
     * @throws TransactionException
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
     * Confirma una transacción o libera un savepoint específico
     *
     * Si se trata de una transacción raíz (nivel 1), realiza un commit completo.
     * Si es una transacción anidada, libera el savepoint correspondiente.
     *
     * @param string|null $savepoint Nombre del savepoint a liberar, o null para commit de transacción raíz
     * @return void
     * @throws TransactionException Si no hay transacción activa o el savepoint no existe
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
     * Revierte una transacción o vuelve a un punto de guardado específico
     *
     * Si se proporciona un nombre de savepoint, revierte hasta ese punto.
     * Si no se proporciona savepoint, revierte toda la transacción.
     *
     * @param string|null $savepoint Nombre del punto de guardado, o null para revertir toda la transacción
     * @return void
     * @throws TransactionException Si no hay transacción activa o el savepoint no existe
     */
    public function rollback(?string $savepoint = null): void {
        if ($this->transactionLevel === 0) {
            throw new TransactionException('No active transaction to rollback');
        }

        if ($savepoint !== null) {
            // Buscar el índice del savepoint
            $index = array_search($savepoint, $this->savepoints, true);

            if ($index === false) {
                throw new TransactionException(
                    "Savepoint '$savepoint' not found in active savepoints"
                );
            }

            $this->rollbackToSavepoint($savepoint);
            // Ajustar el nivel de transacción: índice + 2 (1 para base + 1 para el savepoint)
            $this->transactionLevel = $index + 2;
        } else {
            $this->connection->rollback();
            $this->transactionLevel = 0;
            $this->savepoints = [];
        }
    }

    /**
     * Obtiene el nivel actual de anidamiento de la transacción
     *
     * @return int Nivel actual de anidamiento, donde 0 significa que no hay transacción activa
     */
    public function getLevel(): int {
        return $this->transactionLevel;
    }

    /**
     * Verifica si existe una transacción activa en este momento
     *
     * @return bool true si hay una transacción activa, false en caso contrario
     */
    public function isActive(): bool {
        return $this->transactionLevel > 0;
    }

    /**
     * Crea un punto de guardado (savepoint) en la transacción actual
     *
     * @param string $name Nombre del punto de guardado
     * @return void
     * @throws TransactionException Si el driver no soporta puntos de guardado
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
     * Revierte la transacción hasta un punto de guardado específico
     *
     * @param string $name Nombre del punto de guardado al que se desea revertir
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
     * Libera un punto de guardado previamente creado
     *
     * @param string $name Nombre del punto de guardado a liberar
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
     * Genera un nombre único para un punto de guardado
     *
     * @return string Nombre único generado para el punto de guardado
     */
    protected function generateSavepointName(): string {
        return 'sp_' . $this->transactionLevel . '_' . uniqid();
    }

    /**
     * Obtiene la lista de todos los puntos de guardado activos
     *
     * @return array Lista de nombres de puntos de guardado activos
     * @noinspection PhpUnused
     */
    public function getSavepoints(): array {
        return $this->savepoints;
    }
}
