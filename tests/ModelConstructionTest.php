<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests;

use InitORM\ORM\Exceptions\ModelException;
use InitORM\ORM\Model;
use InitORM\ORM\Tests\Support\AbstractModelTestCase;
use InitORM\ORM\Tests\Support\Fixtures\PostModel;
use InitORM\ORM\Tests\Support\Fixtures\TagModel;
use InitORM\ORM\Tests\Support\SqliteHelper;

/**
 * Covers the model construction contract: explicit and auto-derived schemas,
 * the soft-delete invariant, and standalone credentials.
 */
final class ModelConstructionTest extends AbstractModelTestCase
{
    public function test_explicit_schema_is_preserved(): void
    {
        $model = new PostModel();

        self::assertSame('posts', $model->getSchema());
        self::assertSame('id', $model->getSchemaId());
    }

    public function test_schema_is_auto_derived_from_short_name(): void
    {
        SqliteHelper::seedTags($this->connection);

        $model = new TagModel();

        // "TagModel" → "tag_model"  — exercises Helper::camelCaseToSnakeCase.
        self::assertSame('tag_model', $model->getSchema());
    }

    public function test_soft_delete_without_deleted_field_throws(): void
    {
        $this->expectException(ModelException::class);
        $this->expectExceptionMessageMatches('/\$deletedField/');

        new class () extends Model {
            protected string $schema = 'posts';
            protected bool $useSoftDeletes = true;
            // deletedField intentionally left unset
        };
    }

    public function test_model_uses_db_facade_by_default(): void
    {
        $model = new PostModel();

        self::assertSame($this->db, $model->getDatabase());
    }
}
