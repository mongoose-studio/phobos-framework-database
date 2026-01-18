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
 * Representa la cláusula ORDER BY en una consulta SQL.
 * Esta clase maneja la construcción y manipulación de la parte ORDER BY de una consulta.
 */
class OrderByClause {
    protected array $columns = [];

    /**
     * Agrega una o más columnas para ordenar los resultados.
     *
     * @param string|array $columns Columnas para ordenar. Puede ser una cadena única
     *                             (ej: 'nombre ASC') o un array de columnas
     *                             (ej: ['nombre ASC', 'id DESC'])
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function addColumns(string|array $columns): self {
        if (is_array($columns)) {
            $this->columns = array_merge($this->columns, $columns);
        } else {
            $this->columns[] = $columns;
        }

        return $this;
    }

    /**
     * Verifica si hay columnas definidas para el ordenamiento.
     *
     * @return bool Retorna true si hay columnas definidas, false en caso contrario
     */
    public function hasColumns(): bool {
        return !empty($this->columns);
    }

    /**
     * Obtiene el array de columnas definidas para el ordenamiento.
     *
     * @return array Array con las columnas y sus direcciones de ordenamiento
     * @noinspection PhpUnused
     */
    public function getColumns(): array {
        return $this->columns;
    }

    /**
     * Genera la sentencia SQL correspondiente a la cláusula ORDER BY.
     *
     * @return string Retorna la cláusula ORDER BY completa o una cadena vacía si no hay columnas
     */
    public function toSQL(): string {
        if (empty($this->columns)) {
            return '';
        }

        return 'ORDER BY `' . implode('`, `', $this->columns) . '`';
    }

    /**
     * Reinicia la cláusula ORDER BY eliminando todas las columnas definidas.
     *
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function reset(): self {
        $this->columns = [];
        return $this;
    }
}
