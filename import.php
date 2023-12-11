<?php

require_once __DIR__ . '/vendor/autoload.php';

use Matsubo\Redis\Ranking;

$redis = new \Redis;
$redis->connect('redis-15189.c290.ap-northeast-1-2.ec2.cloud.redislabs.com', 15189);
$redis->auth(['NOYoAfaQFvlRRWxVcEkFMqdaVRSVUB17']);

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

