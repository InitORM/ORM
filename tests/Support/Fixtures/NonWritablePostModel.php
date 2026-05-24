<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support\Fixtures;

class NonWritablePostModel extends PostModel
{
    protected bool $writable = false;
}
