<?php

declare(strict_types=1);

namespace InitORM\ORM\Tests\Support;

use InitORM\Database\Database;
use InitORM\Database\Interfaces\DatabaseInterface;
use InitORM\DBAL\Connection\Connection;
use InitORM\DBAL\Connection\Interfaces\ConnectionInterface;

/**
 * Test fixture: each call returns a brand-new in-memory SQLite connection
 * (a SQLite ":memory:" database is per-PDO-handle), with a `posts` table
 * seeded with three rows that exercise the soft-delete flag.
 */
final class SqliteHelper
{
    /**
     * @param array<string, mixed> $overrides
     */
    public static function makeConnection(array $overrides = []): ConnectionInterface
    {
        return new Connection(array_merge([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'charset'  => '',
        ], $overrides));
    }

    public static function makeDatabase(array $overrides = []): DatabaseInterface
    {
        return new Database(self::makeConnection($overrides));
    }

    public static function seedPosts(ConnectionInterface $connection): void
    {
        $pdo = $connection->getPDO();
        $pdo->exec(
            'CREATE TABLE posts (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                title       TEXT    NOT NULL,
                body        TEXT,
                created_at  TEXT,
                updated_at  TEXT,
                deleted_at  TEXT
            )'
        );
        $pdo->exec(
            "INSERT INTO posts (title, body, created_at, deleted_at) VALUES
                ('First',  'first body',  '2024-01-01 00:00:00', NULL),
                ('Second', 'second body', '2024-01-02 00:00:00', NULL),
                ('Third',  'third body',  '2024-01-03 00:00:00', '2024-02-01 00:00:00')"
        );
    }

    public static function seedTags(ConnectionInterface $connection): void
    {
        $pdo = $connection->getPDO();
        $pdo->exec(
            'CREATE TABLE tags (
                id    INTEGER PRIMARY KEY AUTOINCREMENT,
                label TEXT    NOT NULL
            )'
        );
    }
}
