<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Utils;

/**
 * Internal naming-convention helpers used by {@see \InitORM\ORM\Model} (to
 * auto-derive a schema name from a class short name) and by
 * {@see \InitORM\ORM\Entity} (to translate snake_case column names to the
 * `get{Column}Attribute` / `set{Column}Attribute` accessor convention).
 *
 * Conversions are deliberately Unicode-naive and operate on the ASCII subset
 * that PHP identifiers can legally use; identifier-shape inputs ("Posts",
 * "PostCategory", "post_title") round-trip losslessly.
 */
final class Helper
{
    private function __construct()
    {
    }

    /**
     * Convert a camelCase or PascalCase identifier to snake_case.
     *
     * Boundary rules:
     *
     *   - "Posts"             → "posts"
     *   - "PostCategory"      → "post_category"
     *   - "PostCategoryTag"   → "post_category_tag"
     *   - "XMLParser"         → "xml_parser"  (consecutive uppercase + lower)
     *   - "HTTPRequest"       → "http_request"
     *
     * Already-lowercase or already-snake_case input is preserved.
     */
    public static function camelCaseToSnakeCase(string $string): string
    {
        $string = (string) preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $string);
        $string = (string) preg_replace('/([a-z\d])([A-Z])/', '$1_$2', $string);

        return strtolower($string);
    }

    /**
     * Convert a snake_case identifier to PascalCase.
     *
     *   - "posts"          → "Posts"
     *   - "post_title"     → "PostTitle"
     *   - "post_category"  → "PostCategory"
     *
     * Empty segments produced by consecutive or leading underscores collapse.
     */
    public static function snakeCaseToPascalCase(string $string): string
    {
        return str_replace('_', '', ucwords(strtolower($string), '_'));
    }
}
