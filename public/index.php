<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Application;
use App\Database;
use App\UserRepository;

$databasePath = __DIR__ . '/../storage/database.sqlite';
$database = Database::open($databasePath);
$repository = new UserRepository($database);
$application = new Application($repository);
$application->run($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/', file_get_contents('php://input') ?: '');
