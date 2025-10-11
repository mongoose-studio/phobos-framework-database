<?php

namespace PhobosFramework\Database\Drivers;

use PDO;
use PhobosFramework\Database\Exceptions\ConfigurationException;

/**
 * Clase abstracta con lógica común para todos los drivers PDO
 */
abstract class AbstractDriver implements DriverInterface {
    /**
     * Niveles de aislamiento estándar
     */
    public const ISOLATION_READ_UNCOMMITTED = 'READ UNCOMMITTED';
    public const ISOLATION_READ_COMMITTED = 'READ COMMITTED';
    public const ISOLATION_REPEATABLE_READ = 'REPEATABLE READ';
    public const ISOLATION_SERIALIZABLE = 'SERIALIZABLE';

    /**
     * {@inheritdoc}
     */
    public function getPDOOptions(array $config): array {
        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        return array_merge($defaults, $config['options'] ?? []);
    }

    /**
     * {@inheritdoc}
     */
    public function configure(PDO $pdo, array $config): void {
        // Configuración por defecto (puede ser sobreescrita por drivers específicos)
        if (isset($config['charset'])) {
            $pdo->exec("SET NAMES '{$config['charset']}'");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supportsSavepoints(): bool {
        return true; // La mayoría de drivers modernos soportan savepoints
    }

    /**
     * {@inheritdoc}
     */
    public function getSavepointSQL(string $name): string {
        return "SAVEPOINT {$this->quoteIdentifier($name)}";
    }

    /**
     * {@inheritdoc}
     */
    public function getRollbackSavepointSQL(string $name): string {
        return "ROLLBACK TO SAVEPOINT {$this->quoteIdentifier($name)}";
    }

    /**
     * {@inheritdoc}
     */
    public function getReleaseSavepointSQL(string $name): string {
        return "RELEASE SAVEPOINT {$this->quoteIdentifier($name)}";
    }

    /**
     * {@inheritdoc}
     */
    public function getSetIsolationLevelSQL(string $level): string {
        return "SET TRANSACTION ISOLATION LEVEL {$level}";
    }

    /**
     * {@inheritdoc}
     */
    public function getLastInsertId(PDO $pdo, ?string $sequence = null): string|false {
        return $pdo->lastInsertId($sequence);
    }

    /**
     * Valida que la configuración tenga los campos requeridos
     *
     * @param array $config Configuración a validar
     * @param array $required Campos requeridos
     * @return void
     * @throws ConfigurationException
     */
    protected function validateConfig(array $config, array $required): void {
        foreach ($required as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException(
                    "Missing required configuration field: {$field}"
                );
            }
        }
    }
}
