<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support\Fixtures;

class NonReadablePostModel extends PostModel
{
    protected bool $readable = false;
}
