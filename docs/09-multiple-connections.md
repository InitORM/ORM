# 09 — Multiple connections

Models bind to the shared `DB` facade by default. For models that need a separate connection — a reporting database, a sharded tenant store, a read replica — set `$credentials` on the subclass.

---

## The default: shared `DB` facade

```php
use InitORM\Database\Facade\DB;

DB::createImmutable([
    'dsn'      => 'mysql:host=primary;dbname=app;charset=utf8mb4',
    'username' => 'app',
    'password' => 'secret',
]);

class Posts extends \InitORM\ORM\Model
{
    protected string $schema = 'posts';
}

(new Posts())->getDatabase() === DB::getDatabase(); // true
```

Every model with `$credentials = null` (the default) shares the same Database instance — which means they all share the same underlying PDO connection and query builder pool.

---

## A standalone connection per model

Set `$credentials` to give a model its own connection:

```php
class ReportsEvents extends \InitORM\ORM\Model
{
    protected string $schema = 'events';

    protected ?array $credentials = [
        'driver'   => 'pgsql',
        'host'     => 'reports.internal',
        'database' => 'reports',
        'username' => 'reports_ro',
        'password' => '…',
    ];
}
```

Internally, the constructor calls `DB::connect($credentials)`, which builds a fresh `Database` (and underlying `Connection`) without touching the shared facade slot.

The `$credentials` array is passed verbatim to the DBAL `Connection` constructor — see the [Database configuration reference](https://github.com/InitORM/Database/blob/master/README.md#configuration-reference) for every supported key (`dsn`, `host`, `port`, `database`, `username`, `password`, `charset`, `collation`, `driver`, `options`, `queryOptions`, `log`, `debug`, `queryLogs`).

---

## Multiple standalone connections

Each subclass with its own `$credentials` gets its own Database instance. Two models with identical `$credentials` arrays still produce two separate connections — there is no de-duplication at the model layer.

```php
class TenantA extends \InitORM\ORM\Model
{
    protected ?array $credentials = ['driver' => 'mysql', 'host' => 'tenant-a', ...];
}

class TenantB extends \InitORM\ORM\Model
{
    protected ?array $credentials = ['driver' => 'mysql', 'host' => 'tenant-b', ...];
}
```

If you need shared, named connections, build the `Database` objects yourself and cache them in a container, then expose them as `$credentials` per subclass via a factory:

```php
$connections = [
    'reports' => new \InitORM\Database\Database([...]),
    'audit'   => new \InitORM\Database\Database([...]),
];

class Reports extends \InitORM\ORM\Model
{
    public function __construct(\InitORM\Database\Interfaces\DatabaseInterface $db)
    {
        $this->schema = 'events';
        // Skip the constructor's facade lookup by reassigning $db ourselves
        // after parent::__construct() — but we need a non-null $credentials
        // sentinel to avoid the facade path. Simplest is to bypass entirely:
        parent::__construct();
        $this->db = $db; // requires making $db protected, or using a property
    }
}
```

This is an unusual setup; the conventional path is to either share the facade (one app, one database) or to give each model its own `$credentials` array.

---

## Swapping the facade target

`DB::createImmutable()` deliberately throws if called twice — silent reconfiguration of the application-wide connection is a footgun. To explicitly swap:

```php
DB::replaceImmutable($newDatabase);   // pass null to clear the slot
```

In tests, you usually want to clear and rebuild per test case. The package's own test base does exactly that — see [`tests/Support/AbstractModelTestCase.php`](../tests/Support/AbstractModelTestCase.php) and [10 — Testing models](10-testing-models.md).
