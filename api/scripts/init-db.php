<?php

declare(strict_types=1);

$databasePath = __DIR__ . '/../data/app.sqlite';
$isNewDatabase = !file_exists($databasePath);

if (!is_dir(dirname($databasePath))) {
    mkdir(dirname($databasePath), 0777, true);
}

$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS wheel_segments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        color TEXT NOT NULL,
        chance INTEGER NOT NULL
    )'
);

$existingSegments = (int) $pdo->query('SELECT COUNT(*) FROM wheel_segments')->fetchColumn();

if ($isNewDatabase || $existingSegments === 0) {
    $pdo->exec('DELETE FROM wheel_segments');

    $statement = $pdo->prepare('INSERT INTO wheel_segments (name, color, chance) VALUES (:name, :color, :chance)');

    foreach ([
        ['name' => '10% Off', 'color' => '#f97316', 'chance' => 5],
        ['name' => 'Gift Box', 'color' => '#ef4444', 'chance' => 5],
        ['name' => 'Try Again', 'color' => '#ec4899', 'chance' => 75],
        ['name' => 'Free Ship', 'color' => '#8b5cf6', 'chance' => 5],
        ['name' => '50 Points', 'color' => '#3b82f6', 'chance' => 5],
        ['name' => 'Jackpot', 'color' => '#14b8a6', 'chance' => 5],
    ] as $segment) {
        $statement->execute([
            ':name' => $segment['name'],
            ':color' => $segment['color'],
            ':chance' => $segment['chance'],
        ]);
    }
}

$existingUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($isNewDatabase || $existingUsers === 0) {
    $pdo->exec('DELETE FROM users');

    $statement = $pdo->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
    $statement->execute([
        ':username' => 'admin',
        ':password' => 'password123',
    ]);
}
