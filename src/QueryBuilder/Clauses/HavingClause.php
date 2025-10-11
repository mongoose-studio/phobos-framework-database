<?php

namespace PhobosFramework\Database\QueryBuilder\Clauses;

/**
 * Representa la cláusula HAVING
 * (Similar a WHERE pero para resultados agrupados)
 */
class HavingClause extends WhereClause {
    /**
     * Genera el SQL de la cláusula HAVING
     *
     * @return string
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
