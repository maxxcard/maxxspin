<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ContactRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(
        string $name,
        string $lastName,
        string $email,
        string $company,
        string $cargo,
        string $phone,
        string $prizeWon
    ): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO contacts (name, last_name, email, company, cargo, phone, prize_won) VALUES (:name, :last_name, :email, :company, :cargo, :phone, :prize_won)'
        );
        $statement->execute([
            ':name' => $name,
            ':last_name' => $lastName,
            ':email' => $email,
            ':company' => $company,
            ':cargo' => $cargo,
            ':phone' => $phone,
            ':prize_won' => $prizeWon,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
