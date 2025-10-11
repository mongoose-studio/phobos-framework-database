<?php

namespace PhobosFramework\Database\Entity;

/**
 * Clase base para entidades que son vistas
 */
abstract class ViewEntity extends EntityManager implements View {
    /**
     * {@inheritdoc}
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
        return static::hydrateMany($rows, false);
    }

    /**
     * {@inheritdoc}
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

        return static::hydrate($row, false);
    }

    /**
     * Cuenta registros en la vista
     *
     * @param array $where Condiciones WHERE
     * @return int
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
