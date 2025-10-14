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

use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\UnsupportedDriverException;

/**
 * Clase base abstracta para entidades que representan vistas en la base de datos.
 * Esta clase implementa la interfaz View y proporciona la funcionalidad básica
 * para realizar consultas sobre vistas de base de datos.
 * @noinspection PhpUnused
 */
abstract class ViewEntity extends EntityManager implements View {
    /**
     * Busca y retorna registros de la vista que coincidan con los criterios especificados.
     *
     * @param array $where Condiciones WHERE para filtrar los resultados
     * @param string|array|null $order Criterios de ordenamiento (ORDER BY)
     * @param int|null $limitFrom Número de registro desde donde comenzar la búsqueda
     * @param int|null $limitTo Cantidad máxima de registros a retornar
     * @param bool $dryRun Si es verdadero, retorna la consulta SQL sin ejecutarla
     * @return array|string Colección de objetos de la entidad o cadena SQL si dryRun es verdadero
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    public static function find(
        array             $where = [],
        string|array|null $order = null,
        ?int              $limitFrom = null,
        ?int              $limitTo = null,
        bool              $dryRun = false
    ): array|string {
        $qb = static::query()
            ->select('*')
            ->from(static::getIdentification());

        if (!empty($where)) {
            $qb->where($where);
        }

        if ($order !== null) {
            $qb->orderBy($order);
        }

        if ($limitFrom !== null) {
            $qb->limit($limitFrom, $limitTo);
        }

        if ($dryRun) {
            return $qb->getQuery();
        }

        $rows = $qb->fetch();
        return static::hydrateMany($rows);
    }

    /**
     * Busca y retorna el primer registro de la vista que coincida con los criterios especificados.
     *
     * @param array $where Condiciones WHERE para filtrar la búsqueda
     * @param string|array|null $order Criterios de ordenamiento (ORDER BY)
     * @param bool $dryRun Si es verdadero, retorna la consulta SQL sin ejecutarla
     * @return static|null|string Instancia de la entidad, null si no se encuentra, o cadena SQL si dryRun es verdadero
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    public static function findFirst(
        array             $where = [],
        string|array|null $order = null,
        bool              $dryRun = false
    ): static|null|string {
        $qb = static::query()
            ->select('*')
            ->from(static::getIdentification())
            ->limit(1);

        if (!empty($where)) {
            $qb->where($where);
        }

        if ($order !== null) {
            $qb->orderBy($order);
        }

        if ($dryRun) {
            return $qb->getQuery();
        }

        $row = $qb->fetchFirst();

        if ($row === null) {
            return null;
        }

        return static::hydrate($row);
    }

    /**
     * Cuenta la cantidad total de registros en la vista que coincidan con los criterios especificados.
     *
     * @param array $where Condiciones WHERE para filtrar los registros a contar
     * @return int Número total de registros encontrados
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     * @noinspection PhpUnused
     */
    public static function count(array $where = []): int {
        $qb = static::query()
            ->select('COUNT(*) as total')
            ->from(static::getIdentification());

        if (!empty($where)) {
            $qb->where($where);
        }

        return (int)$qb->fetchColumn('total');
    }
}
