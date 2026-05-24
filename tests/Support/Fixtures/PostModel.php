<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support\Fixtures;

use InitORM\ORM\Model;

/**
 * Standard fixture model. Mirrors the `posts` table seeded by
 * {@see \InitORM\ORM\Tests\Support\SqliteHelper::seedPosts()}.
 */
class PostModel extends Model
{
    protected string $schema = 'posts';

    protected string $schemaId = 'id';

    protected bool $useSoftDeletes = true;

    protected ?string $createdField = 'created_at';

    protected ?string $updatedField = 'updated_at';

    protected ?string $deletedField = 'deleted_at';

    protected string $entity = PostEntity::class;
}
