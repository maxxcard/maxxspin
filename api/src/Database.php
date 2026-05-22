<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    public static function connect(): PDO
    {
        $databasePath = dirname(__DIR__) . '/data/app.sqlite';
        $databaseDirectory = dirname($databasePath);

        if (!is_dir($databaseDirectory)) {
            mkdir($databaseDirectory, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $databasePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');

        self::initializeSchema($pdo);

        return $pdo;
    }

    private static function initializeSchema(PDO $pdo): void
    {
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

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS contacts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                last_name TEXT NOT NULL DEFAULT \'\',
                email TEXT NOT NULL,
                company TEXT NOT NULL,
                cargo TEXT NOT NULL DEFAULT \'\',
                phone TEXT NOT NULL,
                prize_won TEXT NOT NULL DEFAULT \'\',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        if (!self::tableHasColumn($pdo, 'contacts', 'last_name')) {
            $pdo->exec('ALTER TABLE contacts ADD COLUMN last_name TEXT NOT NULL DEFAULT \'\'');
        }

        if (!self::tableHasColumn($pdo, 'contacts', 'cargo')) {
            $pdo->exec('ALTER TABLE contacts ADD COLUMN cargo TEXT NOT NULL DEFAULT \'\'');
        }

        if (!self::tableHasColumn($pdo, 'contacts', 'prize_won')) {
            $pdo->exec('ALTER TABLE contacts ADD COLUMN prize_won TEXT NOT NULL DEFAULT \'\'');
        }

        $existingUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        if ($existingUsers === 0) {
            $statement = $pdo->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
            $statement->execute([
                ':username' => 'admin',
                ':password' => 'password123',
            ]);
        }

        $existingSegments = (int) $pdo->query('SELECT COUNT(*) FROM wheel_segments')->fetchColumn();

        if ($existingSegments === 0) {
            $statement = $pdo->prepare(
                'INSERT INTO wheel_segments (name, color, chance) VALUES (:name, :color, :chance)'
            );

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
    }

    private static function tableHasColumn(PDO $pdo, string $table, string $column): bool
    {
        $statement = $pdo->query(sprintf('PRAGMA table_info(%s)', $table));

        foreach ($statement->fetchAll() as $definition) {
            if (($definition['name'] ?? null) === $column) {
                return true;
            }
        }

        return false;
    }
}
