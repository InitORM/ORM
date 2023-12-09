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
namespace InitORM\ORM;

use ReflectionClass;
use Throwable;
use InitORM\Database\Facade\DB;
use InitORM\ORM\Utils\Helper;
use InitORM\Database\Interfaces\DatabaseInterface;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use InitORM\ORM\Interfaces\EntityInterface;
use InitORM\ORM\Interfaces\ModelInterface;
use InitORM\ORM\Exceptions\{ModelException,
    WritableException,
    ReadableException,
    UpdatableException,
    DeletableException};

abstract class Model implements ModelInterface
{

    /**
     * @var DatabaseInterface
     */
    protected DatabaseInterface $db;

    protected ?array $credentials = null;

    protected string $schema;

    protected string $schemaId = 'id';

    protected string $entity = Entity::class;

    protected bool $writable = true;

    protected bool $readable = true;

    protected bool $updatable = true;

    protected bool $deletable = true;

    protected ?string $createdField = null;

    protected ?string $updatedField = null;

    protected bool $useSoftDeletes = false;

    protected ?string $deletedField = null;

    protected string $timestampFormat = 'Y-m-d H:i:s';

    private bool $isOnlyDelete = false;

    /**
     * @throws Throwable
     */
    public function __construct()
    {
        if (!isset($this->schema)) {
            $modelClass = get_called_class();
            $modelReflection = new ReflectionClass($modelClass);
            $this->schema = Helper::camelCaseToSnakeCase($modelReflection->getShortName());
            unset($modelClass, $modelReflection);
        }
        if ($this->useSoftDeletes !== false && empty($this->deletedField)) {
            throw new ModelException('There must be a delete column to use soft delete.');
        }

        $this->db = empty($this->credentials) ? DB::getDatabase() : DB::connect($this->credentials);
    }

    public function __call(string $name, array $arguments)
    {
        $res = $this->db->{$name}(...$arguments);

        return ($res instanceof DatabaseInterface) ? $this : $res;
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
     * @throws Throwable
     */
    public function create(array $set = []): bool
    {
        if (!$this->writable) {
            throw new WritableException();
        }

        !empty($this->createdField) && $set[$this->createdField] = date($this->timestampFormat);

        return $this->db->create($this->schema, $set);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function createBatch(array $set = []): bool
    {
        if (!$this->writable) {
            throw new WritableException();
        }
        $createdField = $this->createdField;
        if (!empty($createdField) && !empty($set)) {
            foreach ($set as &$row) {
                $row[$createdField] = date($this->timestampFormat);
            }
        }

        return $this->db->createBatch($this->schema, $set);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function read(array $selector = [], array $conditions = []): DataMapperInterface
    {
        if (!$this->readable) {
            throw new ReadableException();
        }
        if ($this->useSoftDeletes) {
            if ($this->isOnlyDelete) {
                $this->onlyDeleted();
            } else {
                $this->ignoreDeleted();
            }
            $this->isOnlyDelete = false;
        }

        return $this->db
            ->read($this->schema, $selector, $conditions)
            ->asClass($this->entity);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function update(array $set = []): bool
    {
        if (!$this->updatable) {
            throw new UpdatableException();
        }

        if (!empty($this->schemaId) && isset($set[$this->schemaId])) {
            $this->db->where($this->schemaId, $set[$this->schemaId]);
            unset($set[$this->schemaId]);
        }

        !empty($this->updatedField) && $set[$this->updatedField] = date($this->timestampFormat);

        $this->ignoreDeleted();

        return $this->db->update($this->schema, $set);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function updateBatch(array $set = [], ?string $referenceColumn = null): bool
    {
        if (!$this->updatable) {
            throw new UpdatableException();
        }
        $updatedField = $this->updatedField;
        if (!empty($updatedField) && !empty($set)) {
            foreach ($set as &$row) {
                $row[$updatedField] = date($this->timestampFormat);
            }
        }
        $this->ignoreDeleted();

        return $this->db->updateBatch($referenceColumn ?? $this->schemaId, $this->schema, $set);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function delete(?array $conditions = null, bool $purge = false): bool
    {
        if (!$this->deletable) {
            throw new DeletableException();
        }
        if ($this->useSoftDeletes && $purge === false) {
            $this->ignoreDeleted()
                ->set($this->deletedField, date($this->timestampFormat));

            if (!empty($conditions)) {
                foreach ($conditions as $column => $value) {
                    if (is_string($column)) {
                        $this->db->where($column, $value);
                    } else {
                        $this->db->where($value);
                    }
                }
            }

            return $this->db->update($this->schema);
        }

        return $this->db->delete($this->schema, $conditions);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function save(EntityInterface $entity): bool
    {
        $data = $entity->toArray();

        return !empty($this->schemaId) && isset($data[$this->schemaId]) ? $this->update($data) : $this->create($data);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function onlyDeleted(): self
    {
        $this->useSoftDeletes && $this->db->whereIsNotNull($this->deletedField);

        return $this;
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function ignoreDeleted(): self
    {
        $this->useSoftDeletes && $this->db->whereIsNull($this->deletedField);

        return $this;
    }

}
