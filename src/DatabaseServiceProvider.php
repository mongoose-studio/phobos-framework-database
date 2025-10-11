<?php

namespace PhobosFramework\Database;

use PhobosFramework\Core\ServiceProvider;
use PhobosFramework\Core\Container;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Connection\TransactionManager;
use PhobosFramework\Database\Exceptions\ConfigurationException;

/**
 * Service Provider para Phobos Database
 */
class DatabaseServiceProvider extends ServiceProvider {
    /**
     * Este provider NO es diferido - se carga siempre
     */
    protected bool $defer = false;

    /**
     * Servicios que proporciona este provider
     */
    protected array $provides = [
        ConnectionManager::class,
        ConnectionInterface::class,
        TransactionManager::class,
        'db',
        'db.manager',
        'db.transaction',
    ];

    /**
     * Registrar servicios en el contenedor
     */
    public function register(Container $container): void {
        // Registrar ConnectionManager como singleton
        $container->singleton(ConnectionManager::class, function () {
            return ConnectionManager::getInstance();
        });

        // Alias: db.manager
        $container->alias('db.manager', ConnectionManager::class);

        // Registrar ConnectionInterface (conexión por defecto)
        $container->bind(ConnectionInterface::class, function (Container $c) {
            return $c->make(ConnectionManager::class)->getConnection();
        });

        // Alias: db
        $container->alias('db', ConnectionInterface::class);

        // Registrar TransactionManager (conexión por defecto)
        $container->bind(TransactionManager::class, function (Container $c) {
            return $c->make(ConnectionManager::class)->getTransactionManager();
        });

        // Alias: db.transaction
        $container->alias('db.transaction', TransactionManager::class);
    }

    /**
     * Bootstrap del servicio - carga configuración y drivers
     */
    public function boot(Container $container): void {
        $config = config('database');

        if (!$config) {
            throw new ConfigurationException('Database configuration not found');
        }

        $manager = $container->make(ConnectionManager::class);

        // Registrar drivers
        $this->registerDrivers($manager, $config['drivers'] ?? []);

        // Registrar conexiones
        $this->registerConnections($manager, $config['connections'] ?? []);

        // Establecer conexión por defecto
        if (isset($config['default'])) {
            $manager->setDefaultConnection($config['default']);
        }
    }

    /**
     * Registra los drivers configurados
     *
     * @param ConnectionManager $manager
     * @param array $drivers
     * @return void
     */
    protected function registerDrivers(ConnectionManager $manager, array $drivers): void {
        foreach ($drivers as $name => $driverClass) {
            if (is_string($driverClass)) {
                // Si es una clase, instanciarla
                $driver = new $driverClass();
            } else {
                // Si ya es una instancia
                $driver = $driverClass;
            }

            $manager->registerDriver($name, $driver);
        }
    }

    /**
     * Registra las conexiones configuradas
     *
     * @param ConnectionManager $manager
     * @param array $connections
     * @return void
     */
    protected function registerConnections(ConnectionManager $manager, array $connections): void {
        foreach ($connections as $name => $config) {
            $manager->addConnection($name, $config);
        }
    }
}
