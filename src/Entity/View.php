<?php

namespace PhobosFramework\Database\Entity;

/**
 * Interface para entidades que son vistas
 */
interface View extends EntityInterface {
    /**
     * Busca registros en la vista
     *
     * @param array $where Condiciones WHERE
     * @param string|array|null $order ORDER BY
     * @param int|null $limitFrom LIMIT desde
     * @param int|null $limitTo LIMIT hasta
     * @param bool $dryRun Si es true, solo retorna la query sin ejecutar
     * @return array|string Array de objetos o query string si dryRun=true
     */
    public static function find(
        array             $where = [],
        string|array|null $order = null,
        ?int              $limitFrom = null,
        ?int              $limitTo = null,
        bool              $dryRun = false
    ): array|string;

    /**
     * Busca el primer registro en la vista
     *
     * @param array $where Condiciones WHERE
     * @param string|array|null $order ORDER BY
     * @param bool $dryRun Si es true, solo retorna la query sin ejecutar
     * @return static|null|string Objeto o null, o query string si dryRun=true
     */
    public static function findFirst(
        array             $where = [],
        string|array|null $order = null,
        bool              $dryRun = false
    ): static|null|string;
}
