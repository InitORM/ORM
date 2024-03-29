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
namespace InitORM\ORM;

use InitORM\ORM\Exceptions\EntityException;
use InitORM\ORM\Interfaces\EntityInterface;
use InitORM\ORM\Utils\Helper;

class Entity implements EntityInterface
{

    protected array $__attributes = [];

    protected array $__attributesOriginal = [];

    public function __construct(?array $data = [])
    {
        $this->setUp($data);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws EntityException
     */
    public function __call($name, $arguments)
    {
        if (str_ends_with($name, 'Attribute') === false) {
            throw new EntityException($name);
        }

        $attr = Helper::camelCaseToSnakeCase(substr($name, 3, -9));

        return match (substr($name, 0, 3)) {
            'get'       => $this->__attributes[$attr] ?? null,
            'set'       => $this->__attributes[$attr] = $arguments[0],
            default     => throw new EntityException($name)
        };
    }

    public function __set($name, $value)
    {
        $methodName = 'set' . Helper::snakeCaseToPascalCase($name) . 'Attribute';
        if(method_exists($this, $methodName)){
            $this->{$methodName}($value);
            return $value;
        }
        return $this->__attributes[$name] = $value;
    }

    public function __get($name)
    {
        $methodName = 'get' . Helper::snakeCaseToPascalCase($name) . 'Attribute';
        if(method_exists($this, $methodName)){
            return $this->{$methodName}();
        }
        return $this->__attributes[$name] ?? null;
    }

    public function __isset($name)
    {
        return isset($this->__attributes[$name]);
    }

    public function __unset($name)
    {
        if(isset($this->__attributes[$name])){
            unset($this->__attributes[$name]);
        }
    }

    public function __debugInfo()
    {
        return $this->__attributes;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->__attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->toArray();
    }

    /**
     * @param array|null $data
     * @return $this
     */
    protected function setUp(?array $data = null): self
    {
        $this->syncOriginal()
            ->fill($data);
        return $this;
    }

    /**
     * @param array|null $data
     * @return $this
     */
    protected function fill(?array $data = null): self
    {
        if($data !== null){
            foreach ($data as $key => $value) {
                $this->__set($key, $value);
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function syncOriginal(): self
    {
        $this->__attributesOriginal = $this->__attributes;
        return $this;
    }

}
