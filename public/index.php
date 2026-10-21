<?php

declare(strict_types=1);

// Front controller. The application lives in ../php (same layout in the repo
// and in the image: /var/www/html and /var/www/php).

use App\Controller\RankingController;
use App\Domain\Ballot;
use App\Domain\VoteLimiter;
use App\Http\Request;
use App\Infrastructure\RedisProvider;
use App\View\Assets;
use App\View\View;

$app = dirname(__DIR__) . '/php';
require $app . '/vendor/autoload.php';

$redis = RedisProvider::fromEnvironment();
$controller = new RankingController(
    new Ballot($redis),
    new VoteLimiter($redis),
    new View($app . '/templates'),
    new Assets(__DIR__, 'https://ranking.teraren.com'),
);

$controller->handle(Request::fromGlobals())->send();
