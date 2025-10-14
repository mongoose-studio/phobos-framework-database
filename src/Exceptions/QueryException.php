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

use Throwable;

/**
 * Excepción especializada para errores relacionados con consultas de base de datos
 *
 * Esta clase extiende DatabaseException para manejar específicamente errores que ocurren
 * durante la ejecución de consultas SQL, proporcionando acceso a la consulta y sus parámetros.
 *
 * @property string $sql La consulta SQL que generó el error
 * @property array $bindings Los parámetros vinculados a la consulta
 */
class QueryException extends DatabaseException {
    protected string $sql = '';
    /**
     * @var array
     * @noinspection PhpUnused
     */
    protected array $bindings = [];

    /**
     * Constructor de la excepción QueryException
     *
     * @param string $message Mensaje descriptivo del error
     * @param int $code Código del error
     * @param Throwable|null $previous Excepción previa que causó este error
     * @param string $sql Consulta SQL que generó el error
     * @param array $bindings Parámetros vinculados a la consulta SQL
     * @param array $context Información adicional del contexto del error
     */
    public function __construct(
        string     $message = "",
        int        $code = 0,
        ?Throwable $previous = null,
        string     $sql = '',
        array      $bindings = [],
        array      $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->sql = $sql;
        $this->bindings = $bindings;
    }

    /**
     * Obtiene la consulta SQL que causó el error
     *
     * Este método retorna la consulta SQL original que generó la excepción,
     * útil para propósitos de diagnóstico y depuración.
     *
     * @return string La consulta SQL completa
     * @noinspection PhpUnused
     */
    public function getSQL(): string {
        return $this->sql;
    }

    /**
     * Obtiene los parámetros vinculados a la consulta SQL
     *
     * Retorna un array con todos los valores que fueron vinculados
     * a la consulta SQL al momento de su ejecución.
     *
     * @return array Los parámetros vinculados a la consulta
     * @noinspection PhpUnused
     */
    public function getBindings(): array {
        return $this->bindings;
    }
}
