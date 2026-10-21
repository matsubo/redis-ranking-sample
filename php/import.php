<?php

declare(strict_types=1);

// Adds the members in member.txt that are missing from Redis with 0 votes;
// existing members keep their votes, so it is safe to re-run.
//   php import.php

use App\Domain\Ballot;
use App\Infrastructure\RedisProvider;

require __DIR__ . '/vendor/autoload.php';

$lines = file(__DIR__ . '/member.txt', \FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    fwrite(\STDERR, "Error: Cannot read member.txt\n");
    exit(1);
}

try {
    $added = new Ballot(RedisProvider::fromEnvironment())->addMissing($lines);
} catch (\RedisException $e) {
    fwrite(\STDERR, 'Redis error: ' . $e->getMessage() . "\n");
    exit(1);
}

foreach ($added as $name) {
    echo "Added: {$name}\n";
}
echo $added === [] ? "All members already exist. Nothing to add.\n" : "\nTotal added: " . count($added) . " members\n";
