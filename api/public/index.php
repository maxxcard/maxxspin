<?php

declare(strict_types=1);

use App\Action\SegmentEditorAction;
use App\Action\SegmentsJsonAction;
use App\Middleware\AppAuthMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->add(new AppAuthMiddleware('wheel-segments-key'));

$app->get('/segments.json', new SegmentsJsonAction());
$app->map(['GET', 'POST'], '/', new SegmentEditorAction());

$app->run();
