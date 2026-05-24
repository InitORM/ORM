<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Exceptions;

/**
 * Thrown by {@see \InitORM\ORM\Model::read()} when the model has
 * {@code $readable = false}.
 */
class ReadableException extends ModelException
{
}
