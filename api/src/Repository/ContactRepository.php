<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class ContactRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(string $name, string $lastName, string $email, string $company, string $phone): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO contacts (name, last_name, email, company, phone) VALUES (:name, :last_name, :email, :company, :phone)'
        );
        $statement->execute([
            ':name' => $name,
            ':last_name' => $lastName,
            ':email' => $email,
            ':company' => $company,
            ':phone' => $phone,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
