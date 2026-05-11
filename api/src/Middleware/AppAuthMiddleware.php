<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Database;
use App\Repository\SegmentRepository;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Slim\Psr7\Stream;

final class AppAuthMiddleware
{
    public function __construct(private readonly string $segmentsApiKey)
    {
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $pdo = Database::connect();
        $path = $request->getUri()->getPath();

        if ($path === '/segments.json' || $path === '/send-data') {
            return $this->authorizeApiKey($request, $handler, $pdo);
        }

        return $this->authorizeBasicAuth($request, $handler, $pdo);
    }

    private function authorizeApiKey(Request $request, RequestHandler $handler, PDO $pdo): Response
    {
        $providedKey = $request->getHeaderLine('X-Wheel-Key');

        if ($providedKey === '' || !hash_equals($this->segmentsApiKey, $providedKey)) {
            $response = new SlimResponse(401);
            $response->getBody()->write('Unauthorized API key.');

            return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        return $handler->handle($request->withAttribute('pdo', $pdo));
    }

    private function authorizeBasicAuth(Request $request, RequestHandler $handler, PDO $pdo): Response
    {
        $server = $request->getServerParams();
        $username = $server['PHP_AUTH_USER'] ?? null;
        $password = $server['PHP_AUTH_PW'] ?? null;

        if ($username === null || $password === null) {
            return $this->emptyBasicAuthResponse();
        }

        $repository = new SegmentRepository($pdo);

        if (!$repository->authenticateUser($username, $password)) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, 'Invalid credentials.');
            rewind($stream);

            return (new SlimResponse(401))
                ->withHeader('WWW-Authenticate', 'Basic realm="Slim App"')
                ->withHeader('Content-Type', 'text/plain')
                ->withBody(new Stream($stream));
        }

        return $handler->handle($request->withAttribute('pdo', $pdo));
    }

    private function emptyBasicAuthResponse(): Response
    {
        return (new SlimResponse(401))
            ->withHeader('WWW-Authenticate', 'Basic realm="Slim App"')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody(new Stream(fopen('php://temp', 'r+')));
    }
}
