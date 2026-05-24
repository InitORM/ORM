# InitORM ORM

A lightweight, PDO-based ORM. Each subclass of [`Model`](src/Model.php) maps a table to an [`Entity`](src/Entity.php) class and exposes Active-Record-style CRUD on top of [`initorm/database`](https://github.com/InitORM/Database). Entities support per-column accessor and mutator hooks (Laravel-style), and models ship with optional timestamp columns, soft deletes, and per-operation permission gates.

[![Latest Stable Version](https://poser.pugx.org/initorm/orm/v)](https://packagist.org/packages/initorm/orm)
[![Total Downloads](https://poser.pugx.org/initorm/orm/downloads)](https://packagist.org/packages/initorm/orm)
[![License](https://poser.pugx.org/initorm/orm/license)](https://packagist.org/packages/initorm/orm)
[![PHP Version Require](https://poser.pugx.org/initorm/orm/require/php)](https://packagist.org/packages/initorm/orm)
[![PHPUnit](https://github.com/InitORM/ORM/actions/workflows/phpunit.yml/badge.svg)](https://github.com/InitORM/ORM/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/InitORM/ORM/actions/workflows/phpstan.yml/badge.svg)](https://github.com/InitORM/ORM/actions/workflows/phpstan.yml)
[![PHP_CodeSniffer](https://github.com/InitORM/ORM/actions/workflows/phpcs.yml/badge.svg)](https://github.com/InitORM/ORM/actions/workflows/phpcs.yml)

---

## Requirements

- **PHP 8.1 or later**
- `ext-pdo`
- One of `ext-pdo_mysql`, `ext-pdo_pgsql`, or `ext-pdo_sqlite` depending on the database you target.

## Supported databases

The full query-builder dialect support comes through `initorm/database`: **MySQL/MariaDB**, **PostgreSQL**, and **SQLite** ship with dialect-aware identifier quoting; any other PDO driver works through a no-escape generic driver.

## Installation

```bash
composer require initorm/orm
```

`initorm/orm` pulls in `initorm/database`, `initorm/dbal`, and `initorm/query-builder` transitively — you do not need to require them yourself.

---

## Quick start

```php
<?php
require_once 'vendor/autoload.php';

use InitORM\Database\Facade\DB;

DB::createImmutable([
    'dsn'      => 'mysql:host=localhost;dbname=app;charset=utf8mb4',
    'username' => 'app',
    'password' => 'secret',
]);
```

Define a model:

```php
namespace App\Model;

class Posts extends \InitORM\ORM\Model
{
    protected string $schema   = 'posts';
    protected string $schemaId = 'id';

    protected bool    $useSoftDeletes = true;
    protected ?string $createdField   = 'created_at';
    protected ?string $updatedField   = 'updated_at';
    protected ?string $deletedField   = 'deleted_at';

    protected string $entity = \App\Entity\PostEntity::class;
}
```

Define an entity:

```php
namespace App\Entity;

class PostEntity extends \InitORM\ORM\Entity
{
    public function getTitleAttribute(mixed $value): mixed
    {
        return is_string($value) ? ucwords($value) : $value;
    }

    public function setTitleAttribute(mixed $value): void
    {
        // ALWAYS use setAttribute() inside a mutator —
        // $this->title = ... bypasses __set and creates a dynamic property.
        $this->setAttribute('title', is_string($value) ? trim($value) : $value);
    }
}
```

Use the model:

```php
use App\Model\Posts;

$posts = new Posts();

// Create
$posts->create(['title' => 'My First Post', 'body' => 'Hello world']);

// Read (returns a DataMapper hydrating PostEntity instances)
foreach ($posts->read()->rows() as $entity) {
    echo $entity->title; // accessor runs here
}

// Update by primary key (lifted out of $set into a WHERE)
$posts->update(['id' => 5, 'title' => 'Edited']);

// Soft delete (sets deleted_at), then permanently purge
$posts->delete(['id' => 5]);
$posts->delete(['id' => 5], purge: true);
```

---

## How it fits together

```
QueryBuilder  ──►  Database  ──►  ORM (this package)
DBAL          ──►  Database
```

A `Model` holds a [`DatabaseInterface`](https://github.com/InitORM/Database/blob/master/src/Interfaces/DatabaseInterface.php) and forwards unknown calls to it via `__call`. The Database, in turn, forwards builder calls (`where`, `select`, `orderBy`, …) to the underlying query builder. Chainable calls re-wrap at every boundary, so this all stays fluent on the Model:

```php
$entities = $posts
    ->where('status', '=', 'published')
    ->orderBy('id', 'DESC')
    ->limit(10)
    ->read()
    ->rows();
```

The full query-builder surface (~100 methods including joins, group/having, sub-queries, LIKE family, BETWEEN, IN, raw expressions) is documented in [`initorm/query-builder`](https://github.com/InitORM/QueryBuilder) and [`initorm/database`](https://github.com/InitORM/Database).

---

## Configuration reference

These protected properties shape a model's behaviour. All are optional with sensible defaults.

| Property             | Type                            | Default        | Notes                                                                                              |
| -------------------- | ------------------------------- | -------------- | -------------------------------------------------------------------------------------------------- |
| `$schema`            | `string`                        | _(derived)_    | Table name. When unset, auto-derived from the short class name via snake_case conversion.          |
| `$schemaId`          | `string`                        | `'id'`         | Primary-key column. Lifted out of `update()`'s `$set` into a WHERE; used by `save()` to pick CRUD. |
| `$entity`            | `class-string`                  | `Entity::class`| Class used to hydrate `read()` rows.                                                               |
| `$credentials`       | `array\|null`                   | `null`         | Standalone connection credentials; null binds to the shared `DB` facade.                           |
| `$writable`          | `bool`                          | `true`         | When false, `create()`/`createBatch()` throw `WritableException`.                                  |
| `$readable`          | `bool`                          | `true`         | When false, `read()` throws `ReadableException`.                                                   |
| `$updatable`         | `bool`                          | `true`         | When false, `update()`/`updateBatch()` throw `UpdatableException`.                                 |
| `$deletable`         | `bool`                          | `true`         | When false, `delete()` throws `DeletableException`.                                                |
| `$createdField`      | `string\|null`                  | `null`         | Auto-filled with the current timestamp on every create. Disabled when null.                        |
| `$updatedField`      | `string\|null`                  | `null`         | Auto-filled with the current timestamp on every update. Disabled when null.                        |
| `$useSoftDeletes`    | `bool`                          | `false`        | When true, `delete()` sets `$deletedField` instead of issuing a DELETE. Requires `$deletedField`.  |
| `$deletedField`      | `string\|null`                  | `null`         | Soft-delete marker column. Must be set when `$useSoftDeletes` is true (enforced at construction).  |
| `$timestampFormat`   | `string`                        | `'Y-m-d H:i:s'`| `date()` format used for created / updated / deleted columns.                                      |

---

## Soft deletes

When `$useSoftDeletes = true`:

- `read()` automatically filters to rows where `$deletedField IS NULL`.
- `delete()` sets `$deletedField` to the current timestamp instead of issuing a DELETE.
- Pass `purge: true` to bypass soft-delete and remove the row for real:
  ```php
  $posts->delete(['id' => 5], purge: true);
  ```
- Use `onlyDeleted()` to read soft-deleted rows on the next `read()`:
  ```php
  foreach ($posts->onlyDeleted()->read()->rows() as $deleted) {
      // …
  }
  ```
  The flag is consumed by the next read and reverts afterwards.

`update()` and `updateBatch()` automatically add `$deletedField IS NULL` to avoid resurrecting soft-deleted rows.

---

## Entities, accessors, and mutators

`Entity` stores values in an internal attribute bag (`$attributes`) and exposes them through the magic `__get` / `__set` accessors. For column `post_title`, you can define:

```php
class PostEntity extends \InitORM\ORM\Entity
{
    public function getPostTitleAttribute(mixed $value): mixed
    {
        return ucwords((string) $value);
    }

    public function setPostTitleAttribute(mixed $value): void
    {
        $this->setAttribute('post_title', strtolower((string) $value));
    }
}
```

- **Accessor** receives the stored attribute value as its single argument.
- **Mutator** must write back via `$this->setAttribute('post_title', …)`. Plain `$this->post_title = …` from inside a class method bypasses `__set` and creates a dynamic property (deprecated in PHP 8.2+, fatal in a future PHP version), so the value would never reach the attribute bag.
- `toArray()` / `getAttributes()` return the raw attribute bag.
- `getOriginal()` returns a snapshot captured at construction time; call `syncOriginal()` to refresh it after a save.

---

## Permission gates

Each operation is gated by a flag — flip any to `false` and the matching typed exception fires:

```php
class ReadOnlyConfig extends \InitORM\ORM\Model
{
    protected string $schema    = 'configuration';
    protected bool   $writable  = false;
    protected bool   $updatable = false;
    protected bool   $deletable = false;
}

(new ReadOnlyConfig())->create([...]); // throws WritableException
```

All four — `WritableException`, `ReadableException`, `UpdatableException`, `DeletableException` — extend `ModelException`, so a single catch handles all of them.

---

## Multiple connections

Models bind to the shared `DB` facade by default. Set `$credentials` on a subclass to give it its own connection:

```php
class ReportsModel extends \InitORM\ORM\Model
{
    protected string $schema = 'events';

    protected ?array $credentials = [
        'dsn'      => 'pgsql:host=reports.internal;dbname=reports',
        'username' => 'reports_ro',
        'password' => '…',
        'driver'   => 'pgsql',
    ];
}
```

`$credentials` is passed through to [`InitORM\Database\Facade\DB::connect()`](https://github.com/InitORM/Database/blob/master/src/Facade/DB.php), which builds a fresh `Database` (and underlying connection) without touching the shared facade slot.

---

## Testing

The library ships with a comprehensive PHPUnit 10 suite that exercises the model against an in-memory SQLite database. Patterns for testing your own models live in [`tests/Support/AbstractModelTestCase.php`](tests/Support/AbstractModelTestCase.php).

Run the full quality suite locally:

```bash
composer qa   # phpcs + phpstan + phpunit
```

Individual targets:

```bash
composer test         # phpunit
composer cs           # phpcs
composer cs-fix       # phpcbf
composer stan         # phpstan analyse
```

---

## Documentation

Deeper, code-first guides live under [`docs/`](docs/):

- [`01-getting-started.md`](docs/01-getting-started.md) — install, bootstrap, the layered architecture.
- [`02-defining-models.md`](docs/02-defining-models.md) — every property, plus auto-schema derivation.
- [`03-entities.md`](docs/03-entities.md) — accessors, mutators, attribute bag, the `setAttribute` rule.
- [`04-crud-basics.md`](docs/04-crud-basics.md) — `create`, `read`, `update`, `delete`, batch variants.
- [`05-soft-deletes.md`](docs/05-soft-deletes.md) — `useSoftDeletes`, `onlyDeleted`, `ignoreDeleted`, `purge`.
- [`06-timestamps.md`](docs/06-timestamps.md) — auto-filled `created_at` / `updated_at` / `deleted_at`.
- [`07-permission-gates.md`](docs/07-permission-gates.md) — `$writable` / `$readable` / `$updatable` / `$deletable`.
- [`08-extending-the-builder.md`](docs/08-extending-the-builder.md) — accessing the full query-builder API through the Model.
- [`09-multiple-connections.md`](docs/09-multiple-connections.md) — `$credentials`, the `DB` facade, secondary connections.
- [`10-testing-models.md`](docs/10-testing-models.md) — how the package's own suite is wired and how to mirror it.

---

## Contributing

Contributions are welcome. The general flow is:

1. Fork and branch off `master`.
2. Add tests for the behaviour you change (see [`tests/`](tests/) — SQLite in-memory, fast, dependency-free).
3. Run the full quality suite locally:
   ```bash
   composer qa
   ```
4. Open a PR — CI runs the same suite across PHP 8.1–8.4.

By submitting a contribution you agree to license it under the MIT License.

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) — `<info@muhammetsafak.com.tr>`

## License

Released under the [MIT License](./LICENSE).
