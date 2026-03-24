<?php

declare(strict_types=1);

namespace App\Action;

use App\Database;
use App\Repository\SegmentRepository;
use App\Support\SegmentForm;
use App\View\SegmentEditorPage;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SegmentEditorAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $pdo = $request->getAttribute('pdo');

        if (!$pdo instanceof PDO) {
            $pdo = Database::connect();
        }

        $repository = new SegmentRepository($pdo);
        $parsedBody = (array) $request->getParsedBody();
        $errors = [];
        $successMessage = null;
        $formRows = SegmentForm::normalize($repository->allForEditor());

        if ($request->getMethod() === 'POST') {
            $formRows = SegmentForm::fromRequest($parsedBody);
            $validation = SegmentForm::validate($formRows);
            $errors = $validation['errors'];

            if ($errors === []) {
                $repository->replaceAll($formRows);
                $successMessage = 'Segments saved. Existing options were replaced with the current rows.';
                $formRows = SegmentForm::normalize($repository->allForEditor());
            }
        }

        $response->getBody()->write(
            (new SegmentEditorPage())->render($formRows, $errors, $successMessage)
        );

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
