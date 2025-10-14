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
 * Excepción para errores de conexión a la base de datos
 *
 * Esta excepción se lanza cuando ocurre un error durante el intento de
 * establecer una conexión con la base de datos, como credenciales incorrectas,
 * servidor no disponible o problemas de red.
 */
class ConnectionException extends DatabaseException {
}
