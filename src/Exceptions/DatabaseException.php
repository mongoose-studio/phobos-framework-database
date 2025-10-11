<?php

namespace PhobosFramework\Database\Exceptions;

use Exception;
use Throwable;

/**
 * Excepción base para todas las excepciones de base de datos
 */
class DatabaseException extends Exception {
    protected array $context = [];

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, array $context = []) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Obtiene el contexto adicional de la excepción
     *
     * @return array
     */
    public function getContext(): array {
        return $this->context;
    }
}
