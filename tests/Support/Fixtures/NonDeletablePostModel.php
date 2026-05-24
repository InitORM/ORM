<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support\Fixtures;

class NonDeletablePostModel extends PostModel
{
    protected bool $deletable = false;
}
