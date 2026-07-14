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

use PhobosFramework\Database\Exceptions\ConnectionException;
use PhobosFramework\Database\Exceptions\InvalidArgumentException;
use PhobosFramework\Database\Exceptions\LogicException;
use PhobosFramework\Database\Exceptions\UnsupportedDriverException;
use PhobosFramework\Database\QueryBuilder\InsertQuery;
use PhobosFramework\Database\QueryBuilder\UpdateQuery;
use PhobosFramework\Database\QueryBuilder\DeleteQuery;
use PhobosFramework\Database\Support\Uuid;

/**
 * Clase base para entidades que representan tablas en la base de datos
 *
 * Esta clase proporciona la funcionalidad básica para interactuar con registros
 * de una tabla, incluyendo operaciones CRUD (Crear, Leer, Actualizar, Eliminar)
 * @noinspection PhpUnused
 */
abstract class TableEntity extends EntityManager implements Table {
    /**
     * Columnas que conforman la clave primaria (primary key) de la tabla
     *
     * @var array Lista de nombres de columnas que forman la clave primaria
     */
    protected static array $pk = [];

    /**
     * Estrategia de generación de la clave primaria (para PK de una sola columna).
     *
     * - `auto`   La base de datos genera el valor (SERIAL/IDENTITY/AUTO_INCREMENT).
     *            Se relee con `RETURNING` si el motor lo soporta, o `lastInsertId()`.
     * - `uuidv7` El framework genera un UUIDv7 en PHP antes del INSERT (sin round-trip).
     * - `manual` La aplicación asigna el valor; se persiste tal cual.
     *
     * Un PK ya seteado (no vacío) siempre gana: se respeta sin generar ni releer.
     * Para claves compuestas usar `manual`.
     */
    protected static string $keyStrategy = 'auto';

    /**
     * {@inheritdoc}
     */
    public static function getPrimaryKey(): array {
        return static::$pk;
    }

    /**
     * {@inheritdoc}
     */
    public static function find(
        array             $where = [],
        string|array|null $order = null,
        ?int              $limit = null,
        ?int              $offset = null,
        bool              $dryRun = false
    ): array {
        $qb = static::query()
            ->select('*')
            ->from(static::getIdentification());

        if (!empty($where)) {
            $qb->where($where);
        }

        if ($order !== null) {
            $qb->orderBy($order);
        }

        if ($limit !== null) {
            $qb->limit($limit, $offset);
        }

        if ($dryRun) {
            return $qb->getQueryWithBindings();
        }

        $rows = $qb->fetch();
        return static::hydrateMany($rows);
    }

    /**
     * {@inheritdoc}
     */
    public static function findFirst(
        array             $where = [],
        string|array|null $order = null,
        bool              $dryRun = false
    ): static|null|array {
        $qb = static::query()
            ->select('*')
            ->from(static::getIdentification())
            ->limit(1);

        if (!empty($where)) {
            $qb->where($where);
        }

        if ($order !== null) {
            $qb->orderBy($order);
        }

        if ($dryRun) {
            return $qb->getQueryWithBindings();
        }

        $row = $qb->fetchFirst();

        if ($row === null) {
            return null;
        }

        return static::hydrate($row);
    }

    /**
     * Encuentra un registro por su clave primaria
     *
     * @param mixed ...$pkValues Valores de la clave primaria (en el mismo orden que fueron definidos en $pk)
     * @return static|null Retorna la entidad si se encuentra, null en caso contrario
     * @throws InvalidArgumentException Si el número de valores no coincide con el número de columnas de la clave primaria
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     * @noinspection PhpUnused
     */
    public static function findByPk(mixed ...$pkValues): ?static {
        $pk = static::getPrimaryKey();

        if (count($pkValues) !== count($pk)) {
            throw new InvalidArgumentException(
                'Number of PK values does not match PK columns'
            );
        }

        $where = [];
        foreach ($pk as $index => $column) {
            $where["$column = ?"] = $pkValues[$index];
        }

        return static::findFirst($where);
    }

