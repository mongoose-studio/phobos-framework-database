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
 * Excepción para errores de transacciones
 *
 * Esta clase representa una excepción específica que se lanza cuando ocurren
 * errores durante las operaciones de transacciones en la base de datos.
 * Extiende de DatabaseException para mantener la jerarquía de excepciones
 * relacionadas con la base de datos.
 */
class TransactionException extends DatabaseException {
}
