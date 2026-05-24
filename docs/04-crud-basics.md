# 04 — CRUD basics

Every model exposes six CRUD methods. They all return `bool true` on successful execution and throw on failure — read [`affectedRows()`](#how-many-rows-changed) when you also need the row count.

| Method            | Job                                                      |
| ----------------- | -------------------------------------------------------- |
| `create($set)`    | Insert one row.                                          |
| `createBatch($set)` | Insert many rows in a single statement.                |
| `read($selectors, $conditions)` | SELECT, hydrated as `$entity` instances.   |
| `update($set, $conditions)` | UPDATE rows.                                   |
| `updateBatch($set, $referenceColumn)` | CASE/WHEN-keyed batch UPDATE.        |
| `delete($conditions, $purge)` | DELETE (or soft-delete) rows.                |

The two batch variants iterate the outer array and run the same builder once.

---

## `create`

```php
$posts = new \App\Model\Posts();

$posts->create([
    'title' => 'My First Post',
    'body'  => 'Hello, world.',
]);

$newId = $posts->getDatabase()->insertId();
```

When the model declares `$createdField`, that column is auto-filled with `date($timestampFormat)` just before execution.

---

## `createBatch`

```php
$posts->createBatch([
    ['title' => 'A', 'body' => 'first body'],
    ['title' => 'B', 'body' => 'second body'],
    ['title' => 'C'],   // body compiles to NULL
]);
```

`$createdField` is applied to every row.

---

## `read`

```php
foreach ($posts->read()->rows() as $entity) {
    echo $entity->title, PHP_EOL;
}
```

Optional projection and inline conditions:

```php
$rows = $posts
    ->read(
        ['id', 'title'],          // SELECT id, title
        ['status' => 'published'] // WHERE status = :status
    )
    ->rows();
```

The result is a `DataMapperInterface` configured to hydrate `$entity` instances. Call `->asAssoc()` or `->asObject()` if you want a different fetch mode.

Compose with the builder for anything more complex:

```php
$rows = $posts
    ->select('id', 'title')
    ->where('status', '=', 'published')
    ->orderBy('id', 'DESC')
    ->limit(10)
    ->read()
    ->rows();
```

See [08 — Extending the builder](08-extending-the-builder.md) for the full forwarded surface.

---

## `update`

```php
// By primary key — the PK is lifted out of $set into a WHERE clause:
$posts->update(['id' => 5, 'title' => 'Renamed']);

// With explicit conditions:
$posts->update(['title' => 'Renamed'], ['id' => 5]);

// Without conditions — affects ALL rows that match any pending WHERE chain:
$posts->where('author_id', '=', 7)->update(['archived' => 1]);
```

When `$updatedField` is set, it is auto-filled with the current timestamp.

When `$useSoftDeletes` is on, an additional `deletedField IS NULL` is added so soft-deleted rows are never touched.

---

## `updateBatch`

```php
$posts->updateBatch(
    [
        ['id' => 1, 'title' => 'Edited #1'],
        ['id' => 2, 'title' => 'Edited #2'],
    ],
    referenceColumn: 'id', // defaults to $schemaId
);
```

Generates a single CASE/WHEN UPDATE keyed by `$referenceColumn`.

---

## `delete`

```php
// Conditional delete:
$posts->delete(['id' => 5]);

// All rows that match a pre-existing WHERE chain:
$posts->where('status', '=', 'spam')->delete();
```

With `$useSoftDeletes` on, `delete()` sets `$deletedField` instead. To bypass soft-delete and actually remove the row:

```php
$posts->delete(['id' => 5], purge: true);
```

---

## `save(Entity)`

`save()` picks between insert and update based on whether the entity carries a non-empty primary-key value:

```php
$entity = new \App\Entity\PostEntity(['title' => 'New']);
$posts->save($entity);   // create()

$entity = new \App\Entity\PostEntity(['id' => 1, 'title' => 'Edit']);
$posts->save($entity);   // update()
```

---

## How many rows changed?

The Database tracks the last CRUD call's affected row count:

```php
$posts->update(['status' => 'archived'], ['author_id' => 7]);
$posts->getDatabase()->affectedRows();   // e.g. 12
```

For SELECT this is driver-dependent: reliable on buffered drivers (MySQL); unreliable elsewhere. For INSERT/UPDATE/DELETE on the common drivers it returns the genuine affected-row count.
