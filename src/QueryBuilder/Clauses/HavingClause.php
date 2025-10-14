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
 * Representa la cláusula HAVING en una consulta SQL.
 * Esta cláusula se utiliza para filtrar resultados agrupados (después de GROUP BY),
 * similar a WHERE pero aplicada a grupos en lugar de registros individuales.
 * Permite establecer condiciones sobre funciones de agregación como COUNT, SUM, AVG, etc.
 */
class HavingClause extends WhereClause {
    /**
     * Genera la representación SQL de la cláusula HAVING.
     * Combina todas las condiciones agregadas utilizando los operadores lógicos
     * especificados (AND, OR) para formar la cláusula HAVING completa.
     *
     * @return string Retorna la cláusula HAVING construida o una cadena vacía si no hay condiciones
     */
    public function toSQL(): string {
        if (empty($this->conditions)) {
            return '';
        }

        $sql = 'HAVING ';
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
}
