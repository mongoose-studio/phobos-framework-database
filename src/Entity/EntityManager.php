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

namespace PhobosFramework\Database\Entity;

use LogicException;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\UnsupportedDriverException;
use PhobosFramework\Database\QueryBuilder\QueryBuilder;
use PhobosFramework\Database\Schema\SchemaRegistry;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

/**
 * Clase base para todas las entidades mapeadas en la base de datos.
 * Proporciona funcionalidad para el mapeo objeto-relacional (ORM),
 * seguimiento de cambios, y operaciones básicas de persistencia.
 *
 * Esta clase implementa el patrón Active Record donde cada instancia
 * representa una fila en la base de datos. Gestiona el estado de las
 * entidades, el seguimiento de cambios y la interacción con la base de datos.
 *
 * @author PhobosFramework
 * @version 3.0.2
 */
abstract class EntityManager implements EntityInterface {
    /**
     * Nombre del esquema en la base de datos.
     * Define el namespace o agrupación lógica de la entidad.
     */
    protected static string $schema;

    /**
     * Nombre de la entidad en la base de datos.
     * Puede ser una tabla, vista o procedimiento almacenado.
     */
    protected static string $entity;

    /**
     * Identificador de la conexión a la base de datos.
     * Si es null, se utilizará la conexión por defecto.
     */
    protected static ?string $connection = null;

    /**
     * Indica si la entidad representa un nuevo registro.
     * true = registro nuevo (INSERT), false = registro existente (UPDATE)
     */
    protected bool $_isNew = true;

    /**
     * Almacena los valores originales del registro.
     * Utilizado para detectar qué campos han sido modificados.
     */
    protected array|object|null $_original = [];

    /**
     * Lista de campos que han sido modificados desde la carga.
     * Se utiliza para optimizar las operaciones de UPDATE.
     */
    protected array $_dirty = [];

    /**
     * Lista de nombres de campos que no pueden ser modificados.
     * Estos campos son internos del sistema y deben preservarse.
     */
    protected array $_reserved = [
        'schema',
        'entity',
        'pk'
    ];

    /**
     * {@inheritdoc}
     */
    public static function getIdentification(): string {
        $schema = static::resolveSchema();
        $entity = static::$entity;

        return "$schema.$entity";
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
     * Obtiene la instancia de conexión configurada para esta entidad.
     * Si no se especificó una conexión específica, usa la conexión por defecto.
     *
     * @return ConnectionInterface La conexión de base de datos a utilizar
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    protected static function getConnection(): ConnectionInterface {
        return ConnectionManager::getInstance()->getConnection(static::$connection);
    }

    /**
     * Crea un QueryBuilder para esta entidad.
     * Este método proporciona una interfaz fluida para construir
     * consultas SQL de manera programática y segura.
     *
     * @return QueryBuilder Instancia de QueryBuilder configurada para esta entidad
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    protected static function query(): QueryBuilder {
        return new QueryBuilder(static::getConnection());
    }

    /**
     * Convierte un array en una instancia de la entidad
     *
     * @param array|object|null $data Datos del registro
     * @param bool $isNew Si es un registro nuevo
     * @return static
     */
    protected static function hydrate(array|object|null $data, bool $isNew = false): static {
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
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            // Ignorar propiedades protegidas/privadas
            if (str_starts_with($name, '_')) {
                continue;
            }

            // Si solo queremos campos dirty, filtrar
            if ($onlyDirty && !in_array($name, $this->_dirty)) {
                continue;
            }

            if (!in_array($name, $this->_reserved)) {
                $data[$name] = $this->$name;
            }
        }

        return $data;
    }

    /**
     * Indica si la entidad representa un nuevo registro que aún no ha sido
     * persistido en la base de datos.
     *
     * @return bool true si es un nuevo registro, false si ya existe en la BD
     * @noinspection PhpUnused
     */
    public function isNew(): bool {
        return $this->_isNew;
    }

    /**
     * Verifica si la entidad tiene cambios pendientes de ser guardados
     * en la base de datos.
     *
     * @return bool true si hay cambios pendientes, false en caso contrario
     */
    public function isDirty(): bool {
        return !empty($this->_dirty);
    }

    /**
     * Obtiene la lista de campos que han sido modificados desde la última
     * vez que se guardó la entidad.
     *
     * @return array Array con los nombres de los campos modificados
     * @noinspection PhpUnused
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
    public function detectChanges(): void {
        $current = $this->toArray();

        foreach ($current as $key => $value) {
            $original = is_array($this->_original) ? $this->_original[$key] ?? null : (is_object($this->_original) ? $this->_original->{$key} ?? null : null);

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
     * Magic method para clonar entidades
     * Al clonar, resetea el estado para tratar la entidad como nueva
     */
    public function __clone() {
        // Marcar como nueva para que el próximo save() haga INSERT
        $this->_isNew = true;

        // Limpiar el tracking de cambios
        $this->_dirty = [];
        $this->_original = [];

        // Si la entidad tiene primary key auto-increment, limpiarla
        // para que se genere un nuevo ID en el INSERT
        if (method_exists($this, 'getPrimaryKey')) {
            foreach (static::getPrimaryKey() as $pkColumn) {
                if (property_exists($this, $pkColumn)) {
                    $this->$pkColumn = null;
                }
            }
        }
    }

    /**
     * Magic method para detectar cambios en propiedades
     */
    public function __set(string $name, mixed $value): void {
        // Evitar recursión: solo actuar si la propiedad no existe o es privada/protegida
        // Las propiedades públicas se setean directamente sin pasar por __set
        // Este método solo se llama para propiedades dinámicas o inaccesibles

        // Crear la propiedad dinámicamente si no existe
        $reflection = new ReflectionClass($this);

        try {
            $property = $reflection->getProperty($name);
            // Si la propiedad existe y es pública, este método no debería ser llamado
            // pero por seguridad, usamos reflexión para setearla
            if (!$property->isPublic()) {
                /** @noinspection PhpExpressionResultUnusedInspection */
                $property->setAccessible(true);
                $property->setValue($this, $value);
            }
        } /** @noinspection PhpUnusedLocalVariableInspection */ catch (ReflectionException $e) {
            // Propiedad no existe, crear dinámicamente
            // No hay forma de evitar la recursión aquí sin usar arrays internos
            // Por ahora, lanzamos excepción para propiedades no declaradas
            throw new LogicException(
                "Property '$name' does not exist in " . static::class .
                ". Declare all properties explicitly to use change tracking."
            );
        }

        if (!$this->_isNew) {
            $this->markDirty($name);
        }
    }
}
