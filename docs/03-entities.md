# 03 — Entities

An entity is a typed row container. The reference implementation is [`InitORM\ORM\Entity`](../src/Entity.php) — values live in an internal `$attributes` array, with optional per-column accessor / mutator hooks for transforming values on read / write.

---

## The attribute bag

```php
$entity = new \InitORM\ORM\Entity(['title' => 'Hello', 'body' => 'World']);

$entity->toArray();        // ['title' => 'Hello', 'body' => 'World']
$entity->getAttributes();  // same
$entity->title;            // 'Hello'
$entity->title = 'Updated';
$entity->title;            // 'Updated'
isset($entity->title);     // true
unset($entity->title);
isset($entity->title);     // false
```

Reads and writes go through `__get` / `__set`, which check for an accessor / mutator method first and fall back to the attribute bag.

---

## Accessors

An accessor is a method named `get{Column}Attribute(mixed $value)`. The column name is the PascalCase form of the snake_case column. `$value` is the current stored value (or `null` if the attribute is absent).

```php
class PostEntity extends \InitORM\ORM\Entity
{
    public function getTitleAttribute(mixed $value): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}

$entity = new PostEntity(['title' => 'hello']);
$entity->title;                       // 'HELLO'   — transformed by the accessor
$entity->getAttribute('title');       // 'hello'   — bypasses the accessor
```

---

## Mutators

A mutator is a method named `set{Column}Attribute(mixed $value)`. It is invoked with the incoming value when the column is written.

**The mutator body must write back via `setAttribute()`** — `$this->title = …` from inside a class method bypasses `__set` and creates a dynamic property, so the value never reaches the attribute bag (and PHP 8.2+ raises a deprecation; a future PHP version will make it fatal).

```php
class PostEntity extends \InitORM\ORM\Entity
{
    public function setTitleAttribute(mixed $value): void
    {
        $this->setAttribute('title', is_string($value) ? trim($value) : $value);
    }
}

$entity = new PostEntity();
$entity->title = '  hello  ';
$entity->getAttribute('title'); // 'hello' — mutator stripped the whitespace
```

A mutator + accessor pair is common — the mutator normalises on write, the accessor presents on read:

```php
class UserEntity extends \InitORM\ORM\Entity
{
    public function setEmailAttribute(mixed $value): void
    {
        $this->setAttribute('email', is_string($value) ? strtolower(trim($value)) : $value);
    }

    public function getEmailAttribute(mixed $value): mixed
    {
        // Stored lower-cased; presented as-is to the caller.
        return $value;
    }
}
```

---

## `setAttribute` / `getAttribute`

The helper methods bypass the magic hooks entirely. Use them:

- Inside a mutator body, to write back without re-entering the mutator.
- Inside an accessor body, to read a peer column without re-entering its accessor.
- In tests, to assert what was actually stored vs. what the accessor presents.

```php
$entity->setAttribute('email', 'me@example.com');
$entity->getAttribute('email'); // 'me@example.com'
```

---

## Dirty tracking baseline

Each entity captures the construction-time attribute bag as the **original** snapshot. Mutations after construction do not change it.

```php
$entity = new \InitORM\ORM\Entity(['title' => 'Hello']);
$entity->title = 'Edited';

$entity->getOriginal();    // ['title' => 'Hello']
$entity->getAttributes();  // ['title' => 'Edited']
```

Call `syncOriginal()` to overwrite the snapshot with the current values — for example, after persisting via `Model::save()`:

```php
$model = new \App\Model\Posts();
$model->save($entity);
$entity->syncOriginal();   // entity is "clean" again
```

Diffing the two arrays gives a simple is-dirty / changed-columns check — the package intentionally does not bake this into the interface so consumers can pick their own semantics.

---

## Bypassing the magic completely

`__call` on the base `Entity` provides a default implementation of `get{Column}Attribute()` / `set{Column}Attribute()` when the subclass has not declared one:

```php
$entity = new \InitORM\ORM\Entity();
$entity->setPostTitleAttribute('Hello');   // routes to $attributes['post_title']
$entity->getPostTitleAttribute();          // returns 'Hello'
```

This is what makes the magic property accessors round-trip with no boilerplate.
