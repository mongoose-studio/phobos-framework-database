<?php

namespace PhobosFramework\Database\Exceptions;

use Exception;

/**
 * Excepción para errores de queries
 */
class QueryException extends DatabaseException {
    protected string $sql = '';
    protected array $bindings = [];

    public function __construct(
        string      $message = "",
        int         $code = 0,
        ?\Throwable $previous = null,
        string      $sql = '',
        array       $bindings = [],
        array       $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->sql = $sql;
        $this->bindings = $bindings;
    }

    /**
     * Obtiene la query SQL que causó el error
     *
     * @return string
     */
    public function getSQL(): string {
        return $this->sql;
    }

    /**
     * Obtiene los bindings de la query
     *
     * @return array
     */
    public function getBindings(): array {
        return $this->bindings;
    }
}
