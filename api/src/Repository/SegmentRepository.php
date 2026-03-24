<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class SegmentRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function authenticateUser(string $username, string $password): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT 1 FROM users WHERE username = :username AND password = :password LIMIT 1'
        );
        $statement->execute([
            ':username' => $username,
            ':password' => $password,
        ]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @return array<int, array{id:int, name:string, color:string, chance:int}>
     */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT id, name, color, chance FROM wheel_segments ORDER BY id ASC'
        )->fetchAll();
    }

    /**
     * @return array<int, array{name:string, color:string, chance:int}>
     */
    public function allForEditor(): array
    {
        return $this->pdo->query(
            'SELECT name, color, chance FROM wheel_segments ORDER BY id ASC'
        )->fetchAll();
    }

    /**
     * @param array<int, array{name:string, color:string, chance:string|int}> $segments
     */
    public function replaceAll(array $segments): void
    {
        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec('DELETE FROM wheel_segments');

            $statement = $this->pdo->prepare(
                'INSERT INTO wheel_segments (name, color, chance) VALUES (:name, :color, :chance)'
            );

            foreach ($segments as $segment) {
                $statement->execute([
                    ':name' => $segment['name'],
                    ':color' => $segment['color'],
                    ':chance' => (int) $segment['chance'],
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }
}
