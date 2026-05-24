<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests;

use InitORM\ORM\Entity;
use InitORM\ORM\Exceptions\EntityException;
use InitORM\ORM\Tests\Support\Fixtures\PostEntity;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the Entity's magic-method machinery and the accessor / mutator
 * convention.
 *
 * Regression notes:
 *
 *   - __get must pass the current stored value to the accessor method; the
 *     previous build invoked the accessor with no arguments, leaving any
 *     "$value" parameter undefined.
 *   - __set must guard against re-entry so that a mutator body which writes
 *     back via property assignment ($this->col = ...) does not trigger
 *     itself recursively.
 *   - syncOriginal() must run AFTER the initial fill so the original
 *     snapshot reflects the construction-time data, not an empty array.
 */
final class EntityTest extends TestCase
{
    public function test_attributes_can_be_read_and_written_via_property_syntax(): void
    {
        $entity = new Entity(['title' => 'first']);

        self::assertSame('first', $entity->title);

        $entity->body = 'lorem';
        self::assertSame('lorem', $entity->body);
    }

    public function test_accessor_receives_stored_value(): void
    {
        $entity = new PostEntity(['title' => 'first']);

        // setTitleAttribute trims; getTitleAttribute upper-cases.
        self::assertSame('FIRST', $entity->title);
    }

    public function test_accessor_returns_null_when_attribute_absent(): void
    {
        $entity = new PostEntity();

        self::assertNull($entity->title);
    }

    public function test_mutator_routes_through_set_attribute(): void
    {
        $entity = new PostEntity();
        $entity->title = '  padded  ';

        self::assertSame('PADDED', $entity->title); // trimmed by mutator, upper-cased by accessor
        self::assertSame('padded', $entity->getAttribute('title'));
    }

    public function test_mutator_with_set_attribute_helper(): void
    {
        $entity = new PostEntity();
        $entity->body = '  some text  ';

        self::assertSame('some text', $entity->body);
    }

    public function test_to_array_and_get_attributes_return_attribute_bag(): void
    {
        $entity = new Entity(['a' => 1, 'b' => 2]);

        self::assertSame(['a' => 1, 'b' => 2], $entity->toArray());
        self::assertSame(['a' => 1, 'b' => 2], $entity->getAttributes());
    }

    public function test_isset_and_unset(): void
    {
        $entity = new Entity(['a' => 1]);

        self::assertTrue(isset($entity->a));
        self::assertFalse(isset($entity->b));

        unset($entity->a);
        self::assertFalse(isset($entity->a));
    }

    public function test_debug_info_returns_attributes(): void
    {
        $entity = new Entity(['a' => 1]);

        self::assertSame(['a' => 1], $entity->__debugInfo());
    }

    public function test_sync_original_captures_constructor_data(): void
    {
        $entity = new Entity(['a' => 1, 'b' => 2]);
        $entity->a = 99;

        self::assertSame(['a' => 1, 'b' => 2], $entity->getOriginal());
        self::assertSame(['a' => 99, 'b' => 2], $entity->toArray());
    }

    public function test_sync_original_can_be_re_called(): void
    {
        $entity = new Entity(['a' => 1]);
        $entity->a = 99;
        $entity->syncOriginal();

        self::assertSame(['a' => 99], $entity->getOriginal());
    }

    public function test_get_set_attribute_helpers_bypass_magic_hooks(): void
    {
        $entity = new PostEntity();
        $entity->setAttribute('title', 'raw');

        self::assertSame('raw', $entity->getAttribute('title'));
        // Reading via property still routes through the accessor:
        self::assertSame('RAW', $entity->title);
    }

    public function test_call_default_get_attribute_method(): void
    {
        $entity = new Entity(['post_title' => 'first']);

        self::assertSame('first', $entity->getPostTitleAttribute());
    }

    public function test_call_default_set_attribute_method(): void
    {
        $entity = new Entity();
        $entity->setPostTitleAttribute('first');

        self::assertSame('first', $entity->getAttribute('post_title'));
    }

    public function test_call_throws_for_unknown_method(): void
    {
        $entity = new Entity();

        $this->expectException(EntityException::class);
        /** @phpstan-ignore-next-line — exercising the failure path. */
        $entity->doSomething();
    }
}
