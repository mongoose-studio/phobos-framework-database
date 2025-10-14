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

namespace PhobosFramework\Database\QueryBuilder\Clauses;

use PhobosFramework\Database\Exceptions\InvalidArgumentException;

/**
 * Representa una cláusula LIMIT/OFFSET en una consulta SQL.
 * Esta clase maneja los valores de límite y desplazamiento para paginar resultados.
 */
class LimitClause {
    /**
     * Almacena el valor del límite de registros a recuperar
     */
    protected ?int $limit = null;

    /**
     * Almacena el valor del desplazamiento inicial de registros
     */
    protected ?int $offset = null;

    /**
     * Establece el número máximo de registros a recuperar
     *
     * @param int $limit Cantidad máxima de registros
     * @return self Retorna la instancia actual para encadenamiento
     * @throws InvalidArgumentException Si el límite es negativo
     */
    public function setLimit(int $limit): self {
        if ($limit < 0) {
            throw new InvalidArgumentException('LIMIT must be a non-negative integer');
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * Establece el número de registros a omitir antes de comenzar a recuperar
     *
     * @param int $offset Número de registros a saltar
     * @return self Retorna la instancia actual para encadenamiento
     * @throws InvalidArgumentException Si el offset es negativo
     */
    public function setOffset(int $offset): self {
        if ($offset < 0) {
            throw new InvalidArgumentException('OFFSET must be a non-negative integer');
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * Verifica si se ha establecido un límite de registros
     *
     * @return bool Verdadero si hay un límite establecido, falso en caso contrario
     */
    public function hasLimit(): bool {
        return $this->limit !== null;
    }

    /**
     * Verifica si se ha establecido un desplazamiento inicial
     *
     * @return bool Verdadero si hay un desplazamiento establecido, falso en caso contrario
     */
    public function hasOffset(): bool {
        return $this->offset !== null;
    }

    /**
     * Obtiene el valor del límite establecido
     *
     * @return int|null El valor del límite o null si no está establecido
     * @noinspection PhpUnused
     */
    public function getLimit(): ?int {
        return $this->limit;
    }

    /**
     * Obtiene el valor del desplazamiento establecido
     *
     * @return int|null El valor del desplazamiento o null si no está establecido
     * @noinspection PhpUnused
     */
    public function getOffset(): ?int {
        return $this->offset;
    }

    /**
     * Genera la parte SQL correspondiente a las cláusulas LIMIT y OFFSET
     *
     * @return string Cadena SQL con las cláusulas LIMIT y/o OFFSET si están establecidas
     */
    public function toSQL(): string {
        $parts = [];

        if ($this->limit !== null) {
            $parts[] = "LIMIT $this->limit";
        }

        if ($this->offset !== null) {
            $parts[] = "OFFSET $this->offset";
        }

        return implode(' ', $parts);
    }

    /**
     * Reinicia los valores de límite y desplazamiento a sus valores predeterminados
     *
     * @return self Retorna la instancia actual para encadenamiento
     * @noinspection PhpUnused
     */
    public function reset(): self {
        $this->limit = null;
        $this->offset = null;
        return $this;
    }
}
