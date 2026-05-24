<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support\Fixtures;

class NonUpdatablePostModel extends PostModel
{
    protected bool $updatable = false;
}
