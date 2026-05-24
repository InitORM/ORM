<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Exceptions;

/**
 * Thrown by {@see \InitORM\ORM\Model::delete()} when the model has
 * {@code $deletable = false}.
 */
class DeletableException extends ModelException
{
}
