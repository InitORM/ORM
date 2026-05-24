# 07 — Permission gates

Each model carries four boolean flags, one per operation. Flip any to `false` and the matching typed exception fires at the start of the call — before any SQL is built or sent.

| Flag           | Operations guarded                       | Exception              |
| -------------- | ---------------------------------------- | ---------------------- |
| `$writable`    | `create()`, `createBatch()`              | `WritableException`    |
| `$readable`    | `read()`                                 | `ReadableException`    |
| `$updatable`   | `update()`, `updateBatch()`              | `UpdatableException`   |
| `$deletable`   | `delete()`                               | `DeletableException`   |

All four exceptions extend [`ModelException`](../src/Exceptions/ModelException.php), so a single catch handles them collectively when you do not care which gate tripped.

---

## When to use them

- **Read-only models**: a configuration or lookup table that the application reads but never mutates.

  ```php
  class Configuration extends \InitORM\ORM\Model
  {
      protected string $schema    = 'configuration';
      protected bool   $writable  = false;
      protected bool   $updatable = false;
      protected bool   $deletable = false;
  }

  (new Configuration())->create(['k' => 'v']); // WritableException
  ```

- **Write-only audit logs**: a table the application appends to but never reads through the model.

  ```php
  class AuditLog extends \InitORM\ORM\Model
  {
      protected string $schema    = 'audit_log';
      protected bool   $readable  = false;
      protected bool   $updatable = false;
      protected bool   $deletable = false;
  }
  ```

- **Append-only with retention**: a journal that the application writes and (rarely) deletes via a maintenance script.

  ```php
  class Events extends \InitORM\ORM\Model
  {
      protected string $schema    = 'events';
      protected bool   $updatable = false;
  }
  ```

---

## Error messages

The exception messages include the fully-qualified class name of the offending model:

```
App\Model\Configuration is not writable.
App\Model\AuditLog is not readable.
```

— which makes the failure easy to locate even when the stack trace skirts the call site (e.g. when the call originates inside a closure passed to a transaction).

---

## Catching collectively vs. specifically

```php
use InitORM\ORM\Exceptions\ModelException;
use InitORM\ORM\Exceptions\WritableException;

try {
    $config->create([...]);
} catch (WritableException $e) {
    // Specific: read-only configuration table can't be written.
} catch (ModelException $e) {
    // Generic ORM-layer failure (entity issues, model misconfiguration, etc.).
}
```

`ModelException` is also raised by the constructor when `$useSoftDeletes` is enabled without a `$deletedField`, so it is the right umbrella for "the ORM said no".

---

## Gates do not replace database constraints

The gates live in PHP — they prevent the model's CRUD methods from issuing the operation, but they do not stop a determined caller from sidestepping the model and calling `$model->getDatabase()` directly. For schema-level invariants (read-only roles, foreign-key cascades, triggers), enforce them in the database itself; gates are a code-hygiene aid, not an authorization layer.
