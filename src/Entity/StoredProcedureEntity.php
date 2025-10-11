<?php

namespace PhobosFramework\Database\Entity;

use PhobosFramework\Database\Exceptions\InvalidArgumentException;

/**
 * Clase base para entidades que son stored procedures
 */
abstract class StoredProcedureEntity extends EntityManager implements StoredProcedure {
    /**
     * Parámetros del procedimiento (debe ser definido en cada clase)
     */
    protected static array $parameters = [];

    /**
     * {@inheritdoc}
     */
    public static function call(array $params = [], bool $dryRun = false): mixed {
        // Validar parámetros
        static::validateParameters($params);

        // Construir CALL statement
        $placeholders = array_fill(0, count($params), '?');
        $sql = 'CALL ' . static::getIdentification() . '(' . implode(',', $placeholders) . ')';

        if ($dryRun) {
            return $sql;
        }

        // Ejecutar
        $conn = static::getConnection();
        $stmt = $conn->execute($sql, array_values($params));

        // Retornar resultados
        $results = $stmt->fetchAll();

        // Si el procedimiento retorna objetos de esta entidad, hidratar
        if (!empty($results) && static::shouldHydrate()) {
            return static::hydrateMany($results, false);
        }

        return $results;
    }

    /**
     * Valida los parámetros del procedimiento
     *
     * @param array $params
     * @return void
     * @throws \InvalidArgumentException
     */
    protected static function validateParameters(array $params): void {
        if (empty(static::$parameters)) {
            return; // Sin validación si no hay parámetros definidos
        }

        foreach (static::$parameters as $paramName => $paramConfig) {
            $required = $paramConfig['required'] ?? false;

            if ($required && !isset($params[$paramName])) {
                throw new InvalidArgumentException(
                    "Required parameter '{$paramName}' is missing"
                );
            }
        }
    }

    /**
     * Determina si debe hidratar los resultados como objetos de la entidad
     * Por defecto false, puede ser sobrescrito
     *
     * @return bool
     */
    protected static function shouldHydrate(): bool {
        return false;
    }

    /**
     * Obtiene los parámetros definidos
     *
     * @return array
     */
    public static function getParameters(): array {
        return static::$parameters;
    }
}
