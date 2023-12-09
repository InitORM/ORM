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
        $split = preg_split('', $string, -1, PREG_SPLIT_NO_EMPTY);
        $snake_case = '';
        $i = 0;
        foreach ($split as $row) {
            $snake_case .= ($i === 0 ? '_' : '')
                . strtolower($row);
            ++$i;
        }

        return lcfirst($snake_case);
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
