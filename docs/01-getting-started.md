# 01 — Getting started

`initorm/orm` is the topmost layer of the InitORM stack. Each layer adds one concern; you can drop down a layer whenever you want raw access.

```
QueryBuilder  ──►  Database  ──►  ORM (this package)
DBAL          ──►  Database
```

| Layer                 | Job                                                              |
| --------------------- | ---------------------------------------------------------------- |
| `initorm/query-builder` | Pure SQL string assembly + parameter binding (no I/O).         |
| `initorm/dbal`          | PDO lifecycle + a fluent result mapper (`asAssoc`, `asClass`). |
| `initorm/database`      | Glues a connection to a builder; exposes CRUD + transactions.  |
| `initorm/orm` (you)     | Active-Record-style models + entities.                         |

A `Model` holds a `DatabaseInterface`. Unknown method calls are forwarded to it (and the Database forwards builder calls to the query builder). Chainable calls re-wrap at every boundary, so the model is the natural root of a fluent chain.

---

## Install

```bash
composer require initorm/orm
```

`initorm/orm` declares `php: ^8.1`, `ext-pdo: *`, and `initorm/database: ^2.0` — the other two layers come transitively.

---

## Bootstrap

Models bind to the shared `DB` facade by default. Configure it once at boot:

```php
require_once 'vendor/autoload.php';

use InitORM\Database\Facade\DB;

DB::createImmutable([
    'dsn'      => 'mysql:host=localhost;dbname=app;charset=utf8mb4',
    'username' => 'app',
    'password' => 'secret',
]);
```

`createImmutable()` throws if called a second time. To swap the slot explicitly (rare, mostly for tests) call `DB::replaceImmutable($next)`.

For SQLite — useful in tests:

```php
DB::createImmutable([
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'charset'  => '',
]);
```

The configuration array is forwarded verbatim to `initorm/database`. See the [Database configuration reference](https://github.com/InitORM/Database/blob/master/README.md#configuration-reference) for every key.

---

## First model + entity

```php
namespace App\Model;

use InitORM\ORM\Model;

class Posts extends Model
{
    protected string $schema   = 'posts';
    protected string $schemaId = 'id';
}
```

```php
namespace App\Entity;

use InitORM\ORM\Entity;

class PostEntity extends Entity
{
}
```

Wire the entity into the model:

```php
class Posts extends Model
{
    protected string $schema = 'posts';
    protected string $entity = \App\Entity\PostEntity::class;
}
```

Use it:

```php
$posts = new \App\Model\Posts();

$posts->create(['title' => 'Hello', 'body' => 'World']);

foreach ($posts->read()->rows() as $row) {
    var_dump($row instanceof \App\Entity\PostEntity); // bool(true)
    echo $row->title, PHP_EOL;
}
```

That is the entire surface for the simplest case. Subsequent docs add timestamps, soft deletes, accessors / mutators, and the builder forwarding contract.
