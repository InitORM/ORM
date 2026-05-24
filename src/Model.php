<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM;

use BadMethodCallException;
use InitORM\Database\Facade\DB;
use InitORM\Database\Interfaces\DatabaseInterface;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use InitORM\ORM\Exceptions\DeletableException;
use InitORM\ORM\Exceptions\ModelException;
use InitORM\ORM\Exceptions\ReadableException;
use InitORM\ORM\Exceptions\UpdatableException;
use InitORM\ORM\Exceptions\WritableException;
use InitORM\ORM\Interfaces\EntityInterface;
use InitORM\ORM\Interfaces\ModelInterface;
use InitORM\ORM\Utils\Helper;
use ReflectionClass;

/**
 * Abstract base for active-record-style models. Each subclass binds a
 * database table (via {@see self::$schema}) to an entity class (via
 * {@see self::$entity}) and exposes the CRUD helpers declared on
 * {@see ModelInterface}.
 *
 * A model holds a private {@see DatabaseInterface}. Unknown method calls are
 * forwarded to it via {@see self::__call()}; when the underlying Database
 * returns itself (chainable builder calls), the call is re-wrapped to return
 * this Model so fluent chains span the wrapper boundary — for example
 * `MyModel::where(...)->limit(...)->read()` works because both `where` and
 * `limit` return the Model.
 *
 * @mixin DatabaseInterface
 */
abstract class Model implements ModelInterface
{
    protected DatabaseInterface $db;

    /**
     * Optional standalone connection credentials. When null, the model
     * binds to the shared {@see DB::getDatabase()} facade instance.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $credentials = null;

    /**
     * Backing table name. Auto-derived from the subclass short name via
     * {@see Helper::camelCaseToSnakeCase()} when left unset.
     */
    protected string $schema;

    /**
     * Primary-key column. Used by {@see self::update()} to lift the PK out
     * of the SET map into a WHERE clause and by {@see self::save()} to
     * decide between insert and update.
     */
    protected string $schemaId = 'id';

    /**
     * Entity class used to hydrate read() results. Must implement
     * {@see EntityInterface} (otherwise hydration is delegated to PDO's
     * FETCH_CLASS which still works, but the contract is loosened).
     *
     * @var class-string
     */
    protected string $entity = Entity::class;

    protected bool $writable = true;

    protected bool $readable = true;

    protected bool $updatable = true;

    protected bool $deletable = true;

    /**
     * Column name auto-filled with `date($timestampFormat)` on each create.
     * Disabled when null.
     */
    protected ?string $createdField = null;

    /**
     * Column name auto-filled with `date($timestampFormat)` on each update.
     * Disabled when null.
     */
    protected ?string $updatedField = null;

    /**
     * When true, {@see self::delete()} sets {@see self::$deletedField}
     * instead of issuing a DELETE; reads filter out rows whose
     * {@see self::$deletedField} is non-null. {@see self::$deletedField} is
     * required when this is on (enforced in the constructor).
     */
    protected bool $useSoftDeletes = false;

    /**
     * Column name used to mark a row as soft-deleted (must be nullable in
     * the underlying schema). Required when {@see self::$useSoftDeletes} is
     * true.
     */
    protected ?string $deletedField = null;

    /**
     * `date()` format string used for created / updated / deleted columns.
     */
    protected string $timestampFormat = 'Y-m-d H:i:s';

    /**
     * One-shot scope flag set by {@see self::onlyDeleted()}; consumed by the
     * next {@see self::read()} call.
     */
    private bool $isOnlyDeleted = false;

    /**
     * @throws ModelException When {@see self::$useSoftDeletes} is on but no
     *         {@see self::$deletedField} is configured.
     */
    public function __construct()
    {
        if (!isset($this->schema)) {
            $shortName    = (new ReflectionClass($this))->getShortName();
            $this->schema = Helper::camelCaseToSnakeCase($shortName);
        }

        if ($this->useSoftDeletes && empty($this->deletedField)) {
            throw new ModelException(sprintf(
                '%s has $useSoftDeletes enabled but $deletedField is not configured.',
                static::class
            ));
        }

        $this->db = $this->credentials === null
            ? DB::getDatabase()
            : DB::connect($this->credentials);
    }

