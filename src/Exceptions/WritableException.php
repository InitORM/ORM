<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Exceptions;

/**
 * Thrown by {@see \InitORM\ORM\Model::create()} /
 * {@see \InitORM\ORM\Model::createBatch()} when the model has
 * {@code $writable = false}.
 */
class WritableException extends ModelException
{
}
