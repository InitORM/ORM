<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests;

use BadMethodCallException;
use InitORM\ORM\Tests\Support\AbstractModelTestCase;
use InitORM\ORM\Tests\Support\Fixtures\PostModel;

/**
 * Locks the {@see \InitORM\ORM\Model::__call()} forwarding contract:
 *
 *   - Builder/database calls that return the underlying Database re-wrap
 *     to the Model so fluent chains stay rooted in the Model.
 *   - Scalar / DataMapper return values propagate unchanged.
 *   - Calls to truly non-existent methods raise BadMethodCallException.
 */
final class ModelCallForwardingTest extends AbstractModelTestCase
{
    public function test_chainable_builder_call_returns_model(): void
    {
        $model = new PostModel();

        $result = $model->where('id', '=', 1);

        self::assertSame($model, $result);
    }

    public function test_chained_builder_calls_compose_into_read(): void
    {
        $model = new PostModel();

        $rows = $model
            ->where('title', '=', 'First')
            ->read()
            ->rows();

        self::assertCount(1, $rows);
    }

    public function test_unknown_method_raises_bad_method_call(): void
    {
        $model = new PostModel();

        $this->expectException(BadMethodCallException::class);
        /** @phpstan-ignore-next-line — exercising the failure path. */
        $model->thisMethodDoesNotExist();
    }
}
