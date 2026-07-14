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
     *
     * Es opcional: si es null, la entidad no se califica con schema y la resolución
     * del nombre queda a cargo del motor (ej: el `search_path` de PostgreSQL).
     */
    protected static ?string $schema = null;

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
     * Mapa de casteo de atributos: `['columna' => 'tipo']`.
     *
     * Convierte los valores entre su representación en la base de datos y su tipo
     * nativo en PHP. Es driver-neutral (beneficia a MySQL, SQLite y PostgreSQL).
     *
     * Tipos soportados:
     * - `json`     array/objeto PHP <-> texto JSON (JSONB en PostgreSQL).
     * - `bool`     bool nativo (entiende `t`/`f`, `1`/`0`, `true`/`false`).
     * - `int`      entero.
     * - `float`    flotante.
     * - `datetime` DateTimeImmutable <-> `Y-m-d H:i:s`.
     */
    protected static array $casts = [];

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

        // Sin schema (null/vacío) la entidad no se califica: la resolución del
        // nombre queda a cargo del motor (ej: el search_path de PostgreSQL).
        if ($schema === '') {
            return $entity;
        }

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

        // Schema opcional: sin valor no hay nada que resolver.
        if ($schema === null || $schema === '') {
            return '';
        }

        // Resolver usando SchemaRegistry (permite re-apuntar alias en runtime).
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

        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = static::castFromDatabase($key, $value);
            }
        }

        // Se guarda el estado original en su forma canónica (la misma que produce
        // toArray()), no la fila cruda: así detectChanges() compara peras con peras.
        // Un valor JSONB o boolean recién leído ('t', '{"b":1}') no debe marcarse como
        // sucio solo porque su reserialización difiera de la representación del motor.
        $instance->_original = $instance->toArray();

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

            // Las propiedades estáticas son configuración de la clase, no columnas.
            if ($property->isStatic()) {
                continue;
            }

            // Ignorar propiedades protegidas/privadas
            if (str_starts_with($name, '_')) {
                continue;
            }

            // Una propiedad tipada sin inicializar (ej: se cargó un subconjunto de
            // columnas) no aporta valor y accederla sería un error: se omite.
            if (!$property->isInitialized($this)) {
                continue;
            }

            // Si solo queremos campos dirty, filtrar
            if ($onlyDirty && !in_array($name, $this->_dirty)) {
                continue;
            }

            if (!in_array($name, $this->_reserved)) {
                $data[$name] = static::castToDatabase($name, $this->$name);
            }
        }

        return $data;
    }

    /**
     * Convierte un valor leído de la base de datos a su tipo nativo en PHP.
     *
     * @param string $key Nombre de la columna
     * @param mixed $value Valor tal como viene de la base de datos
     * @return mixed Valor casteado según el mapa `$casts`, o intacto si no hay casteo
     */
    protected static function castFromDatabase(string $key, mixed $value): mixed {
        if ($value === null || !isset(static::$casts[$key])) {
            return $value;
        }

        return match (static::$casts[$key]) {
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'bool' => static::castToBool($value),
            'int' => (int)$value,
            'float' => (float)$value,
            'datetime' => $value instanceof \DateTimeInterface
                ? $value
                : new \DateTimeImmutable((string)$value),
            default => $value,
        };
    }

    /**
     * Convierte un valor nativo de PHP a su representación para la base de datos.
     *
     * @param string $key Nombre de la columna
     * @param mixed $value Valor nativo en PHP
     * @return mixed Valor listo para persistir, o intacto si no hay casteo
     */
    protected static function castToDatabase(string $key, mixed $value): mixed {
        if ($value === null || !isset(static::$casts[$key])) {
            return $value;
        }

        return match (static::$casts[$key]) {
            'json' => is_string($value) ? $value : json_encode($value),
            // Se persiste como 1/0: PDO bindearía `false` como cadena vacía, que
            // PostgreSQL rechaza para columnas boolean. 1/0 es válido en los tres motores.
            'bool' => $value ? 1 : 0,
            'int' => (int)$value,
            'float' => (float)$value,
            'datetime' => $value instanceof \DateTimeInterface
                ? $value->format('Y-m-d H:i:s')
                : (string)$value,
            default => $value,
        };
    }

    /**
     * Normaliza a bool los distintos formatos que devuelven los motores.
     * PostgreSQL entrega `t`/`f`; MySQL/SQLite entregan `1`/`0`.
     *
     * @param mixed $value Valor a normalizar
     * @return bool
     */
    protected static function castToBool(mixed $value): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return match (strtolower($value)) {
                't', 'true', '1', 'y', 'yes' => true,
                default => false,
            };
        }

        return (bool)$value;
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
