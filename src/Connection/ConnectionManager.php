<?php

namespace PhobosFramework\Database\Connection;

use PhobosFramework\Database\Drivers\DriverInterface;
use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\UnsupportedDriverException;

/**
 * Administra múltiples conexiones a bases de datos
 */
class ConnectionManager {
    protected static ?self $instance = null;
    protected array $connections = [];
    protected array $configs = [];
    protected array $drivers = [];
    protected ?string $defaultConnection = null;
    protected array $transactionManagers = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Registra un driver
     *
     * @param string $name Nombre del driver
     * @param DriverInterface $driver Instancia del driver
     * @return void
     */
    public function registerDriver(string $name, DriverInterface $driver): void {
        $this->drivers[$name] = $driver;
    }

    /**
     * Agrega una configuración de conexión
     *
     * @param string $name Nombre de la conexión
     * @param array $config Configuración
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
     */
    public function setDefaultConnection(string $name): void {
        if (!isset($this->configs[$name])) {
            throw new ConnectionException("Connection '{$name}' is not configured");
        }

        $this->defaultConnection = $name;
    }

    /**
     * Obtiene una conexión
     *
     * @param string|null $name Nombre de la conexión (null para la por defecto)
     * @return ConnectionInterface
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
     * Crea una nueva conexión
     *
     * @param string $name Nombre de la conexión
     * @return ConnectionInterface
     */
    protected function createConnection(string $name): ConnectionInterface {
        if (!isset($this->configs[$name])) {
            throw new ConnectionException("Connection '{$name}' is not configured");
        }

        $config = $this->configs[$name];
        $driverName = $config['driver'] ?? null;

        if ($driverName === null) {
            throw new ConnectionException("Driver not specified for connection '{$name}'");
        }

        if (!isset($this->drivers[$driverName])) {
            throw new UnsupportedDriverException(
                "Driver '{$driverName}' is not registered. Make sure the driver package is installed."
            );
        }

        $driver = $this->drivers[$driverName];
        return new PDOConnection($name, $config, $driver);
    }

    /**
     * Cierra todas las conexiones
     *
     * @return void
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
     */
    public function close(string $name): void {
        if (isset($this->connections[$name])) {
            $this->connections[$name]->disconnect();
            unset($this->connections[$name]);
            unset($this->transactionManagers[$name]);
        }
    }

    /**
     * Obtiene todas las conexiones configuradas
     *
     * @return array
     */
    public function getConfiguredConnections(): array {
        return array_keys($this->configs);
    }

    /**
     * Obtiene la conexión por defecto
     *
     * @return string|null
     */
    public function getDefaultConnectionName(): ?string {
        return $this->defaultConnection;
    }

    /**
     * Verifica si una conexión existe
     *
     * @param string $name Nombre de la conexión
     * @return bool
     */
    public function hasConnection(string $name): bool {
        return isset($this->configs[$name]);
    }

    /**
     * Obtiene todos los drivers registrados
     *
     * @return array
     */
    public function getRegisteredDrivers(): array {
        return array_keys($this->drivers);
    }
}
