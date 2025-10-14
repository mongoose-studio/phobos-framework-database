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
 * Excepción para configuraciones inválidas
 *
 * Esta excepción se lanza cuando se detecta una configuración lógica inválida
 * o inconsistente en la base de datos, como intentar realizar operaciones
 * incompatibles o usar valores de configuración no permitidos.
 */
class LogicException extends \LogicException {
}
