<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM;

use InitORM\ORM\Exceptions\EntityException;
use InitORM\ORM\Interfaces\EntityInterface;
use InitORM\ORM\Utils\Helper;

/**
 * Base entity class — a typed row container with optional per-column
 * accessor and mutator hooks (Laravel-style).
 *
 * Each row of data lives in {@see self::$attributes}. Reading or writing a
 * column with an "{@code $entity->name}" property syntax dispatches through
 * {@see self::__get()} / {@see self::__set()}:
 *
 *   - When a subclass declares `getColumnAttribute($value)` or
 *     `setColumnAttribute($value)`, that method is invoked with the current
 *     stored value (accessor) or the incoming value (mutator).
 *   - Otherwise the value is read from / written to the internal attribute
 *     bag directly.
 *
 * Mutator bodies MUST write the transformed value back through
 * {@see self::setAttribute()} — NOT through `$this->column = ...`. Inside
 * a class method, undeclared property assignment bypasses {@see __set()}
 * and creates a dynamic property instead (deprecated in PHP 8.2+, fatal in
 * a future PHP version), so the value never reaches the attribute bag and
 * later reads see a stale value.
 */
class Entity implements EntityInterface
{
    /**
     * Column → value bag for this row.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Snapshot of {@see self::$attributes} taken at construction time (and
     * any subsequent {@see self::syncOriginal()} call). Subclasses can use
     * this to implement dirty-tracking.
     *
     * @var array<string, mixed>
     */
    protected array $attributesOriginal = [];

    /**
     * @param array<string, mixed>|null $data Initial column values, applied
     *        through mutators when present. Pass null for an empty entity.
     */
    public function __construct(?array $data = [])
    {
        $this->fill($data)
            ->syncOriginal();
    }

    /**
     * Default get/set fallback for the `{verb}{Column}Attribute` family —
     * invoked only when a subclass does not define a real method by that
     * name. Direct {@code $entity->col} property access does not route
     * through this method.
     *
     * @param array<int, mixed> $arguments
     *
     * @throws EntityException When the method name does not match the
     *         `getXAttribute` / `setXAttribute` pattern.
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!str_ends_with($name, 'Attribute')) {
            throw new EntityException(sprintf('Unknown entity method "%s".', $name));
        }

        $prefix = substr($name, 0, 3);
        $column = Helper::camelCaseToSnakeCase(substr($name, 3, -9));

        return match ($prefix) {
            'get'   => $this->attributes[$column] ?? null,
            'set'   => $this->attributes[$column] = $arguments[0] ?? null,
            default => throw new EntityException(sprintf('Unknown entity method "%s".', $name)),
        };
    }

    /**
     * Property write. When a `set{Column}Attribute($value)` method exists on
     * the subclass, it is invoked with $value; the method body is expected
     * to write the transformed value back via {@see self::setAttribute()}.
     */
    public function __set(string $name, mixed $value): void
    {
        $method = 'set' . Helper::snakeCaseToPascalCase($name) . 'Attribute';

        if (method_exists($this, $method)) {
            $this->{$method}($value);
            return;
        }

        $this->attributes[$name] = $value;
    }

    /**
     * Property read. When a `get{Column}Attribute($value)` method exists on
     * the subclass, it is invoked with the stored value (or null) and its
     * return value is propagated to the caller.
     */
    public function __get(string $name): mixed
    {
        $method = 'get' . Helper::snakeCaseToPascalCase($name) . 'Attribute';

        if (method_exists($this, $method)) {
            return $this->{$method}($this->attributes[$name] ?? null);
        }

        return $this->attributes[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getOriginal(): array
    {
        return $this->attributesOriginal;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setAttribute(string $name, mixed $value): static
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function syncOriginal(): static
    {
        $this->attributesOriginal = $this->attributes;

        return $this;
    }

    /**
     * Populate the entity from an associative array, dispatching through
     * mutators where they exist. Null is treated as a no-op.
     *
     * @param array<string, mixed>|null $data
     */
    protected function fill(?array $data = null): static
    {
        if ($data === null) {
            return $this;
        }

        foreach ($data as $key => $value) {
            $this->__set((string) $key, $value);
        }

        return $this;
    }
}
