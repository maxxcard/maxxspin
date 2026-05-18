<?php

declare(strict_types=1);

namespace App\Action;

use App\Database;
use App\Repository\ContactRepository;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SendDataAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $pdo = $request->getAttribute('pdo');

        if (!$pdo instanceof PDO) {
            $pdo = Database::connect();
        }

        $payload = (array) $request->getParsedBody();
        $firstName = trim((string) ($payload['firstName'] ?? ''));
        $lastName = trim((string) ($payload['lastName'] ?? ''));
        $fullName = trim((string) ($payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $company = trim((string) ($payload['company'] ?? ''));
        $cargo = trim((string) ($payload['cargo'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));

        if ($firstName === '' && $fullName !== '') {
            [$firstName, $lastName] = $this->splitName($fullName);
        }

        $errors = [];

        if ($firstName === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Email must be valid.';
        }

        if ($company === '') {
            $errors['company'] = 'Company is required.';
        }

        if ($cargo === '') {
            $errors['cargo'] = 'Cargo is required.';
        }

        if ($phone === '') {
            $errors['phone'] = 'Phone is required.';
        }

        if ($errors !== []) {
            $response->getBody()->write((string) json_encode([
                'success' => false,
                'errors' => $errors,
            ], JSON_UNESCAPED_SLASHES));

            return $response
                ->withStatus(422)
                ->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        $repository = new ContactRepository($pdo);
        $contactId = $repository->create($firstName, $lastName, $email, $company, $cargo, $phone);

        $response->getBody()->write((string) json_encode([
            'success' => true,
            'id' => $contactId,
            'message' => 'Contact saved successfully.',
        ], JSON_UNESCAPED_SLASHES));

        return $response
            ->withStatus(201)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if ($parts === []) {
            return ['', ''];
        }

        $firstName = (string) array_shift($parts);

        return [$firstName, trim(implode(' ', $parts))];
    }
}
