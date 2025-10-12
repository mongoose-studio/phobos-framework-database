<?php

namespace PhobosFramework\Database\Entity;

/**
 * Interface para entidades que son tablas
 */
interface Table extends EntityInterface {
    /**
     * Obtiene las columnas que conforman la primary key
     *
     * @return array
     */
    public static function getPrimaryKey(): array;

    /**
     * Busca registros
     *
     * @param array $where Condiciones WHERE
     * @param string|array|null $order ORDER BY
     * @param int|null $limitFrom LIMIT desde
     * @param int|null $limitTo LIMIT hasta
     * @param bool $dryRun Si es true, solo retorna la query sin ejecutar
     * @return array|string Array de objetos de la entidad o query string si dryRun=true
     */
    public static function find(
        array             $where = [],
        string|array|null $order = null,
        ?int              $limitFrom = null,
        ?int              $limitTo = null,
        bool              $dryRun = false
    ): array;

    /**
     * Busca el primer registro que coincida
     *
     * @param array $where Condiciones WHERE
     * @param string|array|null $order ORDER BY
     * @param bool $dryRun Si es true, solo retorna la query sin ejecutar
     * @return static|null|string Objeto de la entidad, null si no existe, o query string si dryRun=true
     */
    public static function findFirst(
        array             $where = [],
        string|array|null $order = null,
        bool              $dryRun = false
    ): static|null|array;

    /**
     * Elimina registros
     *
     * @param array $where Condiciones WHERE
     * @param int|null $limit LIMIT
     * @param bool $dryRun Si es true, solo retorna la query sin ejecutar
     * @return int|string Número de filas eliminadas o query string si dryRun=true
     */
    public static function delete(
        array $where,
        ?int  $limit = null,
        bool  $dryRun = false
    ): int|array;

    /**
     * Guarda el registro (INSERT o UPDATE según corresponda)
     *
     * @return bool
     */
    public function save(): bool;

    /**
     * Elimina el registro actual
     *
     * @return bool
     */
    public function remove(): bool;

    /**
     * Verifica si es un registro nuevo (no existe en BD)
     *
     * @return bool
     */
    public function isNew(): bool;

    /**
     * Verifica si el registro ha sido modificado
     *
     * @return bool
     */
    public function isDirty(): bool;

    /**
     * Obtiene los campos que han sido modificados
     *
     * @return array
     */
    public function getDirtyFields(): array;
}
