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

namespace PhobosFramework\Database\Drivers;

use PDO;
use PhobosFramework\Database\Exceptions\ConfigurationException;

/**
 * Clase abstracta que proporciona la lógica común para todos los drivers PDO.
 *
 * Esta clase implementa funcionalidades base que son compartidas entre diferentes
 * drivers de base de datos, incluyendo la gestión de transacciones, savepoints
 * y configuración de conexión.
 * @noinspection PhpUnused
 */
abstract class AbstractDriver implements DriverInterface {
    /**
     * Niveles de aislamiento estándar para transacciones.
     *
     * Estos niveles determinan cómo las transacciones interactúan entre sí:
     * - READ UNCOMMITTED: Nivel más bajo, permite lecturas sucias
     * - READ COMMITTED: Evita lecturas sucias
     * - REPEATABLE READ: Garantiza lecturas consistentes
     * - SERIALIZABLE: Nivel más alto de aislamiento
     */
    public const string ISOLATION_READ_UNCOMMITTED = 'READ UNCOMMITTED';
    public const string ISOLATION_READ_COMMITTED = 'READ COMMITTED';
    public const string ISOLATION_REPEATABLE_READ = 'REPEATABLE READ';
    public const string ISOLATION_SERIALIZABLE = 'SERIALIZABLE';

    /**
     * Obtiene las opciones de configuración para PDO.
     *
     * Combina las opciones predeterminadas con las proporcionadas en la configuración.
     * Las opciones por defecto incluyen:
     * - Modo de error en excepciones
     * - Modo de recuperación asociativo
     * - Desactivación de preparación emulada
     * - Desactivación de conversión a cadenas
     *
     * @param array $config Configuración proporcionada por el usuario
     * @return array Opciones combinadas para PDO
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
     * Configura una instancia PDO con los parámetros especificados.
     *
     * Aplica configuraciones específicas a la conexión PDO, como el charset.
     * Incluye validación de seguridad para prevenir inyección SQL.
     *
     * @param PDO $pdo Instancia PDO a configurar
     * @param array $config Configuración con parámetros específicos
     * @throws ConfigurationException Si el charset contiene caracteres no permitidos
     */
    public function configure(PDO $pdo, array $config): void {
        // Configuración por defecto (puede ser sobreescrita por drivers específicos)
        if (isset($config['charset'])) {
            // Sanitizar charset para prevenir inyección SQL
            // Solo permitir caracteres alfanuméricos, guiones y guiones bajos
            $charset = preg_replace('/[^a-zA-Z0-9_-]/', '', $config['charset']);

            if ($charset !== $config['charset']) {
                throw new ConfigurationException(
                    "Invalid charset '{$config['charset']}'. Only alphanumeric, dash and underscore characters are allowed."
                );
            }

            $pdo->exec("SET NAMES '$charset'");
        }
    }

    /**
     * Verifica si el driver soporta savepoints.
     *
     * Por defecto retorna true ya que la mayoría de los drivers
     * modernos soportan esta funcionalidad.
     *
     * @return bool True si soporta savepoints, false en caso contrario
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
        return "SET TRANSACTION ISOLATION LEVEL $level";
    }

    /**
     * {@inheritdoc}
     */
    public function getLastInsertId(PDO $pdo, ?string $sequence = null): string|false {
        return $pdo->lastInsertId($sequence);
    }

    /**
     * Valida que la configuración contenga todos los campos requeridos.
     *
     * Verifica la presencia y no vaciedad de los campos especificados
     * en la configuración proporcionada.
     *
     * @param array $config Configuración a validar
     * @param array $required Lista de campos que deben estar presentes
     * @return void
     * @throws ConfigurationException Si falta algún campo requerido o está vacío
     */
    protected function validateConfig(array $config, array $required): void {
        foreach ($required as $field) {
            /** @noinspection PhpConditionCheckedByNextConditionInspection */
            if (!isset($config[$field]) || empty($config[$field])) {
                throw new ConfigurationException(
                    "Missing required configuration field: $field"
                );
            }
        }
    }
}