    /**
     * Elimina uno o más registros de la tabla que cumplan con las condiciones especificadas
     *
     * @param array $where Condiciones WHERE para filtrar los registros a eliminar
     * @param int|null $limit Cantidad máxima de registros a eliminar
     * @param bool $dryRun Si es verdadero, retorna la consulta SQL sin ejecutarla
     * @return int|array Número de registros eliminados o arreglo con la consulta SQL si dryRun es verdadero
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    public static function delete(
        array $where,
        ?int  $limit = null,
        bool  $dryRun = false
    ): int|array {
        $deleteQuery = new DeleteQuery(static::getConnection())
            ->from(static::getIdentification())
            ->where($where);

        if ($limit !== null) {
            $deleteQuery->limit($limit);
        }

        if ($dryRun) {
            return $deleteQuery->getQueryWithBindings();
        }

        return $deleteQuery->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function save(): bool {
        if (!$this->_isNew) {
            $this->detectChanges();
        }

        if ($this->_isNew) {
            return $this->performInsert();
        } else {
            return $this->performUpdate();
        }
    }

    /**
     * Ejecuta una operación INSERT para crear un nuevo registro en la base de datos
     *
     * @return bool true si la inserción fue exitosa, false en caso contrario
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    protected function performInsert(): bool {
        $data = $this->toArray();
        $pkColumns = static::getPrimaryKey();
        $singlePk = count($pkColumns) === 1 ? $pkColumns[0] : null;

        // Regla de override: un PK de una sola columna ya seteado siempre gana.
        $pkAlreadySet = $singlePk !== null && !empty($this->$singlePk);

        // Estrategia uuidv7: generar el id en PHP antes del INSERT (sin round-trip,
        // portable a los tres motores).
        if ($singlePk !== null && !$pkAlreadySet && static::$keyStrategy === 'uuidv7') {
            $this->$singlePk = Uuid::v7();
            $data[$singlePk] = $this->$singlePk;
            $pkAlreadySet = true;
        }

        // Estrategia auto con PK vacío: dejar que la base de datos lo genere.
        $dbGeneratesPk = $singlePk !== null && !$pkAlreadySet && static::$keyStrategy === 'auto';
        if ($dbGeneratesPk) {
            unset($data[$singlePk]);
        }

        $connection = static::getConnection();
        $insertQuery = new InsertQuery($connection);
        $insertQuery->into(static::getIdentification())->values($data);

        if ($dbGeneratesPk) {
            // Releer el id: RETURNING donde exista, lastInsertId() en el resto.
            $grammar = $connection->getDriver()->getGrammar();

            if ($grammar->supportsReturning()) {
                $id = $insertQuery->executeAndGetId($singlePk);
            } else {
                $insertQuery->execute();
                $id = $connection->lastInsertId();
            }

            if ($id !== false) {
                $this->$singlePk = $id;
            }
        } else {
            $insertQuery->execute();
        }

        $this->_isNew = false;
        $this->clearDirty();

        return true;
    }

    /**
     * Ejecuta una operación UPDATE para actualizar un registro existente en la base de datos
     *
     * @return bool true si la actualización fue exitosa, false si no se modificó ningún registro
     * @throws LogicException Si la entidad no tiene definida una clave primaria
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    protected function performUpdate(): bool {
        // Validar que la entidad tenga primary key definida
        if (empty(static::getPrimaryKey())) {
            throw new LogicException(
                'Cannot update entity ' . static::class . ' without primary key defined. ' .
                'Define the $pk property in your entity class.'
            );
        }

        // Si no hay cambios, no hacer nada
        if (!$this->isDirty()) {
            return true;
        }

        $data = $this->toArray(true); // Solo campos modificados
        $where = $this->buildPkWhere();

        $updateQuery = new UpdateQuery(static::getConnection());
        $updateQuery
            ->table(static::getIdentification())
            ->set($data)
            ->where($where);

        $affected = $updateQuery->execute();
        $this->clearDirty();

        return $affected > 0;
    }

    /**
     * Construye las condiciones WHERE basadas en la clave primaria del registro actual
     *
     * @return array Arreglo asociativo con las condiciones WHERE y sus valores
     */
    protected function buildPkWhere(): array {
        $where = [];

        foreach (static::getPrimaryKey() as $pk) {
            $where["$pk = ?"] = $this->$pk;
        }

        return $where;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(): bool {
        if ($this->_isNew) {
            throw new LogicException('Cannot delete a record that has not been saved');
        }

        $where = $this->buildPkWhere();
        $deleted = static::delete($where);

        return $deleted > 0;
    }

    /**
     * Recarga los datos del registro actual desde la base de datos
     *
     * @return bool true si se pudo recargar el registro, false si no existe
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     * @noinspection PhpUnused
     */
    public function refresh(): bool {
        if ($this->_isNew) {
            return false;
        }

        $where = $this->buildPkWhere();
        $fresh = static::findFirst($where);

        if ($fresh === null) {
            return false;
        }

        // Copiar los valores nativos (ya casteados) del registro fresco. Se copian las
        // propiedades directamente, no toArray(): esa reserializaría a la forma de BD
        // (JSONB a string, boolean a 1/0) y dejaría la entidad con tipos inconsistentes.
        $reflection = new \ReflectionClass($fresh);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $name = $property->getName();

            if ($property->isStatic() || str_starts_with($name, '_')) {
                continue;
            }

            if (!$property->isInitialized($fresh)) {
                continue;
            }

            $this->$name = $fresh->$name;
        }

        $this->clearDirty();
        return true;
    }

    /**
     * Cuenta la cantidad de registros que cumplen con las condiciones especificadas
     *
     * @param array $where Condiciones WHERE para filtrar los registros
     * @return int Número total de registros que cumplen con las condiciones
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     */
    public static function count(array $where = []): int {
        $qb = static::query()
            ->select('COUNT(*) as total')
            ->from(static::getIdentification());

        if (!empty($where)) {
            $qb->where($where);
        }

        return (int)$qb->fetchColumn('total');
    }

    /**
     * Verifica si existe al menos un registro que cumpla con las condiciones especificadas
     *
     * @param array $where Condiciones WHERE para filtrar los registros
     * @return bool true si existe al menos un registro, false en caso contrario
     * @throws ConnectionException Si hay un error al obtener la conexión
     * @throws UnsupportedDriverException Si el driver de base de datos no está soportado
     * @noinspection PhpUnused
     */
    public static function exists(array $where): bool {
        return static::count($where) > 0;
    }
}
