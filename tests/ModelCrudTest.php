<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests;

use InitORM\ORM\Tests\Support\AbstractModelTestCase;
use InitORM\ORM\Tests\Support\Fixtures\PostEntity;
use InitORM\ORM\Tests\Support\Fixtures\PostModel;

final class ModelCrudTest extends AbstractModelTestCase
{
    public function test_create_inserts_row_and_fills_created_field(): void
    {
        $model = new PostModel();

        $ok = $model->create(['title' => 'Fresh', 'body' => 'body4']);

        self::assertTrue($ok);

        $rows = $this->db->table('posts')->read()->asAssoc()->rows();
        self::assertCount(4, $rows);

        $inserted = end($rows);
        self::assertSame('Fresh', $inserted['title']);
        self::assertNotNull($inserted['created_at']);
    }

    public function test_create_batch_fills_created_field_on_every_row(): void
    {
        $model = new PostModel();

        $ok = $model->createBatch([
            ['title' => 'B1', 'body' => 'b1'],
            ['title' => 'B2', 'body' => 'b2'],
        ]);

        self::assertTrue($ok);

        $rows = $this->db->table('posts')->read()->asAssoc()->rows();
        self::assertCount(5, $rows);
        foreach (array_slice($rows, -2) as $row) {
            self::assertNotNull($row['created_at']);
        }
    }

    public function test_read_hydrates_entity_class(): void
    {
        $model  = new PostModel();
        $result = $model->read();

        $first = $result->row();
        self::assertInstanceOf(PostEntity::class, $first);
    }

    public function test_read_filters_soft_deleted_by_default(): void
    {
        $model = new PostModel();

        $rows = $model->read()->rows();

        // Three rows seeded; one is soft-deleted.
        self::assertCount(2, $rows);
    }

    public function test_update_lifts_primary_key_into_where_clause(): void
    {
        $model = new PostModel();

        $ok = $model->update(['id' => 1, 'title' => 'Renamed']);

        self::assertTrue($ok);

        $row = $this->db
            ->table('posts')
            ->where('id', '=', 1)
            ->read()
            ->asAssoc()
            ->row();

        self::assertSame('Renamed', $row['title']);
        self::assertNotNull($row['updated_at']);

        // Second row must not have been touched.
        $row2 = $this->db
            ->table('posts')
            ->where('id', '=', 2)
            ->read()
            ->asAssoc()
            ->row();
        self::assertSame('Second', $row2['title']);
    }

    public function test_update_accepts_explicit_conditions(): void
    {
        $model = new PostModel();

        $ok = $model->update(['title' => 'Updated'], ['id' => 2]);

        self::assertTrue($ok);

        $row = $this->db
            ->table('posts')
            ->where('id', '=', 2)
            ->read()
            ->asAssoc()
            ->row();

        self::assertSame('Updated', $row['title']);
    }

    public function test_delete_soft_deletes_by_default(): void
    {
        $model = new PostModel();

        $ok = $model->delete(['id' => 1]);

        self::assertTrue($ok);

        // The row is still in the table, but `deleted_at` is now set.
        $row = $this->db
            ->table('posts')
            ->where('id', '=', 1)
            ->read()
            ->asAssoc()
            ->row();

        self::assertNotNull($row);
        self::assertNotNull($row['deleted_at']);
    }

    public function test_delete_with_purge_removes_row(): void
    {
        $model = new PostModel();

        $ok = $model->delete(['id' => 1], purge: true);

        self::assertTrue($ok);

        $row = $this->db
            ->table('posts')
            ->where('id', '=', 1)
            ->read()
            ->asAssoc()
            ->row();

        self::assertNull($row);
    }
}
