<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests;

use InitORM\ORM\Exceptions\DeletableException;
use InitORM\ORM\Exceptions\ReadableException;
use InitORM\ORM\Exceptions\UpdatableException;
use InitORM\ORM\Exceptions\WritableException;
use InitORM\ORM\Tests\Support\AbstractModelTestCase;
use InitORM\ORM\Tests\Support\Fixtures\NonDeletablePostModel;
use InitORM\ORM\Tests\Support\Fixtures\NonReadablePostModel;
use InitORM\ORM\Tests\Support\Fixtures\NonUpdatablePostModel;
use InitORM\ORM\Tests\Support\Fixtures\NonWritablePostModel;

/**
 * Locks the per-operation permission flags: a model with any of
 * {@code $writable/$readable/$updatable/$deletable} set to false must throw
 * the matching typed exception when the corresponding operation is attempted.
 */
final class ModelGateTest extends AbstractModelTestCase
{
    public function test_writable_false_blocks_create(): void
    {
        $model = new NonWritablePostModel();

        $this->expectException(WritableException::class);
        $model->create(['title' => 'x']);
    }

    public function test_writable_false_blocks_create_batch(): void
    {
        $model = new NonWritablePostModel();

        $this->expectException(WritableException::class);
        $model->createBatch([['title' => 'x']]);
    }

    public function test_readable_false_blocks_read(): void
    {
        $model = new NonReadablePostModel();

        $this->expectException(ReadableException::class);
        $model->read();
    }

    public function test_updatable_false_blocks_update(): void
    {
        $model = new NonUpdatablePostModel();

        $this->expectException(UpdatableException::class);
        $model->update(['title' => 'x']);
    }

    public function test_updatable_false_blocks_update_batch(): void
    {
        $model = new NonUpdatablePostModel();

        $this->expectException(UpdatableException::class);
        $model->updateBatch([['id' => 1, 'title' => 'x']]);
    }

    public function test_deletable_false_blocks_delete(): void
    {
        $model = new NonDeletablePostModel();

        $this->expectException(DeletableException::class);
        $model->delete(['id' => 1]);
    }
}
