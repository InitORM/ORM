<?php

/**
 * @package InitORM\ORM
 * @license MIT
 */

declare(strict_types=1);

namespace InitORM\ORM\Interfaces;

/**
 * Public contract for an entity — the row container produced by
 * {@see \InitORM\ORM\Model::read()}. The reference implementation lives in
 * {@see \InitORM\ORM\Entity}.
 *
 * The magic property accessors `__get`/`__set`/`__isset`/`__unset` are not
 * part of this interface (PHP does not let interfaces declare magic methods),
 * but every implementation is expected to honour the
 * `get{Column}Attribute` / `set{Column}Attribute` accessor/mutator convention
 * described on {@see \InitORM\ORM\Entity}.
 */
interface EntityInterface
{
    /**
     * The entity's column → value map, including any values produced by
     * mutator hooks. Equivalent to {@see self::getAttributes()} and kept
     * here as the conventional "serialize me" entry point.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Live column → value map. Returned by reference to the entity's own
     * storage — callers must not assume the array is a snapshot.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * Snapshot of attributes captured at construction time (or at the last
     * {@see self::syncOriginal()} call). Used as the baseline for any
     * dirty-tracking a consumer wants to layer on top.
     *
     * @return array<string, mixed>
     */
    public function getOriginal(): array;

    /**
     * Read a single attribute without going through the accessor hook. Use
     * this from within an accessor method to avoid re-entry.
     */
    public function getAttribute(string $name): mixed;

    /**
     * Write a single attribute without going through the mutator hook. Use
     * this from within a mutator method to write the transformed value back
     * into the attribute bag without triggering recursion.
     */
    public function setAttribute(string $name, mixed $value): static;

    /**
     * Capture the current attribute set as the new "original" baseline.
     * Useful after a save() in subclasses that implement dirty-tracking.
     */
    public function syncOriginal(): static;
}
