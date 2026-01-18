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

/**
 * Representa la cláusula SELECT en una consulta SQL.
 * Esta clase maneja la construcción y manipulación de la parte SELECT de una consulta,
 * permitiendo especificar columnas y la opción DISTINCT.
 */
class SelectClause {
    /**
     * Array que almacena las columnas seleccionadas.
     * Si está vacío, se seleccionarán todas las columnas (*).
     *
     * @var array
     */
    protected array $columns = [];

    /**
     * Indica si se debe aplicar DISTINCT a la selección.
     * Cuando es true, elimina filas duplicadas del resultado.
     *
     * @var bool
     */
    protected bool $distinct = false;

    /**
     * Agrega una o más columnas a la cláusula SELECT.
     * Acepta tanto strings individuales como arrays de columnas.
     *
     * @param string|array ...$columns Columnas a agregar. Pueden ser nombres de columnas
     *                                individuales o arrays de columnas
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function addColumns(string|array ...$columns): self {
        foreach ($columns as $column) {
            if (is_array($column)) {
                $this->columns = array_merge($this->columns, $column);
            } else {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Establece si se debe usar DISTINCT en la consulta.
     * Cuando se activa, elimina las filas duplicadas del resultado.
     *
     * @param bool $distinct True para activar DISTINCT, false para desactivarlo
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function setDistinct(bool $distinct = true): self {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Verifica si se han especificado columnas en la cláusula SELECT.
     *
     * @return bool Retorna true si hay columnas especificadas, false si está vacío
     * @noinspection PhpUnused
     */
    public function hasColumns(): bool {
        return !empty($this->columns);
    }

    /**
     * Obtiene el array de columnas especificadas en la cláusula SELECT.
     *
     * @return array Array con las columnas seleccionadas
     * @noinspection PhpUnused
     */
    public function getColumns(): array {
        return $this->columns;
    }

    /**
     * Genera la sentencia SQL correspondiente a la cláusula SELECT.
     * Si no hay columnas especificadas, usa SELECT * como valor predeterminado.
     *
     * @return string Retorna la cláusula SELECT completa como una cadena SQL
     */
    public function toSQL(): string {
        if (empty($this->columns)) {
            $columns = '*';
        } else {
            $columns = '`' . implode('`, `', $this->columns) . '`';
        }

        $sql = 'SELECT';

        if ($this->distinct) {
            $sql .= ' DISTINCT';
        }

        $sql .= ' ' . $columns;

        return $sql;
    }

    /**
     * Reinicia la cláusula SELECT a su estado inicial.
     * Elimina todas las columnas especificadas y desactiva DISTINCT.
     *
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function reset(): self {
        $this->columns = [];
        $this->distinct = false;
        return $this;
    }
}
