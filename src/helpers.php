<?php

use Phobos\Database\Schema\SchemaRegistry;
use PhobosFramework\Database\Connection\ConnectionManager;
use PhobosFramework\Database\Connection\ConnectionInterface;
use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\TransactionException;
use PhobosFramework\Database\QueryBuilder\DeleteQuery;
use PhobosFramework\Database\QueryBuilder\InsertQuery;
use PhobosFramework\Database\QueryBuilder\QueryBuilder;
use PhobosFramework\Database\QueryBuilder\UpdateQuery;

if (!function_exists('db')) {
    /**
     * Obtiene una conexión a la base de datos
     *
     * @param string|null $connection Nombre de la conexión
     * @return ConnectionInterface
     * @throws ConnectionException
     */
    function db(?string $connection = null): ConnectionInterface {
        return ConnectionManager::getInstance()->getConnection($connection);
    }
}

if (!function_exists('beginTransaction')) {
    /**
     * Inicia una transacción (soporta anidamiento con savepoints)
     *
     * @param string|null $connection Nombre de la conexión
     * @return string|null Nombre del savepoint creado (null si es transacción raíz)
     */
    function beginTransaction(?string $connection = null): ?string {
        return ConnectionManager::getInstance()
            ->getTransactionManager($connection)
            ->begin();
    }
}

if (!function_exists('commit')) {
    /**
     * Commitea la transacción actual
     *
     * @param string|null $savepoint Savepoint específico a commitear
     * @param string|null $connection Nombre de la conexión
     * @return void
     * @throws TransactionException
     */
    function commit(?string $savepoint = null, ?string $connection = null): void {
        ConnectionManager::getInstance()
            ->getTransactionManager($connection)
            ->commit($savepoint);
    }
}

if (!function_exists('rollback')) {
    /**
     * Hace rollback de la transacción actual
     *
     * @param string|null $savepoint Savepoint específico al que volver (null para rollback completo)
     * @param string|null $connection Nombre de la conexión
     * @return void
     * @throws TransactionException
     */
    function rollback(?string $savepoint = null, ?string $connection = null): void {
        ConnectionManager::getInstance()
            ->getTransactionManager($connection)
            ->rollback($savepoint);
    }
}

if (!function_exists('transaction')) {
    /**
     * Ejecuta código dentro de una transacción
     *
     * @param callable $callback Callback a ejecutar
     * @param string|null $connection Nombre de la conexión
     * @return mixed Resultado del callback
     * @throws Throwable
     */
    function transaction(callable $callback, ?string $connection = null): mixed {
        $manager = ConnectionManager::getInstance()->getTransactionManager($connection);
        $savepoint = $manager->begin();

        try {
            $result = $callback(db($connection));
            $manager->commit($savepoint);
            return $result;
        } catch (Throwable $e) {
            $manager->rollback($savepoint);
            throw $e;
        }
    }
}

if (!function_exists('inTransaction')) {
    /**
     * Verifica si hay una transacción activa
     *
     * @param string|null $connection Nombre de la conexión
     * @return bool
     */
    function inTransaction(?string $connection = null): bool {
        return ConnectionManager::getInstance()
            ->getTransactionManager($connection)
            ->isActive();
    }
}

if (!function_exists('getTransactionLevel')) {
    /**
     * Obtiene el nivel de anidamiento de transacciones
     *
     * @param string|null $connection Nombre de la conexión
     * @return int
     */
    function getTransactionLevel(?string $connection = null): int {
        return ConnectionManager::getInstance()
            ->getTransactionManager($connection)
            ->getLevel();
    }
}

if (!function_exists('dbConfig')) {
    /**
     * Configura el ConnectionManager (helper para inicialización)
     *
     * @param array $connections Array de configuraciones de conexiones
     * @param array $drivers Array de drivers registrados
     * @param string|null $default Conexión por defecto
     * @return void
     * @throws ConnectionException
     */
    function dbConfig(array $connections, array $drivers = [], ?string $default = null): void {
        $manager = ConnectionManager::getInstance();

        // Registrar drivers
        foreach ($drivers as $name => $driver) {
            $manager->registerDriver($name, $driver);
        }

        // Agregar conexiones
        foreach ($connections as $name => $config) {
            $manager->addConnection($name, $config);
        }

        // Establecer default
        if ($default !== null) {
            $manager->setDefaultConnection($default);
        }
    }
}

if (!function_exists('query')) {
    /**
     * Crea una nueva instancia de QueryBuilder
     *
     * @param string|null $connection Nombre de la conexión
     * @return QueryBuilder
     * @throws ConnectionException
     */
    function query(?string $connection = null): QueryBuilder {
        $conn = ConnectionManager::getInstance()->getConnection($connection);
        return new QueryBuilder($conn);
    }
}


if (!function_exists('insert')) {
    /**
     * Crea una nueva instancia de InsertQuery
     *
     * @param string|null $connection Nombre de la conexión
     * @return InsertQuery
     * @throws ConnectionException
     */
    function insert(?string $connection = null): InsertQuery {
        $conn = ConnectionManager::getInstance()->getConnection($connection);
        return new InsertQuery($conn);
    }
}

if (!function_exists('update')) {
    /**
     * Crea una nueva instancia de UpdateQuery
     *
     * @param string|null $connection Nombre de la conexión
     * @return UpdateQuery
     * @throws ConnectionException
     */
    function update(?string $connection = null): UpdateQuery {
        $conn = ConnectionManager::getInstance()->getConnection($connection);
        return new UpdateQuery($conn);
    }
}

if (!function_exists('delete')) {
    /**
     * Crea una nueva instancia de DeleteQuery
     *
     * @param string|null $connection Nombre de la conexión
     * @return DeleteQuery
     * @throws ConnectionException
     */
    function delete(?string $connection = null): DeleteQuery {
        $conn = ConnectionManager::getInstance()->getConnection($connection);
        return new DeleteQuery($conn);
    }
}

if (!function_exists('schemaAlias')) {
    /**
     * Registra un alias de schema
     *
     * @param string $alias Alias usado en entidades
     * @param string $realSchema Schema real en BD
     * @return void
     */
    function schemaAlias(string $alias, string $realSchema): void {
        SchemaRegistry::getInstance()->register($alias, $realSchema);
    }
}

if (!function_exists('schemaBulkAlias')) {
    /**
     * Registra múltiples alias de schemas
     *
     * @param array $aliases [alias => realSchema, ...]
     * @return void
     */
    function schemaBulkAlias(array $aliases): void {
        SchemaRegistry::getInstance()->registerMany($aliases);
    }
}
