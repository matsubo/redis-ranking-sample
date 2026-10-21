<?php

declare(strict_types=1);

namespace App\Domain;

use App\Infrastructure\RedisProvider;
use Matsubo\Redis\Ranking;

/**
 * At most $limit votes per voter in any sliding window of $windowMs.
 *
 * Each voter has a redis/ranking set of their own votes, scored by the time
 * of the vote, so Ranking::getRank() is a vote's position among the voter's
 * votes still in the window (oldest first). A vote is counted when that
 * position is below the limit. The position is fixed by the timestamps, so
 * when two replicas take the last slot at once the earlier vote wins and the
 * later one is refused; no lock or script is needed. (Pod clocks are NTP
 * synced; a skew of a few ms can at worst let one extra vote through.)
 *
 * Removing expired or refused entries and the key's TTL use the Redis
 * connection directly: redis/ranking 1.3 has no range delete or expiry, and
 * its deleteUser() calls zDelete(), which phpredis 6 removed.
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

        $redis->zRemRangeByScore($key, '-inf', (string) ($nowMs - $this->windowMs));
        $vote = $nowMs . ':' . bin2hex(random_bytes(6));
        $votes->setUserScore($vote, $nowMs);
        $redis->pExpire($key, $this->windowMs);

        $position = $votes->getRank($vote);
        if (!\is_int($position)) {
            throw new \RuntimeException("Vote limit lookup failed for {$voter}");
        }
        if ($position < $this->limit) {
            return VoteQuota::granted();
        }

        $redis->zRem($key, $vote);
        // The oldest vote in the window is the next one to expire.
        $oldest = $votes->getRange(0, 0, true);
        $oldestAt = $oldest === false || $oldest === [] ? $nowMs : (int) reset($oldest);

        return VoteQuota::denied($oldestAt + $this->windowMs - $nowMs);
    }
}
