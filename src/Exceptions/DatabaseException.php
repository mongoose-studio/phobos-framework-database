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

use Exception;
use Throwable;

/**
 * Excepción base para todas las excepciones de base de datos
 *
 * Esta clase sirve como base para todas las excepciones relacionadas con
 * operaciones de base de datos en el framework. Permite incluir información
 * adicional de contexto que puede ser útil para el diagnóstico de errores.
 */
class DatabaseException extends Exception {
    protected array $context = [];

    /**
     * Constructor de la excepción
     *
     * @param string $message Mensaje descriptivo del error
     * @param int $code Código de error
     * @param Throwable|null $previous Excepción previa que causó este error
     * @param array $context Información adicional de contexto
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $context = []) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Obtiene el contexto adicional de la excepción
     *
     * Este método devuelve un array con información adicional sobre el error
     * que puede ser útil para el diagnóstico y la resolución del problema.
     *
     * @return array Información de contexto del error
     * @noinspection PhpUnused
     */
    public function getContext(): array {
        return $this->context;
    }
}
