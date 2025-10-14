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
 * Interface que define el comportamiento para entidades que representan procedimientos almacenados (stored procedures)
 * en la base de datos. Esta interfaz extiende de EntityInterface y proporciona métodos para ejecutar
 * procedimientos almacenados de manera estandarizada.
 */
interface StoredProcedure extends EntityInterface {
    /**
     * Ejecuta el procedimiento almacenado en la base de datos
     *
     * @param array $params Arreglo asociativo con los parámetros que se pasarán al procedimiento almacenado.
     *                      Cada elemento debe tener la forma ['nombre_parametro' => valor]
     * @param bool $dryRun Si se establece como true, el método solo generará y retornará la consulta SQL
     *                     sin ejecutarla realmente en la base de datos
     * @return mixed Retorna el resultado de la ejecución del procedimiento. El tipo de retorno
     *               dependerá de la implementación específica del procedimiento almacenado
     * @throws ConnectionException Si la conexión no está configurada o falta el driver
     * @throws UnsupportedDriverException Si el driver especificado no está registrado
     */
    public static function call(array $params = [], bool $dryRun = false): mixed;
}
