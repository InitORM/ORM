# 06 — Timestamps

Each model can auto-fill up to three timestamp columns: a creation timestamp on insert, an update timestamp on every update, and a deletion timestamp on soft-delete.

---

## Properties

| Property            | Type           | Default            | Effect                                                          |
| ------------------- | -------------- | ------------------ | --------------------------------------------------------------- |
| `$createdField`     | `string\|null` | `null`             | Filled on every `create()` / `createBatch()`.                   |
| `$updatedField`     | `string\|null` | `null`             | Filled on every `update()` / `updateBatch()`.                   |
| `$deletedField`     | `string\|null` | `null`             | Filled on `delete()` when `$useSoftDeletes` is true.            |
| `$timestampFormat`  | `string`       | `'Y-m-d H:i:s'`    | `date()` format string used for all three.                      |

Each column is independent — enable any subset that fits your schema. Leaving a property as `null` disables the corresponding auto-fill entirely.

---

## Examples

### Created + updated

```php
class Posts extends \InitORM\ORM\Model
{
    protected string $schema = 'posts';

    protected ?string $createdField = 'created_at';
    protected ?string $updatedField = 'updated_at';
}

$posts = new Posts();
$posts->create(['title' => 'Hello']);
// INSERT INTO posts (title, created_at) VALUES (:title, :created_at)

$posts->update(['id' => 1, 'title' => 'Edited']);
// UPDATE posts SET title = :title, updated_at = :updated_at WHERE id = :id
```

### Custom format

```php
class Posts extends \InitORM\ORM\Model
{
    protected ?string $createdField    = 'created_at';
    protected string  $timestampFormat = 'Y-m-d\TH:i:sP';   // ISO-8601 with timezone
}
```

`date()` formats are documented at <https://www.php.net/manual/en/datetime.format.php>. The format is shared across all three columns.

### Soft delete

```php
class Posts extends \InitORM\ORM\Model
{
    protected string $schema = 'posts';

    protected bool    $useSoftDeletes = true;
    protected ?string $deletedField   = 'deleted_at';
}

$posts->delete(['id' => 5]);
// UPDATE posts SET deleted_at = :deleted_at WHERE deleted_at IS NULL AND id = :id
```

See [05 — Soft deletes](05-soft-deletes.md).

---

## Overriding the value

Auto-fill only kicks in when the column is absent from `$set`. To pass an explicit value, include it yourself:

```php
$posts->create([
    'title'      => 'Backfilled',
    'created_at' => '2020-01-01 00:00:00',
]);
```

The model writes auto-filled timestamps **after** copying the caller-supplied `$set`, so a value the caller wrote into `$set[$createdField]` will be overwritten. To preserve a custom value, either disable auto-fill for that model or use the underlying `Database` API directly:

```php
$posts->getDatabase()->create($posts->getSchema(), [
    'title'      => 'Backfilled',
    'created_at' => '2020-01-01 00:00:00',
]);
```

---

## Timezone

`date()` uses the runtime's default timezone (`date_default_timezone_get()` / `date.timezone` INI). For consistent results, set this once at boot:

```php
date_default_timezone_set('UTC');
```

If you store timestamps in a typed column (`TIMESTAMP` on MySQL, `timestamp with time zone` on PostgreSQL), the database driver may apply its own conversion on read — verify the round trip in tests.
