<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests;

use InitORM\ORM\Tests\Support\AbstractModelTestCase;
use InitORM\ORM\Tests\Support\Fixtures\PostModel;

/**
 * Verifies the soft-delete scope behaviour.
 *
 * Regression: in the previous build, {@code onlyDeleted()} immediately
 * added `whereIsNotNull(deletedField)` but never set its internal flag, so
 * the subsequent {@code read()} would additionally add
 * `whereIsNull(deletedField)`, producing a WHERE that could never match.
 */
final class ModelSoftDeleteTest extends AbstractModelTestCase
{
    public function test_only_deleted_returns_soft_deleted_rows(): void
    {
        $model = new PostModel();

        $rows = $model->onlyDeleted()->read()->rows();

        // The fixture seeds exactly one soft-deleted row.
        self::assertCount(1, $rows);
        self::assertSame('THIRD', $rows[0]->title); // upper-cased by accessor
    }

    public function test_only_deleted_flag_is_consumed_after_one_read(): void
    {
        $model = new PostModel();

        $model->onlyDeleted()->read()->rows();
        $rows = $model->read()->rows(); // back to default scope

        self::assertCount(2, $rows);
    }

    public function test_ignore_deleted_is_default_for_read(): void
    {
        $model = new PostModel();

        $rows = $model->read()->rows();

        // Two non-deleted out of three seeded rows.
        self::assertCount(2, $rows);
    }

    public function test_update_with_soft_deletes_skips_already_deleted_rows(): void
    {
        $model = new PostModel();

        // Try to update the soft-deleted row by PK — should affect 0 rows.
        $model->update(['id' => 3, 'title' => 'Resurrected']);

        $row = $this->db
            ->table('posts')
            ->where('id', '=', 3)
            ->read()
            ->asAssoc()
            ->row();

        self::assertSame('Third', $row['title']);
    }
}
