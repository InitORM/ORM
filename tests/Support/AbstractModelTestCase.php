<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support;

use InitORM\Database\Database;
use InitORM\Database\Facade\DB;
use InitORM\Database\Interfaces\DatabaseInterface;
use InitORM\DBAL\Connection\Interfaces\ConnectionInterface;
use PHPUnit\Framework\TestCase;

/**
 * Base test case that gives each test a fresh in-memory SQLite connection
 * wired through the {@see DB} facade, and tears the facade slot down again
 * afterwards so tests do not bleed state into one another.
 */
abstract class AbstractModelTestCase extends TestCase
{
    protected ConnectionInterface $connection;

    protected DatabaseInterface $db;

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
