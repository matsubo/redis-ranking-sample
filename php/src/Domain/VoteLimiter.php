<?php

declare(strict_types=1);

namespace App\Domain;

use App\Infrastructure\RedisProvider;
use Matsubo\Redis\Ranking;

/**
 * At most $limit votes per voter in any sliding window of $windowMs.
 *
 * Each voter has a redis/ranking set of their own votes, scored by the time
 * of the vote. One MULTI/EXEC transaction drops the votes that left the
 * window, adds this vote and counts the set, so every request's count
 * includes every vote added before it: when two replicas race for the last
 * slot, the second one counts limit + 1 and is refused. A refused vote is
 * removed again afterwards; until then it can only make a concurrent request
 * count high (refuse), never let one exceed the limit. Admission depends on
 * the count, not on the timestamps, so pod clock skew does not matter.
 *
 * The range delete, the count and the key's TTL use the Redis connection
 * directly: redis/ranking 1.3 has no range delete or expiry, and its
 * countUsers()/deleteUser() call zSize()/zDelete(), which phpredis 6 removed.
 */
final readonly class VoteLimiter
{
    public const string KEY_PREFIX = 'vote-limit:';

    public function __construct(
        private RedisProvider $redis,
        private int $limit = 100,
        private int $windowMs = 24 * 60 * 60 * 1000,
    ) {}

    public function limit(): int
    {
        return $this->limit;
    }

    public function attempt(string $voter, int $nowMs): VoteQuota
    {
        $key = self::KEY_PREFIX . $voter;
        $redis = $this->redis->get();
        $votes = new Ranking($key, $redis);
        $vote = $nowMs . ':' . bin2hex(random_bytes(6));

        $redis->multi();
        $redis->zRemRangeByScore($key, '-inf', (string) ($nowMs - $this->windowMs));
        $votes->setUserScore($vote, $nowMs);
        $redis->zCard($key);
        // A new key gets a TTL even if this process dies before the end; an
        // existing TTL is left alone so refused attempts do not extend it.
        $redis->pExpire($key, $this->windowMs, 'NX');
        $result = $redis->exec();

        $count = \is_array($result) ? $result[2] ?? null : null;
        if (!\is_int($count)) {
            throw new \RuntimeException("Vote limit transaction failed for {$voter}");
        }

        if ($count <= $this->limit) {
            $redis->pExpire($key, $this->windowMs);

            return VoteQuota::granted();
        }

        $redis->zRem($key, $vote);
        // The oldest vote in the window is the next one to expire.
        $oldest = $votes->getRange(0, 0, true);
        $oldestAt = $oldest === false || $oldest === [] ? $nowMs : (int) reset($oldest);

        return VoteQuota::denied($oldestAt + $this->windowMs - $nowMs);
    }
}
