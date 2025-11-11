<?php
declare(strict_types=1);

namespace App;

use RuntimeException;
use SQLite3;
use Throwable;

final class UserRepository
{
    private SQLite3 $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database->connection();
    }

    public function all(): array
    {
        $result = $this->connection->query('SELECT id, username, email, created_at, updated_at FROM users ORDER BY id');
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, username, email, created_at, updated_at FROM users WHERE id = :id');
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $statement->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        return $row ?: null;
    }

    public function create(string $username, string $email, string $password): array
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $statement = $this->connection->prepare('INSERT INTO users (username, email, password, created_at, updated_at) VALUES (:username, :email, :password, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
        $statement->bindValue(':username', $username, SQLITE3_TEXT);
        $statement->bindValue(':email', $email, SQLITE3_TEXT);
        $statement->bindValue(':password', $hash, SQLITE3_TEXT);
        try {
            $statement->execute();
        } catch (Throwable $exception) {
            throw $exception;
        }
        $id = (int) $this->connection->lastInsertRowID();
        return $this->requireById($id);
    }

    public function update(int $id, array $attributes): array
    {
        $segments = [];
        $bindings = [];
        if (array_key_exists('username', $attributes)) {
            $segments[] = 'username = :username';
            $bindings[] = [':username', $attributes['username'], SQLITE3_TEXT];
        }
        if (array_key_exists('email', $attributes)) {
            $segments[] = 'email = :email';
            $bindings[] = [':email', $attributes['email'], SQLITE3_TEXT];
        }
        if (array_key_exists('password', $attributes)) {
            $segments[] = 'password = :password';
            $bindings[] = [':password', password_hash($attributes['password'], PASSWORD_BCRYPT), SQLITE3_TEXT];
        }
        if (empty($segments)) {
            throw new RuntimeException('No fields provided');
        }
        $segments[] = 'updated_at = CURRENT_TIMESTAMP';
        $query = 'UPDATE users SET ' . implode(', ', $segments) . ' WHERE id = :id';
        $statement = $this->connection->prepare($query);
        foreach ($bindings as $binding) {
            $statement->bindValue($binding[0], $binding[1], $binding[2]);
        }
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        try {
            $statement->execute();
        } catch (Throwable $exception) {
            throw $exception;
        }
        return $this->requireById($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM users WHERE id = :id');
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $statement->execute();
        return $this->connection->changes() > 0;
    }

    public function exists(int $id): bool
    {
        $statement = $this->connection->prepare('SELECT 1 FROM users WHERE id = :id');
        $statement->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $statement->execute();
        return (bool) $result->fetchArray(SQLITE3_NUM);
    }

    private function requireById(int $id): array
    {
        $user = $this->find($id);
        if ($user === null) {
            throw new RuntimeException('User not found');
        }
        return $user;
    }
}
