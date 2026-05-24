<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Exceptions;

use Exception;

/**
 * Raised by {@see \InitORM\ORM\Entity::__call()} when the invoked method
 * name does not match the `get{Column}Attribute` / `set{Column}Attribute`
 * convention.
 */
class EntityException extends Exception
{
}
