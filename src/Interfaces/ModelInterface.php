<?php
/**
 * InitORM ORM
 *
 * This file is part of InitORM ORM.
 *
 * @author      Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright   Copyright © 2023 Muhammet ŞAFAK
 * @license     ./LICENSE  MIT
 * @version     1.0
 * @link        https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);
namespace InitORM\ORM\Interfaces;

use InitORM\Database\Interfaces\DatabaseInterface;
use InitORM\DBAL\Connection\Exceptions\SQLExecuteException;
use InitORM\QueryBuilder\Exceptions\QueryBuilderException;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use \InitORM\ORM\Exceptions\{WritableException, ReadableException, UpdatableException, DeletableException};

/**
 * @mixin DatabaseInterface
 */
interface ModelInterface
{

    public function __construct();


    /**
     * @return string
     */
    public function getSchema(): string;

    /**
     * @return string
     */
    public function getSchemaId(): string;

    /**
     * @param array $set
     * @return bool
     * @throws SQLExecuteException
     * @throws QueryBuilderException
     * @throws WritableException
     */
    public function create(array $set = []): bool;

    /**
     * @param array $set
     * @return bool
     * @throws SQLExecuteException
     * @throws QueryBuilderException
     * @throws WritableException
     */
    public function createBatch(array $set = []): bool;

    /**
     * @param array $selector
     * @param array $conditions
     * @return DataMapperInterface
     * @throws SQLExecuteException
     * @throws QueryBuilderException
     * @throws ReadableException
     */
    public function read(array $selector = [], array $conditions = []): DataMapperInterface;

    /**
     * @param array $set
     * @return bool
     * @throws SQLExecuteException
     * @throws QueryBuilderException
     * @throws UpdatableException
     */
    public function update(array $set = []): bool;

    /**
     * @param array $set
     * @param string|null $referenceColumn
     * @return bool
     * @throws SQLExecuteException
     * @throws QueryBuilderException
     * @throws UpdatableException
     */
    public function updateBatch(array $set = [], ?string $referenceColumn = null): bool;

    /**
     * @param array|null $conditions
     * @param bool $purge
     * @return bool
     * @throws SQLExecuteException
     * @throws QueryBuilderException
     * @throws DeletableException
     */
    public function delete(?array $conditions = null, bool $purge = false): bool;

    /**
     * @param EntityInterface $entity
     * @return bool
     * @throws SQLExecuteException
     * @throws QueryBuilderException
     * @throws WritableException
     * @throws UpdatableException
     */
    public function save(EntityInterface $entity): bool;

    /**
     * @return self
     */
    public function onlyDeleted(): self;

    /**
     * @return self
     */
    public function ignoreDeleted(): self;

}
