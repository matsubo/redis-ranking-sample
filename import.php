<?php

require_once __DIR__ . '/vendor/autoload.php';

use Matsubo\Redis\Ranking;

$redis = new \Redis;
$redis->connect('127.0.0.1', 6379);

$ranking = new Ranking($key = 'akb', $redis);


// initialize code.
$member_text = file_get_contents('member.txt');
$member_array = explode("\n", $member_text);
foreach ($member_array as $member) {
    $member = trim($member);
    if (!$member) {
        continue;
    }
    if ($ranking->getScore($member) !== false) {
        continue;
    }
    $ranking->setUserScore($member, 0);
    print "Added: $member \n";
}

