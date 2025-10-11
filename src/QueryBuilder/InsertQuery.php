<?php

namespace PhobosFramework\Database\QueryBuilder;

use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\Exceptions\InvalidArgumentException;
use PhobosFramework\Database\Exceptions\LogicException;

/**
 * Query Builder para INSERT
 */
class InsertQuery {
    protected ConnectionInterface $connection;
    protected ?string $table = null;
    protected array $data = [];
    protected bool $multiple = false;

    public function __construct(?ConnectionInterface $connection = null) {
        $this->connection = $connection ?? ConnectionManager::getInstance()->getConnection();
    }

    /**
     * Establece la tabla
     */
    public function into(string $table): self {
        $this->table = $table;
        return $this;
    }

    /**
     * Establece los datos a insertar
     * Soporta: ['col' => 'val'] o [['col' => 'val1'], ['col' => 'val2']]
     */
    public function values(array $data): self {
        // Detectar si es insert múltiple
        if ($this->isMultipleInsert($data)) {
            $this->multiple = true;
            $this->data = $data;
        } else {
            $this->multiple = false;
            $this->data = $data;
        }

        return $this;
    }

    /**
     * Detecta si es un insert múltiple
     */
    protected function isMultipleInsert(array $data): bool {
        if (empty($data)) {
            return false;
        }

        $firstElement = reset($data);
        return is_array($firstElement) && !empty($firstElement);
    }

    /**
     * Genera el SQL
     */
    public function getQuery(): string {
        if ($this->multiple) {
            return $this->getMultipleInsertQuery();
        }

        return $this->getSingleInsertQuery();
    }

    /**
     * Genera SQL para insert simple
     */
    protected function getSingleInsertQuery(): string {
        $columns = array_keys($this->data);
        $placeholders = array_fill(0, count($columns), '?');

        $columnsStr = implode(', ', $columns);
        $placeholdersStr = implode(', ', $placeholders);

        /** @noinspection SqlNoDataSourceInspection */
        return "INSERT INTO {$this->table} ({$columnsStr}) VALUES ({$placeholdersStr})";
    }

    /**
     * Genera SQL para insert múltiple
     */
    protected function getMultipleInsertQuery(): string {
        if (empty($this->data)) {
            throw new InvalidArgumentException('No data provided for insert');
        }

        // Usar las columnas del primer registro
        $columns = array_keys($this->data[0]);
        $columnsStr = implode(', ', $columns);

        // Crear placeholders para cada fila
        $valueSets = [];
        foreach ($this->data as $row) {
            $placeholders = array_fill(0, count($columns), '?');
            $valueSets[] = '(' . implode(', ', $placeholders) . ')';
        }

        $valuesStr = implode(', ', $valueSets);

        /** @noinspection SqlNoDataSourceInspection */
        return "INSERT INTO {$this->table} ({$columnsStr}) VALUES {$valuesStr}";
    }

    /**
     * Obtiene los bindings
     */
    public function getBindings(): array {
        if ($this->multiple) {
            $bindings = [];
            $columns = array_keys($this->data[0]);

            foreach ($this->data as $row) {
                foreach ($columns as $col) {
                    $bindings[] = $row[$col] ?? null;
                }
            }

            return $bindings;
        }

        return array_values($this->data);
    }

    /**
     * Ejecuta el INSERT
     */
    public function execute(): bool {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();

        $this->connection->execute($sql, $bindings);
        return true;
    }

    /**
     * Ejecuta el INSERT y retorna el ID insertado (solo para insert simple)
     */
    public function executeAndGetId(?string $sequence = null): string|false {
        if ($this->multiple) {
            throw new LogicException('Cannot get last insert ID for multiple inserts');
        }

        $this->execute();
        return $this->connection->lastInsertId($sequence);
    }

    /**
     * Ejecuta y retorna el número de filas afectadas
     */
    public function executeAndCount(): int {
        $sql = $this->getQuery();
        $bindings = $this->getBindings();

        $stmt = $this->connection->execute($sql, $bindings);
        return $stmt->rowCount();
    }
}
