# 02 — Defining models

A model is a subclass of [`InitORM\ORM\Model`](../src/Model.php). Each protected property below customises one aspect of its behaviour; every one is optional.

---

## Properties

| Property             | Type                            | Default        |
| -------------------- | ------------------------------- | -------------- |
| `$schema`            | `string`                        | _(derived)_    |
| `$schemaId`          | `string`                        | `'id'`         |
| `$entity`            | `class-string`                  | `Entity::class`|
| `$credentials`       | `array\|null`                   | `null`         |
| `$writable`          | `bool`                          | `true`         |
| `$readable`          | `bool`                          | `true`         |
| `$updatable`         | `bool`                          | `true`         |
| `$deletable`         | `bool`                          | `true`         |
| `$createdField`      | `string\|null`                  | `null`         |
| `$updatedField`      | `string\|null`                  | `null`         |
| `$useSoftDeletes`    | `bool`                          | `false`        |
| `$deletedField`      | `string\|null`                  | `null`         |
| `$timestampFormat`   | `string`                        | `'Y-m-d H:i:s'`|

### `$schema`

The backing table name. When the property is **not declared** (or left unset), the constructor derives it from the subclass short name via `Helper::camelCaseToSnakeCase()`:

```php
class PostCategory extends \InitORM\ORM\Model {}

(new PostCategory())->getSchema(); // 'post_category'
```

Conversion rules:

| Class short name       | Derived schema           |
| ---------------------- | ------------------------ |
| `Posts`                | `posts`                  |
| `PostCategory`         | `post_category`          |
| `PostCategoryTag`      | `post_category_tag`      |
| `XMLParser`            | `xml_parser`             |
| `HTTPRequest`          | `http_request`           |

For anything more exotic, set `$schema` explicitly.

### `$schemaId`

Primary-key column. Used in two places:

- `update()` lifts the PK out of the `$set` array into a WHERE clause, so it is never overwritten.
- `save(Entity)` reads it to decide whether to insert or update.

### `$entity`

Class used by `read()` to hydrate rows. Defaults to the bare `Entity` class. Any class with a no-arg-compatible constructor works (PDO's `FETCH_CLASS` is used under the hood), but the conventional choice is a subclass of `Entity`.

### `$credentials`

Standalone connection credentials passed straight to `DB::connect()`. When `null`, the model binds to the shared `DB::getDatabase()` facade. See [09 — Multiple connections](09-multiple-connections.md).

### Permission gates

`$writable`, `$readable`, `$updatable`, `$deletable` — set any of these to `false` and the matching operation throws a typed exception. See [07 — Permission gates](07-permission-gates.md).

### Timestamps

`$createdField`, `$updatedField`, `$deletedField`, `$timestampFormat` — see [06 — Timestamps](06-timestamps.md).

### Soft deletes

`$useSoftDeletes`, `$deletedField` — see [05 — Soft deletes](05-soft-deletes.md).

---

## Construction

The constructor:

1. Auto-derives `$schema` if it was not set.
2. Validates the soft-delete invariant — `$useSoftDeletes = true` without a `$deletedField` raises `ModelException`.
3. Acquires a `DatabaseInterface` (the `DB` facade or a fresh standalone connection via `$credentials`).

Subclasses overriding `__construct` should call `parent::__construct()` after they have set any required properties:

```php
class Posts extends \InitORM\ORM\Model
{
    public function __construct(string $schema = 'posts')
    {
        $this->schema = $schema;
        parent::__construct();
    }
}
```

That said — overriding the constructor is rare. Configuring the model through declared properties is the conventional path because it composes cleanly with PSR-4 autoloading.
