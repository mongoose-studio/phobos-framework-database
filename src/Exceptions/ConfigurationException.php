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
 * Excepción que se lanza cuando hay errores en la configuración de la base de datos
 *
 * Esta excepción se utiliza específicamente cuando hay problemas con la configuración
 * de la conexión a la base de datos, como credenciales inválidas, parámetros
 * faltantes o valores de configuración incorrectos.
 *
 * @throws ConfigurationException Cuando hay errores en la configuración de la base de datos
 */
class ConfigurationException extends DatabaseException {
}
