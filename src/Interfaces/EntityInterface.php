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

namespace InitORM\ORM\Interfaces;

interface EntityInterface
{

    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @return array
     */
    public function getAttributes(): array;

}
