<?php
declare(strict_types=1);

namespace App;

use SQLite3;
use SQLite3Result;
use Throwable;

final class Database
{
    private SQLite3 $connection;

    private function __construct(SQLite3 $connection)
    {
        $this->connection = $connection;
    }

    public static function open(string $path): self
    {
        $connection = new SQLite3($path);
        $connection->exec('PRAGMA foreign_keys = ON');
        $connection->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)');
        return new self($connection);
    }

    public function connection(): SQLite3
    {
        return $this->connection;
    }

    public function run(string $query, array $bindings = []): SQLite3Result|false
    {
        $statement = $this->connection->prepare($query);
        foreach ($bindings as $binding) {
            $statement->bindValue($binding[0], $binding[1], $binding[2]);
        }
        try {
            return $statement->execute();
        } catch (Throwable $exception) {
            throw $exception;
        }
    }
}
