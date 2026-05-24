<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Interfaces;

use InitORM\Database\Exceptions\DatabaseException;
use InitORM\Database\Interfaces\DatabaseInterface;
use InitORM\DBAL\Connection\Exceptions\ConnectionException;
use InitORM\DBAL\Connection\Exceptions\SQLExecuteException;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use InitORM\ORM\Exceptions\DeletableException;
use InitORM\ORM\Exceptions\ReadableException;
use InitORM\ORM\Exceptions\UpdatableException;
use InitORM\ORM\Exceptions\WritableException;
use InitORM\QueryBuilder\Exceptions\QueryBuilderException;

/**
 * Public contract for an InitORM model. The reference implementation is
 * {@see \InitORM\ORM\Model}.
 *
 * Method-level @throws blocks list the exceptions that originate from the
 * model layer itself; the chained Database / DBAL / QueryBuilder layers may
 * raise additional {@see DatabaseException} / {@see SQLExecuteException} /
 * {@see QueryBuilderException} / {@see ConnectionException} subclasses,
 * which propagate unchanged.
 */
interface ModelInterface
{
    public function __construct();

    /**
     * Backing table name (either {@code $schema} or the auto-derived value).
     */
    public function getSchema(): string;

    /**
     * Primary-key column name.
     */
    public function getSchemaId(): string;

    /**
     * The underlying Database the model is bound to. Useful for sharing the
     * connection with sibling code or starting a transaction:
     *
     *     $model->getDatabase()->transaction(fn () => …);
     */
    public function getDatabase(): DatabaseInterface;

    /**
     * Insert a single row. When configured, {@see \InitORM\ORM\Model::$createdField}
     * is auto-filled with {@code date($timestampFormat)} just before execution.
     *
     * @param array<string, mixed> $set Column → value map.
     *
     * @throws WritableException When {@code $writable} is false.
     */
    public function create(array $set = []): bool;

    /**
     * Insert multiple rows in a single statement.
     *
     * @param array<int, array<string, mixed>> $set One row per element.
     *
     * @throws WritableException When {@code $writable} is false.
     */
    public function createBatch(array $set = []): bool;

    /**
     * Compile and execute a SELECT, returning the result wrapped in a
     * {@see DataMapperInterface} configured to hydrate the model's
     * {@code $entity} class.
     *
     * When soft-deletes are enabled, the WHERE clause is augmented with a
     * `deletedField IS NULL` predicate (or `IS NOT NULL` when
     * {@see self::onlyDeleted()} was called immediately before).
     *
     * @param array<int, string|\InitORM\QueryBuilder\RawQuery> $selector
     *        Optional projection columns.
     * @param array<int|string, mixed>                          $conditions
     *        Optional WHERE shortcuts (string-keyed → `where(key, '=', value)`;
     *        integer-keyed → `where(value)`).
     *
     * @throws ReadableException When {@code $readable} is false.
     */
    public function read(array $selector = [], array $conditions = []): DataMapperInterface;

    /**
     * Update rows.
     *
     * When the model has a non-empty {@code $schemaId} and {@code $set}
     * contains a value for it, that PK is automatically lifted out into a
     * WHERE clause (preventing the PK from being written) and the
     * {@code $set} entry is removed.
     *
     * {@see \InitORM\ORM\Model::$updatedField} is auto-filled when set.
     *
     * @param array<string, mixed>          $set
     * @param array<int|string, mixed>|null $conditions Optional WHERE
     *        shortcuts (same semantics as {@see self::read()}).
     *
     * @throws UpdatableException When {@code $updatable} is false.
     */
    public function update(array $set = [], ?array $conditions = null): bool;

    /**
     * Update rows in a single CASE/WHEN-keyed batch.
     *
     * @param array<int, array<string, mixed>> $set
     * @param string|null                      $referenceColumn The column
     *        used as the CASE key. Defaults to {@code $schemaId}.
     *
     * @throws UpdatableException When {@code $updatable} is false.
     */
    public function updateBatch(array $set = [], ?string $referenceColumn = null): bool;

    /**
     * Delete rows. With soft-deletes on, the row is marked via
     * {@see \InitORM\ORM\Model::$deletedField} unless {@code $purge} is true.
     *
     * @param array<int|string, mixed>|null $conditions Optional WHERE
     *        shortcuts (same semantics as {@see self::read()}).
     * @param bool                          $purge      When true, soft-delete
     *        is bypassed and a real DELETE is issued.
     *
     * @throws DeletableException When {@code $deletable} is false.
     */
    public function delete(?array $conditions = null, bool $purge = false): bool;

    /**
     * Convenience: insert the entity when no PK is set, otherwise update it.
     *
     * @throws WritableException
     * @throws UpdatableException
     */
    public function save(EntityInterface $entity): bool;

    /**
     * Mark the *next* {@see self::read()} call as targeting soft-deleted
     * rows (`deletedField IS NOT NULL` instead of `IS NULL`). The flag is
     * consumed by that next read and resets afterwards.
     */
    public function onlyDeleted(): static;

    /**
     * Immediately add `deletedField IS NULL` to the current pending WHERE.
     * Used internally by update/delete; safe to call from user code to
     * exclude soft-deleted rows from a custom builder chain.
     */
    public function ignoreDeleted(): static;
}
