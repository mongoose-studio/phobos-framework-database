<?php

namespace PhobosFramework\Database\Drivers;

use PDO;

/**
 * Interface que deben implementar todos los drivers de base de datos
 */
interface DriverInterface {
    /**
     * Obtiene el DSN (Data Source Name) para la conexión PDO
     *
     * @param array $config Configuración de conexión
     * @return string
     */
    public function getDSN(array $config): string;

    /**
     * Obtiene las opciones PDO específicas del driver
     *
     * @param array $config Configuración de conexión
     * @return array
     */
    public function getPDOOptions(array $config): array;

    /**
     * Configura la conexión después de establecerla
     *
     * @param PDO $pdo Instancia PDO conectada
     * @param array $config Configuración de conexión
     * @return void
     */
    public function configure(PDO $pdo, array $config): void;

    /**
     * Obtiene el nombre del driver
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Verifica si el driver soporta savepoints
     *
     * @return bool
     */
    public function supportsSavepoints(): bool;

    /**
     * Obtiene la sintaxis para crear un savepoint
     *
     * @param string $name Nombre del savepoint
     * @return string
     */
    public function getSavepointSQL(string $name): string;

    /**
     * Obtiene la sintaxis para hacer rollback a un savepoint
     *
     * @param string $name Nombre del savepoint
     * @return string
     */
    public function getRollbackSavepointSQL(string $name): string;

    /**
     * Obtiene la sintaxis para liberar un savepoint
     *
     * @param string $name Nombre del savepoint
     * @return string
     */
    public function getReleaseSavepointSQL(string $name): string;

    /**
     * Obtiene la sintaxis para setear el isolation level
     *
     * @param string $level Nivel de aislamiento
     * @return string
     */
    public function getSetIsolationLevelSQL(string $level): string;

    /**
     * Obtiene el último ID insertado (considerando drivers que no usan lastInsertId)
     *
     * @param PDO $pdo Instancia PDO
     * @param string|null $sequence Nombre de secuencia (PostgreSQL)
     * @return string|false
     */
    public function getLastInsertId(PDO $pdo, ?string $sequence = null): string|false;

    /**
     * Escapa un identificador (tabla, columna, etc) según el driver
     *
     * @param string $identifier Identificador a escapar
     * @return string
     */
    public function quoteIdentifier(string $identifier): string;
}
