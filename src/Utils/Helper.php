<?php
/**
 * InitORM ORM
 *
 * This file is part of InitORM ORM.
 *
 * @author      Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright   Copyright © 2023 Muhammet ŞAFAK
 * @license     ./LICENSE  MIT
 * @version     1.0
 * @link        https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);
namespace InitORM\ORM\Utils;

final class Helper
{

    /**
     * @param string $string
     * @return string
     */
    public static function camelCaseToSnakeCase(string $string): string
    {
        $string = lcfirst($string);

        return preg_replace_callback('/[A-Z]/', function ($match) {
            return '_' . strtolower($match[0]);
        }, $string);
    }

    /**
     * @param string $string
     * @return string
     */
    public static function snakeCaseToPascalCase(string $string): string
    {
        $split = explode('_', strtolower($string));
        $camelCase = '';
        foreach ($split as $row) {
            $camelCase .= ucfirst($row);
        }

        return $camelCase;
    }

}
