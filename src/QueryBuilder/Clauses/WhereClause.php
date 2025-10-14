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

namespace PhobosFramework\Database\QueryBuilder\Clauses;

use PhobosFramework\Database\Exceptions\InvalidArgumentException;

/**
 * Representa la cláusula WHERE en consultas SQL
 *
 * Esta clase maneja la construcción de cláusulas WHERE para consultas SQL,
 * permitiendo condiciones múltiples con diferentes operadores lógicos.
 * Soporta tanto condiciones simples como complejas, incluyendo operadores IN
 * y comparaciones personalizadas.
 * @noinspection PhpUnused
 */
class WhereClause {

    /**
     * @var array
     * @noinspection PhpUnused
     */
    protected array $conditions = [];
    protected array $bindings = [];

    /**
     * Agrega una condición WHERE a la consulta
     *
     * Permite agregar condiciones en formato string o array. Para condiciones array,
     * soporta los siguientes formatos:
     * - ['columna = ?' => valor]
     * - ['columna > ?' => valor]
     * - ['columna IN (?)' => [1,2,3]]
     * - ['columna' => valor] (se convierte automáticamente a 'columna = ?')
     *
     * @param array|string $conditions Condiciones en formato string o array
     * @param string $operator Operador lógico para concatenar condiciones (AND/OR)
     * @param mixed ...$params Valores para los placeholders cuando se usa formato string
     * @return self Retorna la instancia actual para encadenamiento
     * @noinspection PhpUnused
     */
    public function addCondition(array|string $conditions, string $operator = 'AND', mixed ...$params): self {
        if (is_array($conditions)) {
            $this->addArrayConditions($conditions, $operator);
        } else {
            $this->addStringCondition($conditions, $operator, $params);
        }

        return $this;
    }

    /**
     * Agrega condiciones desde un array a la cláusula WHERE
     *
     * Procesa diferentes formatos de condiciones en array:
     * - Condiciones con placeholder: ['columna = ?' => valor]
     * - Operadores de comparación: ['columna > ?' => valor]
     * - Cláusulas IN: ['columna IN (?)' => [1,2,3]]
     * - Condiciones simples: ['columna' => valor]
     * - Condiciones raw: [0 => 'columna IS NULL']
     *
     * @param array $conditions Array de condiciones a procesar
     * @param string $operator Operador lógico (AND/OR) para concatenar múltiples condiciones
     * @return void
     * @throws InvalidArgumentException Si se usa IN con array vacío
     */
    protected function addArrayConditions(array $conditions, string $operator): void {
        foreach ($conditions as $condition => $value) {
            // Si la key es numérica, asumir que es una condición raw
            if (is_numeric($condition)) {
                $this->conditions[] = [
                    'sql' => $value,
                    'operator' => $operator,
                    'bindings' => []
                ];
                continue;
            }

            // Procesar condición con placeholder
            if (str_contains($condition, '?')) {
                // Manejar IN clause con arrays
                if (is_array($value) && stripos($condition, 'IN') !== false) {
                    if (empty($value)) {
                        throw new InvalidArgumentException(
                            'IN clause cannot have an empty array. Use "1=0" or equivalent for always-false conditions.'
                        );
                    }
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $sql = str_replace('?', $placeholders, $condition);
                    $bindings = $value;
                } else {
                    $sql = $condition;
                    $bindings = is_array($value) ? $value : [$value];
                }

                $this->conditions[] = [
                    'sql' => $sql,
                    'operator' => $operator,
                    'bindings' => $bindings
                ];

                $this->bindings = array_merge($this->bindings, $bindings);
            } else {
                // Condición sin placeholder, agregar = ?
                $this->conditions[] = [
                    'sql' => $condition . ' = ?',
                    'operator' => $operator,
                    'bindings' => [$value]
                ];

                $this->bindings[] = $value;
            }
        }
    }

    /**
     * Agrega una condición en formato string a la cláusula WHERE
     *
     * Permite agregar condiciones SQL personalizadas con placeholders.
     * Los valores para los placeholders se proporcionan en el parámetro $params.
     *
     * @param string $condition Condición SQL con placeholders (?)
     * @param string $operator Operador lógico para concatenar con condiciones existentes
     * @param array $params Valores que reemplazarán los placeholders
     * @return void
     */
    protected function addStringCondition(string $condition, string $operator, array $params): void {
        $this->conditions[] = [
            'sql' => $condition,
            'operator' => $operator,
            'bindings' => $params
        ];

        $this->bindings = array_merge($this->bindings, $params);
    }

    /**
     * Verifica si la cláusula WHERE tiene condiciones
     *
     * @return bool true si existen condiciones, false en caso contrario
     * @noinspection PhpUnused
     */
    public function hasConditions(): bool {
        return !empty($this->conditions);
    }

    /**
     * Obtiene los valores de binding para la cláusula WHERE
     *
     * Retorna un array con todos los valores que reemplazarán
     * los placeholders en la consulta SQL final.
     *
     * @return array Array de valores para binding
     * @noinspection PhpUnused
     */
    public function getBindings(): array {
        return $this->bindings;
    }

    /**
     * Genera el SQL de la cláusula WHERE
     *
     * Construye la cadena SQL para la cláusula WHERE combinando todas
     * las condiciones con sus respectivos operadores lógicos.
     * Si no hay condiciones, retorna una cadena vacía.
     *
     * @return string Cadena SQL de la cláusula WHERE o cadena vacía
     */
    public function toSQL(): string {
        if (empty($this->conditions)) {
            return '';
        }

        $sql = 'WHERE ';
        $parts = [];

        foreach ($this->conditions as $index => $condition) {
            if ($index > 0) {
                $parts[] = $condition['operator'];
            }
            $parts[] = $condition['sql'];
        }

        $sql .= implode(' ', $parts);

        return $sql;
    }

    /**
     * Resetea la cláusula WHERE
     *
     * Elimina todas las condiciones y bindings almacenados,
     * dejando la cláusula en su estado inicial.
     *
     * @return self Retorna la instancia actual para encadenamiento
     * @noinspection PhpUnused
     */
    public function reset(): self {
        $this->conditions = [];
        $this->bindings = [];
        return $this;
    }
}
