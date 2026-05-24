<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Exceptions;

/**
 * Thrown by {@see \InitORM\ORM\Model::update()} /
 * {@see \InitORM\ORM\Model::updateBatch()} when the model has
 * {@code $updatable = false}.
 */
class UpdatableException extends ModelException
{
}
