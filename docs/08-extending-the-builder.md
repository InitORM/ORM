# 08 — Extending the builder

A `Model` is a thin wrapper around a `DatabaseInterface`. Every call you make to the model first checks for a method on the model itself; anything else flows downward through `__call`:

```
Model::someBuilderMethod()
    → Database::__call()
        → QueryBuilder::someBuilderMethod()   (returns the builder)
    → Database returns itself (because the builder returned itself)
Model returns itself (because the Database returned itself)
```

The net effect: any method on the [`DatabaseInterface`](https://github.com/InitORM/Database/blob/master/src/Interfaces/DatabaseInterface.php) and any method on [`QueryBuilderInterface`](https://github.com/InitORM/QueryBuilder/blob/master/src/QueryBuilderInterface.php) is callable directly on the model, and fluent chains stay rooted in the model.

---

## A full chain

```php
$posts = new \App\Model\Posts();

$rows = $posts
    ->select('id', 'title')
    ->where('status', '=', 'published')
    ->andWhere('author_id', '=', 7)
    ->orderBy('id', 'DESC')
    ->limit(10)
    ->offset(0)
    ->read()              // hydrates as PostEntity instances
    ->rows();
```

`select`, `where`, `andWhere`, `orderBy`, `limit`, `offset` are all builder calls — forwarded through Database, re-wrapped to the Model. `read()` is the Model's own method.

---

## The forwarded surface

The builder ships ~100 fluent methods. The most useful families:

### Projection

- `select(...$columns)`, `clearSelect()`
- `selectAs($col, $alias)`, `selectDistinct(...)`, `selectMax(...)`, `selectMin(...)`, `selectAvg(...)`, `selectSum(...)`, `selectCount(...)`, `selectCountDistinct(...)`
- `selectUpper(...)`, `selectLower(...)`, `selectLength(...)`, `selectMid(...)`, `selectLeft(...)`, `selectRight(...)`, `selectCoalesce(...)`, `selectConcat(...)`

### Joins

- `join($table, $on, $type)`
- `innerJoin`, `leftJoin`, `rightJoin`, `leftOuterJoin`, `rightOuterJoin`, `selfJoin`, `naturalJoin`

### WHERE family

- `where($col, $op, $val)`, `andWhere`, `orWhere`
- `whereIn`, `whereNotIn`, `orWhereIn`, `andWhereIn`, …
- `whereIsNull`, `whereIsNotNull`, `andWhereIsNull`, `orWhereIsNull`
- `between`, `andBetween`, `orBetween`, `notBetween`, …
- `like`, `andLike`, `orLike`, `notLike`, `startLike`, `endLike`, …
- `regexp`, `soundex`, `findInSet`, `notFindInSet`

### Grouping, sorting, paging

- `groupBy(...)`, `having(...)`
- `orderBy($col, $direction)`
- `limit($n)`, `offset($n)`

### Other

- `subQuery(Closure $closure, ?string $alias = null, bool $isInterval = true)`
- `group(Closure $closure, string $logical = 'AND')` — grouped WHERE
- `raw($sql)` / `DB::raw($sql)` — escape-hatch for hand-written SQL fragments

For the exhaustive list, see the `@method static` annotations on [`InitORM\Database\Facade\DB`](https://github.com/InitORM/Database/blob/master/src/Facade/DB.php) — every method listed there is callable on a model with the same signature.

---

## Mixing builder calls and CRUD

Builder methods accumulate state on the underlying query builder. CRUD calls (`read`, `update`, `delete`, etc.) consume that state and then reset it — so the next CRUD call starts clean:

```php
$posts->where('id', '=', 5)->update(['title' => 'X']); // uses WHERE
$posts->update(['title' => 'Y'], ['id' => 6]);          // clean slate; explicit conditions
```

If you need two independent queries against the same connection, spin off a fresh builder via the Database:

```php
$reports = $posts->getDatabase()->withFreshBuilder();
$reports->read('events')->rows();
```

---

## When forwarding fails

`Model::__call` raises `BadMethodCallException` when the method does not exist on the Database, on the builder, or on any of their `@mixin` surfaces:

```php
$posts->thisIsNotARealMethod();   // BadMethodCallException
```

The exception message includes the model class and the requested method name. The original `DatabaseException` raised by the Database layer is chained as the previous exception (`getPrevious()`).
