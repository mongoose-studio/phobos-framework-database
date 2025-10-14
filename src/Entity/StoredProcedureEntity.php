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

namespace PhobosFramework\Database\Entity;

use PhobosFramework\Database\Exceptions\InvalidArgumentException;

/**
 * Clase base para entidades que representan procedimientos almacenados en la base de datos.
 * Esta clase proporciona la funcionalidad básica para ejecutar y gestionar
 * procedimientos almacenados como entidades del sistema.
 * @noinspection PhpUnused
 */
abstract class StoredProcedureEntity extends EntityManager implements StoredProcedure {
    /**
     * Array de parámetros que define la configuración del procedimiento almacenado.
     * Debe ser definido en cada clase hija que implemente un procedimiento específico.
     * Formato esperado:
     * [
     *     'nombreParametro' => ['required' => true|false, ...],
     *     ...
     * ]
     */
    protected static array $parameters = [];

    /**
     * {@inheritdoc}
     */
    public static function call(array $params = [], bool $dryRun = false): string|array {
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
            return static::hydrateMany($results);
        }

        return $results;
    }

    /**
     * Valida que los parámetros proporcionados cumplan con la configuración definida.
     * Verifica que todos los parámetros requeridos estén presentes en la llamada
     * al procedimiento almacenado.
     *
     * @param array $params Arreglo de parámetros a validar
     * @return void
     * @throws \InvalidArgumentException Si falta algún parámetro requerido
     */
    protected static function validateParameters(array $params): void {
        if (empty(static::$parameters)) {
            return; // Sin validación si no hay parámetros definidos
        }

        foreach (static::$parameters as $paramName => $paramConfig) {
            $required = $paramConfig['required'] ?? false;

            if ($required && !isset($params[$paramName])) {
                throw new InvalidArgumentException(
                    "Required parameter '$paramName' is missing"
                );
            }
        }
    }

    /**
     * Determina si los resultados del procedimiento deben ser hidratados como objetos de la entidad.
     * Por defecto retorna false. Las clases hijas pueden sobrescribir este método
     * para permitir la hidratación automática de resultados.
     *
     * @return bool true si los resultados deben ser hidratados, false en caso contrario
     */
    protected static function shouldHydrate(): bool {
        return false;
    }

    /**
     * Retorna el array de configuración de parámetros definidos para el procedimiento almacenado.
     * Este método permite acceder a la definición de parámetros desde fuera de la clase.
     *
     * @return array Configuración de parámetros del procedimiento
     * @noinspection PhpUnused
     */
    public static function getParameters(): array {
        return static::$parameters;
    }
}
