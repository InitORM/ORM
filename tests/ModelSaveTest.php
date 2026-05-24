<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests;

use InitORM\ORM\Tests\Support\AbstractModelTestCase;
use InitORM\ORM\Tests\Support\Fixtures\PostEntity;
use InitORM\ORM\Tests\Support\Fixtures\PostModel;

/**
 * Locks {@see \InitORM\ORM\Model::save()}'s insert-vs-update decision:
 * when the entity carries a non-empty primary-key value it must update,
 * otherwise it must insert.
 */
final class ModelSaveTest extends AbstractModelTestCase
{
    public function test_save_inserts_when_no_id_is_set(): void
    {
        $model  = new PostModel();
        $entity = new PostEntity(['title' => 'New', 'body' => 'nb']);

        self::assertTrue($model->save($entity));

        $rows = $this->db->table('posts')->read()->asAssoc()->rows();
        self::assertCount(4, $rows);
    }

    public function test_save_updates_when_id_is_set(): void
    {
        $model  = new PostModel();
        $entity = new PostEntity(['id' => 1, 'title' => 'Renamed', 'body' => 'b1']);

        self::assertTrue($model->save($entity));

        $row = $this->db
            ->table('posts')
            ->where('id', '=', 1)
            ->read()
            ->asAssoc()
            ->row();

        self::assertSame('Renamed', $row['title']);
    }
}
