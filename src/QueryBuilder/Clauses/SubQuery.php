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

use PhobosFramework\Database\QueryBuilder\QueryBuilder;

/**
 * Representa una subconsulta (subquery) que puede ser utilizada dentro de otra consulta SQL.
 *
 * Esta clase permite encapsular una consulta completa como una subconsulta, que puede ser
 * utilizada en cláusulas SELECT, FROM, WHERE, etc. de otra consulta principal.
 * Opcionalmente se puede asignar un alias a la subconsulta.
 * @noinspection PhpUnused
 */
class SubQuery {
    protected QueryBuilder $query;
    protected ?string $alias = null;

    /**
     * Constructor de la clase SubQuery
     *
     * @param QueryBuilder $query La consulta que se utilizará como subconsulta
     * @param string|null $alias Alias opcional para la subconsulta
     */
    public function __construct(QueryBuilder $query, ?string $alias = null) {
        $this->query = $query;
        $this->alias = $alias;
    }

    /**
     * Convierte la subconsulta a su representación SQL
     *
     * Genera la cadena SQL de la subconsulta, incluyendo paréntesis y
     * el alias si fue especificado.
     *
     * @return string La representación SQL de la subconsulta
     */
    public function toSQL(): string {
        $sql = '(' . $this->query->getQuery() . ')';

        if ($this->alias !== null) {
            $sql .= ' AS ' . $this->alias;
        }

        return $sql;
    }

    /**
     * Obtiene los parámetros vinculados de la subconsulta
     *
     * Retorna un array con todos los valores que deben ser vinculados
     * de forma segura a la consulta SQL.
     *
     * @return array Los parámetros vinculados de la subconsulta
     * @noinspection PhpUnused
     */
    public function getBindings(): array {
        return $this->query->getBindings();
    }

    /**
     * Obtiene el alias de la subconsulta
     *
     * @return string|null El alias de la subconsulta o null si no tiene alias
     * @noinspection PhpUnused
     */
    public function getAlias(): ?string {
        return $this->alias;
    }

    /**
     * Obtiene el objeto QueryBuilder que representa la subconsulta
     *
     * @return QueryBuilder El constructor de consultas asociado a esta subconsulta
     * @noinspection PhpUnused
     */
    public function getQuery(): QueryBuilder {
        return $this->query;
    }

    /**
     * Permite convertir el objeto SubQuery a una cadena
     *
     * Implementa la interfaz __toString para permitir usar el objeto
     * directamente como una cadena SQL.
     *
     * @return string La representación SQL de la subconsulta
     */
    public function __toString(): string {
        return $this->toSQL();
    }
}
