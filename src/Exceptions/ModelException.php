<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Exceptions;

use Exception;

/**
 * Root exception for the ORM layer. Subclasses identify the specific
 * operation gate that was tripped ({@see WritableException},
 * {@see ReadableException}, {@see UpdatableException},
 * {@see DeletableException}) or signal an inconsistent model
 * configuration discovered at construction time.
 */
class ModelException extends Exception
{
}
