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
 * Interface que define el comportamiento de las entidades que representan tablas en la base de datos.
 * Proporciona métodos para realizar operaciones CRUD y gestionar el estado de los registros.
 */
interface Table extends EntityInterface {
    /**
     * Obtiene las columnas que conforman la clave primaria (primary key) de la tabla.
     *
     * @return array Arreglo con los nombres de las columnas que forman la clave primaria
     */
    public static function getPrimaryKey(): array;

    /**
     * Busca registros en la tabla según los criterios especificados.
     *
     * @param array $where Condiciones WHERE para filtrar los resultados
     * @param string|array|null $order Criterios de ordenamiento (ORDER BY)
     * @param int|null $limitFrom Número de registro desde donde comenzar la búsqueda
     * @param int|null $limitTo Cantidad máxima de registros a retornar
     * @param bool $dryRun Si es verdadero, retorna la consulta SQL sin ejecutarla
     * @return array Colección de objetos de la entidad o cadena SQL si dryRun es verdadero
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    public static function find(
        array             $where = [],
        string|array|null $order = null,
        ?int              $limitFrom = null,
        ?int              $limitTo = null,
        bool              $dryRun = false
    ): array;

    /**
     * Busca y retorna el primer registro que coincida con los criterios especificados.
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
    ): static|null|array;

    /**
     * Elimina registros de la tabla que cumplan con los criterios especificados.
     *
     * @param array $where Condiciones WHERE para identificar los registros a eliminar
     * @param int|null $limit Límite máximo de registros a eliminar
     * @param bool $dryRun Si es verdadero, retorna la consulta SQL sin ejecutarla
     * @return int|array Cantidad de registros eliminados o cadena SQL si dryRun es verdadero
     */
    public static function delete(
        array $where,
        ?int  $limit = null,
        bool  $dryRun = false
    ): int|array;

    /**
     * Persiste el registro actual en la base de datos.
     * Realiza una inserción (INSERT) si es un registro nuevo o una actualización (UPDATE) si ya existe.
     *
     * @return bool Verdadero si la operación fue exitosa, falso en caso contrario
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    public function save(): bool;

    /**
     * Elimina el registro actual de la base de datos.
     *
     * @return bool Verdadero si la eliminación fue exitosa, falso en caso contrario
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    public function remove(): bool;

    /**
     * Verifica si el registro actual es nuevo y aún no existe en la base de datos.
     *
     * @return bool Verdadero si es un registro nuevo, falso si ya existe en la base de datos
     * @noinspection PhpUnused
     */
    public function isNew(): bool;

    /**
     * Verifica si el registro actual ha sido modificado desde su última lectura o guardado.
     *
     * @return bool Verdadero si el registro ha sido modificado, falso en caso contrario
     * @noinspection PhpUnused
     */
    public function isDirty(): bool;

    /**
     * Obtiene un arreglo con los nombres de los campos que han sido modificados en el registro actual.
     *
     * @return array Arreglo con los nombres de los campos modificados
     * @noinspection PhpUnused
     */
    public function getDirtyFields(): array;
}
