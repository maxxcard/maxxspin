<?php

declare(strict_types=1);

namespace App\Action;

use App\Database;
use App\Repository\SegmentRepository;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SegmentsJsonAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $pdo = $request->getAttribute('pdo');

        if (!$pdo instanceof PDO) {
            $pdo = Database::connect();
        }

        $repository = new SegmentRepository($pdo);
        $response->getBody()->write((string) json_encode(
            $repository->all(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
