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

namespace PhobosFramework\Database\Exceptions;

/**
 * Excepción lanzada cuando se intenta utilizar un driver de base de datos no soportado
 *
 * Esta excepción se lanza cuando:
 * - Se intenta configurar un driver que no está implementado
 * - Se usa un driver que no es compatible con la versión actual
 */
class UnsupportedDriverException extends DatabaseException {
}
