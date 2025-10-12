<?php

namespace PhobosFramework\Database\Entity;

use PhobosFramework\Database\Exceptions\InvalidArgumentException;
use PhobosFramework\Database\Exceptions\LogicException;
use PhobosFramework\Database\QueryBuilder\InsertQuery;
use PhobosFramework\Database\QueryBuilder\UpdateQuery;
use PhobosFramework\Database\QueryBuilder\DeleteQuery;

/**
 * Clase base para entidades que son tablas
 */
abstract class TableEntity extends EntityManager implements Table {
    /**
     * Columnas que conforman la primary key
     */
    protected static array $pk = [];

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
        ?int              $limitFrom = null,
        ?int              $limitTo = null,
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

        if ($limitFrom !== null) {
            $qb->limit($limitFrom, $limitTo);
        }

        if ($dryRun) {
            return $qb->getQueryWithBindings();
        }

        $rows = $qb->fetch();
        return static::hydrateMany($rows, false);
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

        return static::hydrate($row, false);
    }

    /**
     * Encuentra un registro por su primary key
     *
     * @param mixed ...$pkValues Valores de la PK (en orden de $pk)
     * @return static|null
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
            $where["{$column} = ?"] = $pkValues[$index];
        }

        return static::findFirst($where);
    }

    /**
     * {@inheritdoc}
     */
    public static function delete(
        array $where,
        ?int  $limit = null,
        bool  $dryRun = false
    ): int|array {
        $deleteQuery = (new DeleteQuery(static::getConnection()))
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
     * Ejecuta un INSERT
     *
     * @return bool
     */
    protected function performInsert(): bool {
        $data = $this->toArray();
        // Remover PKs auto-increment que estén vacías
        foreach (static::getPrimaryKey() as $pk) {
            if (empty($data[$pk])) {
                unset($data[$pk]);
            }
        }

        $insertQuery = new InsertQuery(static::getConnection());
        $insertQuery->into(static::getIdentification())->values($data);

        $insertQuery->execute();

        // Si tiene auto-increment, obtener el ID
        if (count(static::getPrimaryKey()) === 1) {
            $pkColumn = static::getPrimaryKey()[0];

            if (empty($this->$pkColumn)) {
                $lastId = static::getConnection()->lastInsertId();
                $this->$pkColumn = $lastId;
            }
        }

        $this->_isNew = false;
        $this->clearDirty();

        return true;
    }

    /**
     * Ejecuta un UPDATE
     *
     * @return bool
     */
    protected function performUpdate(): bool {
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
     * Construye el WHERE para la PK
     *
     * @return array
     */
    protected function buildPkWhere(): array {
        $where = [];

        foreach (static::getPrimaryKey() as $pk) {
            $where["{$pk} = ?"] = $this->$pk;
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
        $deleted = static::delete($where, 1);

        return $deleted > 0;
    }

    /**
     * Recarga el registro desde la base de datos
     *
     * @return bool
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

        // Copiar propiedades del registro fresco
        foreach ($fresh->toArray() as $key => $value) {
            $this->$key = $value;
        }

        $this->clearDirty();
        return true;
    }

    /**
     * Cuenta registros
     *
     * @param array $where Condiciones WHERE
     * @return int
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
     * Verifica si existe al menos un registro con las condiciones dadas
     *
     * @param array $where Condiciones WHERE
     * @return bool
     */
    public static function exists(array $where): bool {
        return static::count($where) > 0;
    }
}
