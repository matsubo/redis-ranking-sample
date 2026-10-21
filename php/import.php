<?php

require_once '../vendor/autoload.php';

use Matsubo\Redis\Ranking;

$member_text = file_get_contents('member.txt');
if ($member_text === false) {
    fwrite(STDERR, "Error: Cannot read member.txt\n");
    exit(1);
}

$redis = new \Redis;
try {
    $redis->connect(getenv('REDIS_HOST'), (int)getenv('REDIS_PORT'));
    $redis->auth([getenv('REDIS_PASSWORD')]);
} catch (\RedisException $e) {
    fwrite(STDERR, "Redis connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

$key = 'akb';
$ranking = new Ranking($key, $redis);


// initialize code.
$member_array = explode("\n", $member_text);

// Get all existing members at once
$existing_members = array_flip($ranking->getRange(0, -1));

// Filter members that need to be added
$members_to_add = [];
foreach ($member_array as $member) {
    $member = trim($member);
    if (!$member) {
        continue;
    }
    if (!isset($existing_members[$member])) {
        $members_to_add[] = $member;
    }
}

// Batch add using pipeline
if (!empty($members_to_add)) {
    $redis->multi(\Redis::PIPELINE);
    foreach ($members_to_add as $member) {
        $redis->zadd($key, 0, $member);
    }
    $results = $redis->exec();

    $added = 0;
    foreach ($members_to_add as $i => $member) {
        if ($results[$i] !== false) {
            print "Added: $member\n";
            $added++;
        } else {
            fwrite(STDERR, "Failed to add: $member\n");
        }
    }
    print "\nTotal added: $added members\n";
} else {
    print "All members already exist. Nothing to add.\n";
}
