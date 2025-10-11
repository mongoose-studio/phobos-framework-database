<?php

namespace PhobosFramework\Database\QueryBuilder\Clauses;

/**
 * Representa la cláusula LIMIT/OFFSET
 */
class LimitClause {
    protected ?int $limit = null;
    protected ?int $offset = null;

    /**
     * Establece el LIMIT
     *
     * @param int $limit
     * @return self
     */
    public function setLimit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Establece el OFFSET
     *
     * @param int $offset
     * @return self
     */
    public function setOffset(int $offset): self {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Verifica si tiene límite
     *
     * @return bool
     */
    public function hasLimit(): bool {
        return $this->limit !== null;
    }

    /**
     * Verifica si tiene offset
     *
     * @return bool
     */
    public function hasOffset(): bool {
        return $this->offset !== null;
    }

    /**
     * Obtiene el límite
     *
     * @return int|null
     */
    public function getLimit(): ?int {
        return $this->limit;
    }

    /**
     * Obtiene el offset
     *
     * @return int|null
     */
    public function getOffset(): ?int {
        return $this->offset;
    }

    /**
     * Genera el SQL de la cláusula LIMIT/OFFSET
     *
     * @return string
     */
    public function toSQL(): string {
        $parts = [];

        if ($this->limit !== null) {
            $parts[] = "LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $parts[] = "OFFSET {$this->offset}";
        }

        return implode(' ', $parts);
    }

    /**
     * Resetea la cláusula
     *
     * @return self
     */
    public function reset(): self {
        $this->limit = null;
        $this->offset = null;
        return $this;
    }
}
