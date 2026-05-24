# 10 — Testing models

Models are testable without a database server. The package's own suite hits an **in-memory SQLite** connection, which is fast and dependency-free — every test gets a fresh schema and seed data, isolated by construction.

---

## Requirements

- `ext-pdo`
- `ext-pdo_sqlite`
- PHPUnit 10+

The package's `composer.json` lists `phpunit/phpunit ^10.5` and `ext-pdo_sqlite: *` under `require-dev`, so a fresh `composer install` gives you everything you need.

---

## The test base

[`tests/Support/AbstractModelTestCase.php`](../tests/Support/AbstractModelTestCase.php) sets up a fresh facade-wired Database per test:

```php
abstract class AbstractModelTestCase extends \PHPUnit\Framework\TestCase
{
    protected ConnectionInterface $connection;
    protected DatabaseInterface   $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = SqliteHelper::makeConnection();
        SqliteHelper::seedPosts($this->connection);

        $this->db = new Database($this->connection);
        DB::replaceImmutable($this->db);
    }

    protected function tearDown(): void
    {
        DB::replaceImmutable(null);
        parent::tearDown();
    }
}
```

Two important details:

1. Each `setUp()` produces a **new** in-memory SQLite database — `:memory:` is per-PDO-handle, so reusing a Database across tests would imply reusing schema and rows.
2. `DB::replaceImmutable(null)` in `tearDown()` clears the facade slot so the next test does not inherit it.

---

## Fixtures

Test fixtures live under [`tests/Support/Fixtures/`](../tests/Support/Fixtures/). The package ships:

- `PostModel` — a soft-delete-enabled model with auto-filled timestamps.
- `PostEntity` — an entity with both an accessor and a mutator.
- `TagModel` — a model with no explicit `$schema` (exercises auto-derivation).
- `NonWritablePostModel`, `NonReadablePostModel`, `NonUpdatablePostModel`, `NonDeletablePostModel` — gate-checked variants.

---

## A complete example

```php
final class MyPostsTest extends \InitORM\ORM\Tests\Support\AbstractModelTestCase
{
    public function test_creates_a_post(): void
    {
        $posts = new \InitORM\ORM\Tests\Support\Fixtures\PostModel();
        $posts->create(['title' => 'Hello', 'body' => 'world']);

        $rows = $this->db->table('posts')->read()->asAssoc()->rows();
        self::assertCount(4, $rows); // 3 seeded + 1 new
    }

    public function test_soft_delete_hides_row(): void
    {
        $posts = new \InitORM\ORM\Tests\Support\Fixtures\PostModel();
        $posts->delete(['id' => 1]);

        $remaining = $posts->read()->rows();
        self::assertCount(1, $remaining);   // 2 seeded - 1 just-deleted
    }
}
```

---

## Asserting against bound parameters

Set `enableQueryLog()` on the Database before the operation and inspect the buffer afterwards:

```php
$this->db->enableQueryLog();
$posts->create(['title' => 'X']);

$entries = $this->db->getQueryLogs();
self::assertStringContainsString('INSERT INTO', $entries[0]['query']);
self::assertSame('X', $entries[0]['args'][':title']);
```

Useful when locking in *exactly* what SQL the model generates.

---

## Running the suite

```bash
composer test                  # phpunit
composer test:coverage         # phpunit with HTML coverage at build/coverage/
composer qa                    # phpcs + phpstan + phpunit
```

CI runs the same matrix across PHP 8.1, 8.2, 8.3, and 8.4 on every push and pull request — see the [`.github/workflows/`](../.github/workflows/) directory.

---

## Testing models against a real database

The same patterns work against MySQL or PostgreSQL — just point `SqliteHelper::makeConnection()` (or your own builder) at the right `dsn` and credentials. In CI, this typically means a service container (GitHub Actions' `services:` block, Docker, etc.) per workflow.

Generally, prefer SQLite in-memory for fast, per-test isolation, and run a smaller integration suite against the real driver to catch dialect differences (identifier quoting, type coercion, transaction visibility).
