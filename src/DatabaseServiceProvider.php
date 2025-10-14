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

namespace PhobosFramework\Database;

use PhobosFramework\Core\ServiceProvider;
use PhobosFramework\Core\Container;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Connection\TransactionManager;
use PhobosFramework\Database\Exceptions\ConfigurationException;

/**
 * Service Provider para la integración de base de datos en Phobos Framework
 *
 * Este proveedor de servicios maneja la configuración y gestión de conexiones
 * a bases de datos, incluyendo el registro de drivers y la administración de
 * transacciones.
 * @noinspection PhpUnused
 * @codeCoverageIgnore
 */
class DatabaseServiceProvider extends ServiceProvider {
    /**
     * Indica si el proveedor debe cargarse de forma diferida
     *
     * Este proveedor NO es diferido, lo que significa que se carga
     * automáticamente durante el inicio de la aplicación.
     * @noinspection PhpUnused
     */
    protected bool $defer = false;

    /**
     * Lista de servicios proporcionados por este proveedor
     *
     * Define los identificadores de los servicios que este proveedor
     * registra en el contenedor de dependencias.
     * @noinspection PhpUnused
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
     * Registra los servicios de base de datos en el contenedor
     *
     * Este método registra el ConnectionManager, las interfaces de conexión
     * y el TransactionManager en el contenedor de dependencias, estableciendo
     * también los alias correspondientes para un acceso más conveniente.
     *
     * @param Container $container Contenedor de dependencias
     * @noinspection PhpUnused
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
     * Inicializa el servicio de base de datos
     *
     * Carga la configuración de la base de datos, registra los drivers disponibles,
     * configura las conexiones y establece la conexión predeterminada.
     *
     * @param Container $container Contenedor de dependencias
     * @throws ConfigurationException Si no se encuentra la configuración de la base de datos
     * @noinspection PhpUnused
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
     * Registra los drivers de base de datos configurados
     *
     * Procesa y registra cada driver especificado en la configuración,
     * permitiendo tanto instancias de clase como nombres de clase.
     *
     * @param ConnectionManager $manager Gestor de conexiones
     * @param array $drivers Configuración de drivers
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
     * Registra las conexiones a bases de datos configuradas
     *
     * Procesa y añade cada conexión especificada en la configuración
     * al gestor de conexiones.
     *
     * @param ConnectionManager $manager Gestor de conexiones
     * @param array $connections Configuración de conexiones
     * @return void
     */
    protected function registerConnections(ConnectionManager $manager, array $connections): void {
        foreach ($connections as $name => $config) {
            $manager->addConnection($name, $config);
        }
    }
}
