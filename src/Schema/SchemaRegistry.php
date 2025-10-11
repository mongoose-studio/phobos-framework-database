<?php

namespace Phobos\Database\Schema;

/**
 * Registro de alias de schemas
 * Permite cambiar dinámicamente el schema real sin modificar las entidades
 */
class SchemaRegistry {
    /**
     * Instancia singleton
     */
    protected static ?self $instance = null;

    /**
     * Mapeo de alias → schema real
     */
    protected array $aliases = [];

    /**
     * Schema por defecto a usar si no hay alias
     */
    protected ?string $default = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registra un alias de schema
     *
     * @param string $alias Alias usado en las entidades
     * @param string $realSchema Schema real en la base de datos
     * @return void
     */
    public function register(string $alias, string $realSchema): void {
        $this->aliases[$alias] = $realSchema;
    }

    /**
     * Registra múltiples alias
     *
     * @param array $aliases Array de [alias => realSchema]
     * @return void
     */
    public function registerMany(array $aliases): void {
        foreach ($aliases as $alias => $realSchema) {
            $this->register($alias, $realSchema);
        }
    }

    /**
     * Resuelve un alias al schema real
     *
     * @param string $alias Alias a resolver
     * @return string Schema real o el alias original si no existe mapeo
     */
    public function resolve(string $alias): string {
        return $this->aliases[$alias] ?? $alias;
    }

    /**
     * Verifica si un alias está registrado
     *
     * @param string $alias
     * @return bool
     */
    public function has(string $alias): bool {
        return isset($this->aliases[$alias]);
    }

    /**
     * Elimina un alias
     *
     * @param string $alias
     * @return void
     */
    public function remove(string $alias): void {
        unset($this->aliases[$alias]);
    }

    /**
     * Obtiene todos los alias registrados
     *
     * @return array
     */
    public function getAll(): array {
        return $this->aliases;
    }

    /**
     * Limpia todos los alias
     *
     * @return void
     */
    public function clear(): void {
        $this->aliases = [];
    }

    /**
     * Establece el schema por defecto
     *
     * @param string $schema
     * @return void
     */
    public function setDefault(string $schema): void {
        $this->default = $schema;
    }

    /**
     * Obtiene el schema por defecto
     *
     * @return string|null
     */
    public function getDefault(): ?string {
        return $this->default;
    }
}
