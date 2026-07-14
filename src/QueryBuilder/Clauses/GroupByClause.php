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

use PhobosFramework\Database\QueryBuilder\Grammar\Grammar;

/**
 * Representa la cláusula GROUP BY en una consulta SQL.
 * Esta clase maneja la agrupación de resultados por columnas específicas.
 */
class GroupByClause {
    protected array $columns = [];

    /**
     * Agrega una o más columnas para agrupar los resultados.
     * Acepta tanto una cadena única como un array de nombres de columnas.
     *
     * @param string|array $columns Columna o array de columnas para agrupar
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
     * Verifica si existen columnas definidas para la agrupación.
     *
     * @return bool Retorna true si hay columnas definidas, false en caso contrario
     */
    public function hasColumns(): bool {
        return !empty($this->columns);
    }

    /**
     * Obtiene el array de columnas definidas para la agrupación.
     *
     * @return array Array con los nombres de las columnas de agrupación
     * @noinspection PhpUnused
     */
    public function getColumns(): array {
        return $this->columns;
    }

    /**
     * Genera la parte SQL correspondiente a la cláusula GROUP BY.
     * Si no hay columnas definidas, retorna una cadena vacía.
     *
     * @param Grammar $grammar Gramática del dialecto para citar identificadores
     * @return string Fragmento SQL de la cláusula GROUP BY
     */
    public function toSQL(Grammar $grammar): string {
        if (empty($this->columns)) {
            return '';
        }

        return 'GROUP BY ' . implode(', ', array_map($grammar->wrap(...), $this->columns));
    }

    /**
     * Reinicia la cláusula GROUP BY eliminando todas las columnas definidas.
     *
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function reset(): self {
        $this->columns = [];
        return $this;
    }
}
