# 05 — Soft deletes

When enabled, soft deletes turn `delete()` into a "mark this row as deleted" operation and automatically filter soft-deleted rows out of subsequent reads / updates.

---

## Setup

```php
class Posts extends \InitORM\ORM\Model
{
    protected string $schema   = 'posts';

    protected bool    $useSoftDeletes = true;
    protected ?string $deletedField   = 'deleted_at';   // required
}
```

The constructor enforces the invariant — `$useSoftDeletes = true` without a `$deletedField` raises `ModelException` before the model is usable.

The `deletedField` column must be **nullable** in the underlying schema (it stores either a timestamp or `NULL`).

---

## Soft-deleting a row

```php
$posts->delete(['id' => 5]);
```

This compiles to (roughly):

```sql
UPDATE posts SET deleted_at = :deleted_at WHERE deleted_at IS NULL AND id = :id
```

— note the auto-injected `deleted_at IS NULL` predicate, which prevents an already-deleted row from being "deleted" again.

To remove the row for real:

```php
$posts->delete(['id' => 5], purge: true);
```

The `purge: true` flag bypasses soft-delete and issues a real `DELETE` statement.

---

## Reading

`read()` automatically excludes soft-deleted rows:

```php
foreach ($posts->read()->rows() as $entity) {
    // Only rows where deleted_at IS NULL.
}
```

To read **only** soft-deleted rows on the next read, use `onlyDeleted()`:

```php
foreach ($posts->onlyDeleted()->read()->rows() as $tombstone) {
    // Only rows where deleted_at IS NOT NULL.
}
```

The `onlyDeleted` flag is consumed by the next `read()` — subsequent reads revert to the default scope:

```php
$posts->onlyDeleted()->read()->rows();  // soft-deleted only
$posts->read()->rows();                  // default scope
```

---

## Updates and `ignoreDeleted()`

`update()` and `updateBatch()` automatically add `deleted_at IS NULL` to their WHERE chain so they never touch soft-deleted rows. If you build a custom chain (e.g. via direct `where()` calls) and want the same protection without calling `update()`, use `ignoreDeleted()`:

```php
$posts
    ->ignoreDeleted()
    ->where('author_id', '=', 7)
    ->update(['archived' => 1]);
```

`ignoreDeleted()` adds `deletedField IS NULL` to the pending WHERE and returns the model (chainable). `onlyDeleted()`, by contrast, only flips an internal flag — the WHERE is added by the next `read()`.

---

## Restoring a soft-deleted row

There is no built-in `restore()` helper — soft-delete is a one-bit, one-column convention, so restoration is a plain update with `purge`-style intent:

```php
// Bypass the auto-injected "deleted_at IS NULL" by setting deleted_at explicitly.
$posts->getDatabase()->update('posts', ['deleted_at' => null], ['id' => 5]);
```

Or, if you prefer to stay on the model API, expose a thin helper on your subclass:

```php
class Posts extends \InitORM\ORM\Model
{
    public function restore(int $id): bool
    {
        return $this->db->update($this->getSchema(), ['deleted_at' => null], ['id' => $id]);
    }
}
```
