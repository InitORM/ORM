<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support\Fixtures;

use InitORM\ORM\Entity;

/**
 * Fixture entity exercising both accessor and mutator hooks. Both mutators
 * use the {@see Entity::setAttribute()} helper — the only safe way to write
 * back from a mutator body. Plain `$this->col = ...` would create a dynamic
 * property and bypass the attribute bag entirely.
 */
class PostEntity extends Entity
{
    /**
     * Accessor — receives the stored value and returns the transformed
     * form. {@see Entity::__get()} passes the current attribute as the
     * sole argument.
     */
    public function getTitleAttribute(mixed $value): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }

    public function setTitleAttribute(mixed $value): void
    {
        $this->setAttribute('title', is_string($value) ? trim($value) : $value);
    }

    public function setBodyAttribute(mixed $value): void
    {
        $this->setAttribute('body', is_string($value) ? trim($value) : $value);
    }
}
