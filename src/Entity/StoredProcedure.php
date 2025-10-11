<?php

namespace PhobosFramework\Database\Entity;

/**
 * Interface para entidades que son stored procedures
 */
interface StoredProcedure extends EntityInterface {
    /**
     * Llama al stored procedure
     *
     * @param array $params Parámetros del procedimiento
     * @param bool $dryRun Si es true, solo retorna la query sin ejecutar
     * @return mixed Resultado del procedimiento
     */
    public static function call(array $params = [], bool $dryRun = false): mixed;
}
