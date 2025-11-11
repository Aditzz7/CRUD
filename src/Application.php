<?php
declare(strict_types=1);

namespace App;

use RuntimeException;
use Throwable;

final class Application
{
    private UserRepository $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    public function run(string $method, string $uri, string $body): void
    {
        $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');
        $segments = $path === '' ? [] : explode('/', $path);
        if (empty($segments) || $segments[0] !== 'users') {
            HttpResponse::json(404, ['error' => 'Not Found']);
            return;
        }
        $id = $segments[1] ?? null;
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($id);
                    break;
                case 'POST':
                    $this->handlePost($body);
                    break;
                case 'PUT':
                    $this->handlePut($id, $body);
                    break;
                case 'DELETE':
                    $this->handleDelete($id);
                    break;
                default:
                    HttpResponse::json(405, ['error' => 'Method Not Allowed']);
            }
        } catch (RuntimeException $exception) {
            HttpResponse::json(400, ['error' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            if (stripos($exception->getMessage(), 'UNIQUE') !== false) {
                HttpResponse::json(409, ['error' => 'Username or email already exists']);
                return;
            }
            HttpResponse::json(500, ['error' => 'Internal Server Error']);
        }
    }

    private function handleGet(?string $id): void
    {
        if ($id === null) {
            $users = $this->users->all();
            HttpResponse::json(200, ['data' => $users]);
            return;
        }
        $userId = $this->assertValidId($id);
        $user = $this->users->find($userId);
        if ($user === null) {
            HttpResponse::json(404, ['error' => 'User not found']);
            return;
        }
        HttpResponse::json(200, ['data' => $user]);
    }

    private function handlePost(string $body): void
    {
        $payload = $this->decodeJson($body);
        foreach (['username', 'email', 'password'] as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                throw new RuntimeException($field . ' is required');
            }
        }
        $user = $this->users->create((string) $payload['username'], (string) $payload['email'], (string) $payload['password']);
        HttpResponse::json(201, ['data' => $user]);
    }

    private function handlePut(?string $id, string $body): void
    {
        $userId = $this->assertValidId($id);
        $payload = $this->decodeJson($body);
        $allowed = array_intersect_key($payload, array_flip(['username', 'email', 'password']));
        if (empty($allowed)) {
            throw new RuntimeException('No valid fields provided');
        }
        if (!$this->users->exists($userId)) {
            HttpResponse::json(404, ['error' => 'User not found']);
            return;
        }
        $user = $this->users->update($userId, $allowed);
        HttpResponse::json(200, ['data' => $user]);
    }

    private function handleDelete(?string $id): void
    {
        $userId = $this->assertValidId($id);
        if (!$this->users->delete($userId)) {
            HttpResponse::json(404, ['error' => 'User not found']);
            return;
        }
        HttpResponse::empty(204);
    }

    private function decodeJson(string $body): array
    {
        if ($body === '') {
            throw new RuntimeException('Request body must be valid JSON');
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new RuntimeException('Request body must be valid JSON');
        }
        return $data;
    }

    private function assertValidId(?string $id): int
    {
        if ($id === null || !ctype_digit($id)) {
            throw new RuntimeException('Invalid user ID');
        }
        return (int) $id;
    }
}
