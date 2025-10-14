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

namespace PhobosFramework\Database\Schema;

use Exception;

/**
 * Registro de alias de schemas.
 * Permite cambiar dinámicamente el schema real sin modificar las entidades.
 *
 * Esta clase implementa el patrón Singleton para mantener un registro centralizado
 * de mapeos entre alias de schemas y sus nombres reales en la base de datos.
 * Esto permite una capa de abstracción entre los nombres usados en el código
 * y los schemas físicos en la base de datos.
 */
class SchemaRegistry {
    /**
     * Instancia única de la clase (patrón Singleton).
     * Se utiliza para mantener un único punto de acceso a los registros de schemas.
     */
    protected static ?self $instance = null;

    /**
     * Almacena el mapeo entre los alias y los nombres reales de los schemas.
     * La clave es el alias y el valor es el nombre real del schema en la base de datos.
     */
    protected array $aliases = [];

    /**
     * Schema predeterminado que se utilizará cuando no se encuentre un alias específico.
     * Puede ser nulo si no se ha configurado un schema por defecto.
     */
    protected ?string $default = null;

    /**
     * Constructor privado que implementa el patrón Singleton.
     * Previene la creación directa de instancias de esta clase.
     */
    private function __construct() {}

    /**
     * Previene la clonación de la instancia Singleton.
     * Este método se sobrescribe para asegurar que no se pueda clonar la instancia.
     *
     * @throws Exception Si se intenta clonar la instancia
     */
    private function __clone() {
        throw new Exception('Cannot clone singleton SchemaRegistry');
    }

    /**
     * Previene la deserialización de la instancia Singleton.
     * Este método se sobrescribe para asegurar que no se pueda deserializar la instancia.
     *
     * @throws Exception Si se intenta deserializar la instancia
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton SchemaRegistry');
    }

    /**
     * Obtiene la instancia única del registro de schemas.
     * Si no existe la instancia, la crea y la devuelve.
     *
     * @return self La instancia única del registro de schemas
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registra un nuevo alias de schema en el registro.
     * Establece una relación entre un alias utilizado en el código y el nombre real del schema.
     *
     * @param string $alias Nombre del alias que se utilizará en las entidades
     * @param string $realSchema Nombre real del schema en la base de datos
     * @return void
     */
    public function register(string $alias, string $realSchema): void {
        $this->aliases[$alias] = $realSchema;
    }

    /**
     * Registra múltiples alias de schemas simultáneamente.
     * Permite registrar varios mapeos de alias a schemas reales en una sola operación.
     *
     * @param array $aliases Array asociativo donde las claves son los alias y los valores son los nombres reales de los schemas
     * @return void
     */
    public function registerMany(array $aliases): void {
        foreach ($aliases as $alias => $realSchema) {
            $this->register($alias, $realSchema);
        }
    }

    /**
     * Resuelve un alias y devuelve el nombre real del schema.
     * Si el alias no está registrado, devuelve el mismo alias como nombre del schema.
     *
     * @param string $alias Alias que se desea resolver
     * @return string Nombre real del schema o el alias original si no existe un mapeo
     */
    public function resolve(string $alias): string {
        return $this->aliases[$alias] ?? $alias;
    }

    /**
     * Verifica si existe un alias específico en el registro.
     * Comprueba si se ha establecido previamente un mapeo para el alias dado.
     *
     * @param string $alias Alias que se desea verificar
     * @return bool true si el alias existe, false en caso contrario
     * @noinspection PhpUnused
     */
    public function has(string $alias): bool {
        return isset($this->aliases[$alias]);
    }

    /**
     * Elimina un alias específico del registro.
     * Remueve el mapeo entre el alias y su schema real correspondiente.
     *
     * @param string $alias Alias que se desea eliminar
     * @return void
     * @noinspection PhpUnused
     */
    public function remove(string $alias): void {
        unset($this->aliases[$alias]);
    }

    /**
     * Obtiene todos los alias registrados con sus schemas correspondientes.
     * Devuelve un array asociativo con todos los mapeos registrados.
     *
     * @return array Array asociativo de [alias => schemaReal]
     * @noinspection PhpUnused
     */
    public function getAll(): array {
        return $this->aliases;
    }

    /**
     * Elimina todos los alias registrados.
     * Limpia completamente el registro de mapeos entre alias y schemas reales.
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function clear(): void {
        $this->aliases = [];
    }

    /**
     * Establece el schema predeterminado que se utilizará.
     * Define el schema que se usará cuando no se encuentre un alias específico.
     *
     * @param string $schema Nombre del schema predeterminado
     * @return void
     * @noinspection PhpUnused
     */
    public function setDefault(string $schema): void {
        $this->default = $schema;
    }

    /**
     * Obtiene el nombre del schema predeterminado configurado.
     * Devuelve null si no se ha establecido ningún schema predeterminado.
     *
     * @return string|null Nombre del schema predeterminado o null si no está configurado
     * @noinspection PhpUnused
     */
    public function getDefault(): ?string {
        return $this->default;
    }
}
