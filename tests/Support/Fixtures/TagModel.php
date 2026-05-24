<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support\Fixtures;

use InitORM\ORM\Model;

/**
 * Fixture model without an explicit {@code $schema} — exercises the
 * auto-derivation path via {@see \InitORM\ORM\Utils\Helper}.
 */
class TagModel extends Model
{
}