    /**
     * Forward unknown calls to the inner {@see DatabaseInterface}. Chainable
     * calls (those the Database forwards back as itself) re-wrap to return
     * this Model so fluent chains continue across the wrapper boundary.
     *
     * @param array<int, mixed> $arguments
     *
     * @throws BadMethodCallException When the method does not exist on the
     *         underlying Database or query builder.
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!method_exists($this->db, $name)) {
            // Database itself forwards to the builder via __call; we can't
            // method_exists() the builder transparently, so let Database
            // decide and surface its DatabaseException as-is. The presence
            // check is best-effort for the direct Database surface.
            try {
                $result = $this->db->{$name}(...$arguments);
            } catch (\Throwable $e) {
                throw new BadMethodCallException(
                    sprintf('Method "%s::%s" does not exist.', static::class, $name),
                    0,
                    $e
                );
            }
        } else {
            $result = $this->db->{$name}(...$arguments);
        }

        return $result instanceof DatabaseInterface ? $this : $result;
    }

    /**
     * @inheritDoc
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaId(): string
    {
        return $this->schemaId;
    }

    /**
     * @inheritDoc
     */
    public function getDatabase(): DatabaseInterface
    {
        return $this->db;
    }

    /**
     * @inheritDoc
     */
    public function create(array $set = []): bool
    {
        if (!$this->writable) {
            throw new WritableException(sprintf('%s is not writable.', static::class));
        }

        if ($this->createdField !== null && $this->createdField !== '') {
            $set[$this->createdField] = date($this->timestampFormat);
        }

        return $this->db->create($this->schema, $set);
    }

    /**
     * @inheritDoc
     */
    public function createBatch(array $set = []): bool
    {
        if (!$this->writable) {
            throw new WritableException(sprintf('%s is not writable.', static::class));
        }

        if ($this->createdField !== null && $this->createdField !== '' && !empty($set)) {
            $now = date($this->timestampFormat);
            foreach ($set as &$row) {
                $row[$this->createdField] = $now;
            }
            unset($row);
        }

        return $this->db->createBatch($this->schema, $set);
    }

    /**
     * @inheritDoc
     */
    public function read(array $selector = [], array $conditions = []): DataMapperInterface
    {
        if (!$this->readable) {
            throw new ReadableException(sprintf('%s is not readable.', static::class));
        }

        if ($this->useSoftDeletes) {
            if ($this->isOnlyDeleted) {
                $this->db->whereIsNotNull($this->deletedField);
                $this->isOnlyDeleted = false;
            } else {
                $this->db->whereIsNull($this->deletedField);
            }
        }

        return $this->db
            ->read($this->schema, $selector, $conditions)
            ->asClass($this->entity);
    }

    /**
     * @inheritDoc
     */
    public function update(array $set = [], ?array $conditions = null): bool
    {
        if (!$this->updatable) {
            throw new UpdatableException(sprintf('%s is not updatable.', static::class));
        }

        if ($this->schemaId !== '' && isset($set[$this->schemaId])) {
            $this->db->where($this->schemaId, '=', $set[$this->schemaId]);
            unset($set[$this->schemaId]);
        }

        if ($this->updatedField !== null && $this->updatedField !== '') {
            $set[$this->updatedField] = date($this->timestampFormat);
        }

        if ($this->useSoftDeletes) {
            $this->db->whereIsNull($this->deletedField);
        }

        return $this->db->update($this->schema, $set, $conditions);
    }

    /**
     * @inheritDoc
     */
    public function updateBatch(array $set = [], ?string $referenceColumn = null): bool
    {
        if (!$this->updatable) {
            throw new UpdatableException(sprintf('%s is not updatable.', static::class));
        }

        if ($this->updatedField !== null && $this->updatedField !== '' && !empty($set)) {
            $now = date($this->timestampFormat);
            foreach ($set as &$row) {
                $row[$this->updatedField] = $now;
            }
            unset($row);
        }

        if ($this->useSoftDeletes) {
            $this->db->whereIsNull($this->deletedField);
        }

        return $this->db->updateBatch($referenceColumn ?? $this->schemaId, $this->schema, $set);
    }

    /**
     * @inheritDoc
     */
    public function delete(?array $conditions = null, bool $purge = false): bool
    {
        if (!$this->deletable) {
            throw new DeletableException(sprintf('%s is not deletable.', static::class));
        }

        if ($this->useSoftDeletes && !$purge) {
            $this->db
                ->whereIsNull($this->deletedField)
                ->set($this->deletedField, date($this->timestampFormat));

            return $this->db->update($this->schema, null, $conditions);
        }

        return $this->db->delete($this->schema, $conditions);
    }

    /**
     * @inheritDoc
     */
    public function save(EntityInterface $entity): bool
    {
        $data = $entity->toArray();

        $hasId = $this->schemaId !== ''
            && isset($data[$this->schemaId])
            && $data[$this->schemaId] !== null
            && $data[$this->schemaId] !== '';

        return $hasId ? $this->update($data) : $this->create($data);
    }

    /**
     * @inheritDoc
     */
    public function onlyDeleted(): static
    {
        $this->isOnlyDeleted = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function ignoreDeleted(): static
    {
        if ($this->useSoftDeletes) {
            $this->db->whereIsNull($this->deletedField);
        }

        return $this;
    }
}
