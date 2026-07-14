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

/**
 * Interface que define el comportamiento de las entidades que representan vistas en la base de datos.
 * Proporciona métodos para realizar operaciones de consulta sobre las vistas.
 */
interface View extends EntityInterface {
    /**
     * Busca y retorna registros de la vista que coincidan con los criterios especificados.
     *
     * Genera `LIMIT $limit OFFSET $offset`. Para paginar: `find($where, $order, 20, 40)`
     * devuelve 20 registros saltándose los primeros 40.
     *
     * @param array $where Condiciones WHERE para filtrar los resultados
     * @param string|array|null $order Criterios de ordenamiento (ORDER BY)
     * @param int|null $limit Cantidad máxima de registros a retornar
     * @param int|null $offset Cantidad de registros a saltar antes de empezar a retornar
     * @param bool $dryRun Si es verdadero, retorna la consulta SQL sin ejecutarla
     * @return array|string Colección de objetos de la entidad o cadena SQL si dryRun es verdadero
     */
    public static function find(
        array             $where = [],
        string|array|null $order = null,
        ?int              $limit = null,
        ?int              $offset = null,
        bool              $dryRun = false
    ): array|string;

    /**
     * Busca y retorna el primer registro de la vista que coincida con los criterios especificados.
     *
     * @param array $where Condiciones WHERE para filtrar la búsqueda
     * @param string|array|null $order Criterios de ordenamiento (ORDER BY)
     * @param bool $dryRun Si es verdadero, retorna la consulta SQL sin ejecutarla
     * @return static|null|string Instancia de la entidad, null si no se encuentra, o cadena SQL si dryRun es verdadero
     */
    public static function findFirst(
        array             $where = [],
        string|array|null $order = null,
        bool              $dryRun = false
    ): static|null|string;
}
