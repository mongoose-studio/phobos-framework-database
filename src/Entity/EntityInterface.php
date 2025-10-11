<?php

namespace PhobosFramework\Database\Entity;

/**
 * Interface base para todas las entidades
 */
interface EntityInterface {
    /**
     * Obtiene el identificador completo de la entidad (schema.table)
     *
     * @return string
     */
    public static function getIdentification(): string;

    /**
     * Obtiene el nombre del schema
     *
     * @return string
     */
    public static function getSchema(): string;

    /**
     * Obtiene el nombre de la entidad (tabla/vista/procedimiento)
     *
     * @return string
     */
    public static function getEntityName(): string;
}
