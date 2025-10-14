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

namespace PhobosFramework\Database\Connection;

use Exception;
use PhobosFramework\Database\Drivers\DriverInterface;
use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\UnsupportedDriverException;

/**
 * Administrador de conexiones a bases de datos.
 *
 * Esta clase implementa el patrón Singleton para gestionar múltiples conexiones a bases de datos.
 * Permite registrar drivers, configurar conexiones y administrar las transacciones de manera centralizada.
 */
class ConnectionManager {
    /**
     * @var self|null Instancia única del gestor de conexiones (patrón Singleton)
     */
    protected static ?self $instance = null;

    /**
     * @var array Lista de conexiones activas en el sistema
     */
    protected array $connections = [];

    /**
     * @var array Configuraciones de las diferentes conexiones
     */
    protected array $configs = [];

    /**
     * @var array Drivers de base de datos registrados
     */
    protected array $drivers = [];

    /**
     * @var string|null Identificador de la conexión predeterminada
     */
    protected ?string $defaultConnection = null;

    /**
     * @var array Gestores de transacciones para cada conexión
     */
    protected array $transactionManagers = [];

    /**
     * Constructor privado que previene la instanciación directa.
     *
     * Implementa el patrón Singleton garantizando que solo exista una instancia
     * del gestor de conexiones en toda la aplicación.
     */
    private function __construct() {}

    /**
     * Previene la clonación del singleton.
     *
     * Este método asegura que no se pueda clonar la instancia del ConnectionManager,
     * manteniendo la integridad del patrón Singleton.
     *
     * @throws Exception Si se intenta clonar la instancia
     */
    private function __clone() {
        throw new Exception('Cannot clone singleton ConnectionManager');
    }

    /**
     * Previene la deserialización del singleton.
     *
     * Este método asegura que no se pueda deserializar la instancia del ConnectionManager,
     * manteniendo la integridad del patrón Singleton.
     *
     * @throws Exception Si se intenta deserializar la instancia
     */
    public function __wakeup() {
        throw new Exception('Cannot unserialize singleton ConnectionManager');
    }

    /**
     * Obtiene la instancia única del ConnectionManager.
     *
     * Si no existe una instancia, la crea. Si ya existe, devuelve la instancia existente,
     * implementando así el patrón Singleton.
     *
     * @return self La instancia única del ConnectionManager
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registra un controlador de base de datos
     *
     * @param string $name Identificador único del controlador
     * @param DriverInterface $driver Instancia del controlador a registrar
     * @return void
     */
    public function registerDriver(string $name, DriverInterface $driver): void {
        $this->drivers[$name] = $driver;
    }

    /**
     * Agrega una nueva configuración de conexión
     *
     * @param string $name Identificador único de la conexión
     * @param array $config Parámetros de configuración de la conexión
     * @return void
     */
    public function addConnection(string $name, array $config): void {
        $this->configs[$name] = $config;

        if ($this->defaultConnection === null) {
            $this->defaultConnection = $name;
        }
    }

    /**
     * Establece la conexión por defecto
     *
     * @param string $name Nombre de la conexión
     * @return void
     * @throws ConnectionException
     */
    public function setDefaultConnection(string $name): void {
        if (!isset($this->configs[$name])) {
            throw new ConnectionException("Connection '$name' is not configured");
        }

        $this->defaultConnection = $name;
    }

    /**
     * Obtiene una conexión
     *
     * @param string|null $name Nombre de la conexión (null para la por defecto)
     * @return ConnectionInterface
     * @throws ConnectionException Si la conexión no está configurada o falta el driver
     * @throws UnsupportedDriverException Si el driver especificado no está registrado
     */
    public function getConnection(?string $name = null): ConnectionInterface {
        $name = $name ?? $this->defaultConnection;

        if ($name === null) {
            throw new ConnectionException('No default connection configured');
        }

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->createConnection($name);
        }

        return $this->connections[$name];
    }

    /**
     * Obtiene el TransactionManager para una conexión
     *
     * @param string|null $name Nombre de la conexión
     * @return TransactionManager
     * @throws ConnectionException Si la conexión no está configurada o falta el driver
     * @throws UnsupportedDriverException Si el driver especificado no está registrado
     */
    public function getTransactionManager(?string $name = null): TransactionManager {
        $name = $name ?? $this->defaultConnection;

        if (!isset($this->transactionManagers[$name])) {
            $connection = $this->getConnection($name);
            $this->transactionManagers[$name] = new TransactionManager($connection);
        }

        return $this->transactionManagers[$name];
    }

    /**
     * Crea una nueva instancia de conexión basada en la configuración proporcionada
     *
     * @param string $name Nombre de la conexión a crear
     * @return ConnectionInterface La instancia de conexión creada
     * @throws ConnectionException Si la conexión no está configurada o falta el driver
     * @throws UnsupportedDriverException Si el driver especificado no está registrado
     */
    protected function createConnection(string $name): ConnectionInterface {
        if (!isset($this->configs[$name])) {
            throw new ConnectionException("Connection '$name' is not configured");
        }

        $config = $this->configs[$name];
        $driverName = $config['driver'] ?? null;

        if ($driverName === null) {
            throw new ConnectionException("Driver not specified for connection '$name'");
        }

        if (!isset($this->drivers[$driverName])) {
            throw new UnsupportedDriverException(
                "Driver '$driverName' is not registered. Make sure the driver package is installed."
            );
        }

        $driver = $this->drivers[$driverName];
        return new PDOConnection($name, $config, $driver);
    }

    /**
     * Cierra todas las conexiones
     *
     * @return void
     * @noinspection PhpUnused
     */
    public function closeAll(): void {
        foreach ($this->connections as $connection) {
            $connection->disconnect();
        }

        $this->connections = [];
        $this->transactionManagers = [];
    }

    /**
     * Cierra una conexión específica
     *
     * @param string $name Nombre de la conexión
     * @return void
     * @noinspection PhpUnused
     */
    public function close(string $name): void {
        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
            unset($this->connections[$name]);
            unset($this->transactionManagers[$name]);
        }
    }

    /**
     * Devuelve la lista de identificadores de todas las conexiones configuradas
     *
     * @return array Lista de identificadores de conexión
     * @noinspection PhpUnused
     */
    public function getConfiguredConnections(): array {
        return array_keys($this->configs);
    }

    /**
     * Obtiene la conexión por defecto
     *
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getDefaultConnectionName(): ?string {
        return $this->defaultConnection;
    }

    /**
     * Verifica si existe una configuración de conexión
     *
     * @param string $name Identificador de la conexión a verificar
     * @return bool True si la conexión está configurada, False en caso contrario
     * @noinspection PhpUnused
     */
    public function hasConnection(string $name): bool {
        return isset($this->configs[$name]);
    }

    /**
     * Obtiene todos los drivers registrados
     *
     * @return array
     * @noinspection PhpUnused
     */
    public function getRegisteredDrivers(): array {
        return array_keys($this->drivers);
    }
}
