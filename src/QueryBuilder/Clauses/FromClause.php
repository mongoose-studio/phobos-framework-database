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
 * Representa la cláusula FROM en una consulta SQL.
 * Esta clase maneja la construcción y manipulación de la parte FROM de una consulta SQL,
 * permitiendo especificar la tabla objetivo y su alias opcional.
 */
class FromClause {
    /**
     * Nombre de la tabla en la cláusula FROM
     * @var string|null
     */
    protected ?string $table = null;

    /**
     * Alias opcional para la tabla
     * @var string|null
     */
    protected ?string $alias = null;

    /**
     * Establece la tabla y su alias opcional para la cláusula FROM
     *
     * @param string $table Nombre de la tabla a consultar
     * @param string|null $alias Alias opcional para referenciar la tabla
     * @return self Retorna la instancia actual para encadenamiento de métodos
     */
    public function setTable(string $table, ?string $alias = null): self {
        $this->table = $table;
        $this->alias = $alias;
        return $this;
    }

    /**
     * Obtiene el nombre de la tabla establecida en la cláusula FROM
     *
     * @return string|null Retorna el nombre de la tabla o null si no se ha establecido
     * @noinspection PhpUnused
     */
    public function getTable(): ?string {
        return $this->table;
    }

    /**
     * Obtiene el alias definido para la tabla
     *
     * @return string|null Retorna el alias de la tabla o null si no se ha establecido
     * @noinspection PhpUnused
     */
    public function getAlias(): ?string {
        return $this->alias;
    }

    /**
     * Verifica si se ha establecido una tabla en la cláusula FROM
     *
     * @return bool Retorna true si hay una tabla establecida, false en caso contrario
     */
    public function hasTable(): bool {
        return $this->table !== null;
    }

    /**
     * Genera la cadena SQL correspondiente a la cláusula FROM
     *
     * @param Grammar $grammar Gramática del dialecto para citar identificadores
     * @return string Retorna la cláusula FROM completa o una cadena vacía si no hay tabla
     */
    public function toSQL(Grammar $grammar): string {
        if ($this->table === null) {
            return '';
        }

        return 'FROM ' . $grammar->wrapTable($this->table, $this->alias);
    }

    /**
     * Reinicia la cláusula FROM, eliminando la tabla y el alias establecidos
     *
     * @return self Retorna la instancia actual para encadenamiento de métodos
     * @noinspection PhpUnused
     */
    public function reset(): self {
        $this->table = null;
        $this->alias = null;
        return $this;
    }
}
