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
 * Interface base para todas las entidades.
 *
 * Esta interfaz define los métodos básicos que deben implementar todas las entidades
 * del sistema para poder ser gestionadas por el ORM.
 */
interface EntityInterface {
    /**
     * Obtiene el identificador completo de la entidad (schema.table).
     *
     * Este método debe retornar una cadena que contenga el nombre del schema
     * y el nombre de la tabla separados por un punto.
     *
     * @return string El identificador completo en formato "schema.table"
     */
    public static function getIdentification(): string;

    /**
     * Obtiene el nombre del schema.
     *
     * Este método debe retornar el nombre del schema al que pertenece
     * la entidad en la base de datos.
     *
     * @return string El nombre del schema
     */
    public static function getSchema(): string;

    /**
     * Obtiene el nombre de la entidad (tabla/vista/procedimiento).
     *
     * Este método debe retornar el nombre de la tabla, vista o procedimiento
     * que representa esta entidad en la base de datos.
     *
     * @return string El nombre de la entidad
     */
    public static function getEntityName(): string;
}
