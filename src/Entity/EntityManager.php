<?php

namespace PhobosFramework\Database\Entity;

use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\QueryBuilder\QueryBuilder;
use PhobosFramework\Database\Schema\SchemaRegistry;

/**
 * Clase base para todas las entidades mapeadas
 */
abstract class EntityManager implements EntityInterface {
    /**
     * Nombre del schema
     */
    protected static string $schema;

    /**
     * Nombre de la entidad (tabla/vista/procedimiento)
     */
    protected static string $entity;

    /**
     * Nombre de la conexión a usar (null = default)
     */
    protected static ?string $connection = null;

    /**
     * Indica si es un registro nuevo
     */
    protected bool $_isNew = true;

    /**
     * Valores originales del registro (para detectar cambios)
     */
    protected array $_original = [];

    /**
     * Campos que han sido modificados
     */
    protected array $_dirty = [];

    /**
     * {@inheritdoc}
     */
    public static function getIdentification(): string {
        $schema = static::resolveSchema();
        $entity = static::$entity;

        return "{$schema}.{$entity}";
    }

    /**
     * {@inheritdoc}
     */
    public static function getSchema(): string {
        return static::resolveSchema();
    }

    /**
     * {@inheritdoc}
     */
    public static function getEntityName(): string {
        return static::$entity;
    }

    /**
     * Resuelve el schema real (considerando alias)
     *
     * @return string
     */
    protected static function resolveSchema(): string {
        $schema = static::$schema;

        // Resolver usando SchemaRegistry
        $registry = SchemaRegistry::getInstance();
        return $registry->resolve($schema);
    }

    /**
     * Obtiene la conexión a usar
     *
     * @return ConnectionInterface
     * @throws ConnectionException
     */
    protected static function getConnection(): ConnectionInterface {
        return ConnectionManager::getInstance()->getConnection(static::$connection);
    }

    /**
     * Crea un QueryBuilder para esta entidad
     *
     * @return QueryBuilder
     * @throws ConnectionException
     */
    protected static function query(): QueryBuilder {
        return new QueryBuilder(static::getConnection());
    }

    /**
     * Convierte un array en una instancia de la entidad
     *
     * @param array $data Datos del registro
     * @param bool $isNew Si es un registro nuevo
     * @return static
     */
    protected static function hydrate(array $data, bool $isNew = false): static {
        $instance = new static();
        $instance->_isNew = $isNew;
        $instance->_original = $data;

        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }

        return $instance;
    }

    /**
     * Convierte un array de datos en un array de instancias
     *
     * @param array $rows Filas de datos
     * @param bool $isNew Si son registros nuevos
     * @return array
     */
    protected static function hydrateMany(array $rows, bool $isNew = false): array {
        $instances = [];

        foreach ($rows as $row) {
            $instances[] = static::hydrate($row, $isNew);
        }

        return $instances;
    }

    /**
     * Convierte la instancia actual en un array
     *
     * @param bool $onlyDirty Si es true, solo retorna campos modificados
     * @return array
     */
    public function toArray(bool $onlyDirty = false): array {
        $data = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            // Ignorar propiedades protegidas/privadas
            if (str_starts_with($name, '_')) {
                continue;
            }

            // Si solo queremos campos dirty, filtrar
            if ($onlyDirty && !in_array($name, $this->_dirty)) {
                continue;
            }

            $data[$name] = $this->$name;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isNew(): bool {
        return $this->_isNew;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirty(): bool {
        return !empty($this->_dirty);
    }

    /**
     * {@inheritdoc}
     */
    public function getDirtyFields(): array {
        return $this->_dirty;
    }

    /**
     * Marca un campo como modificado
     *
     * @param string $field Nombre del campo
     * @return void
     */
    protected function markDirty(string $field): void {
        if (!in_array($field, $this->_dirty)) {
            $this->_dirty[] = $field;
        }
    }

    /**
     * Detecta cambios comparando con valores originales
     *
     * @return void
     */
    protected function detectChanges(): void {
        $current = $this->toArray();

        foreach ($current as $key => $value) {
            $original = $this->_original[$key] ?? null;

            if ($original !== $value) {
                $this->markDirty($key);
            }
        }
    }

    /**
     * Limpia el estado de cambios
     *
     * @return void
     */
    protected function clearDirty(): void {
        $this->_dirty = [];
        $this->_original = $this->toArray();
    }

    /**
     * Magic method para detectar cambios en propiedades
     */
    public function __set(string $name, mixed $value): void {
        $this->$name = $value;

        if (!$this->_isNew) {
            $this->markDirty($name);
        }
    }
}
