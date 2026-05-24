<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests;

use InitORM\ORM\Utils\Helper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Locks the naming-convention behaviour {@see Helper} provides to the model
 * (auto-schema derivation) and to the entity (attribute name resolution).
 *
 * Regression: the previous implementation used {@code preg_split('')} (an
 * invalid empty pattern that emits a warning and silently fails on most
 * runtimes) and only ever prepended a single underscore at index 0; it
 * could not insert a separator between camel-case word boundaries.
 */
final class HelperTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function camelToSnakeProvider(): array
    {
        return [
            'single lowercase'    => ['posts', 'posts'],
            'single PascalCase'   => ['Posts', 'posts'],
            'two words PascalCase' => ['PostCategory', 'post_category'],
            'three words'         => ['PostCategoryTag', 'post_category_tag'],
            'acronym + word'      => ['XMLParser', 'xml_parser'],
            'long acronym'        => ['HTTPRequest', 'http_request'],
            'already snake'       => ['post_title', 'post_title'],
            'digit boundary'      => ['User2Login', 'user2_login'],
        ];
    }

    #[DataProvider('camelToSnakeProvider')]
    public function test_camel_case_to_snake_case_handles_real_identifiers(string $input, string $expected): void
    {
        self::assertSame($expected, Helper::camelCaseToSnakeCase($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function snakeToPascalProvider(): array
    {
        return [
            'single'           => ['posts', 'Posts'],
            'two segments'     => ['post_title', 'PostTitle'],
            'three segments'   => ['post_category_tag', 'PostCategoryTag'],
            'already pascal'   => ['PostTitle', 'Posttitle'],
            'leading underscore' => ['_hidden', 'Hidden'],
        ];
    }

    #[DataProvider('snakeToPascalProvider')]
    public function test_snake_case_to_pascal_case_round_trips(string $input, string $expected): void
    {
        self::assertSame($expected, Helper::snakeCaseToPascalCase($input));
    }

    public function test_round_trip_snake_pascal_snake(): void
    {
        $snake = 'post_category_tag';

        self::assertSame(
            $snake,
            Helper::camelCaseToSnakeCase(Helper::snakeCaseToPascalCase($snake))
        );
    }
}
