<?php

declare(strict_types=1);

namespace App\Domain;

use App\Infrastructure\RedisProvider;
use Matsubo\Redis\ExpireMode;
use Matsubo\Redis\Ranking;

/**
 * At most $limit votes per voter in any sliding window of $windowMs.
 *
 * Each voter has a redis/ranking set of their own votes, scored by the time
 * of the vote. A request drops the votes that left the window, adds its own
 * vote, then counts the set: the vote is counted when the count is within the
 * limit, otherwise it is removed again.
 *
 * No lock or transaction is needed: a counted vote stays in the set until it
 * leaves the window, so the count a request sees right after adding itself
 * includes every counted vote in the window. When two replicas race for the
 * last slot, at least one of them sees limit + 1 and refuses. A refused vote
 * that has not been removed yet can only make another request refuse too,
 * never let one through. Admission depends on the count, not on the
 * timestamps' order, so a late request with an earlier timestamp is refused
 * like any other.
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
        $votes = new Ranking(self::KEY_PREFIX . $voter, $this->redis->get());
        $vote = $nowMs . ':' . bin2hex(random_bytes(6));

        $votes->deleteUsersByScores('-inf', $nowMs - $this->windowMs);
        $votes->setUserScore($vote, $nowMs);
        // A new set gets an expiry even if this request dies before the end;
        // an existing one keeps its expiry, so refused attempts do not extend it.
        $votes->expire($this->windowMs, ExpireMode::IfNone);

        if ($votes->countUsers() <= $this->limit) {
            $votes->expire($this->windowMs);

            return VoteQuota::granted();
        }

        $votes->deleteUser($vote);
        // The oldest vote in the window is the next one to expire.
        $oldestAt = (int) (array_values($votes->getRangeWithScores(0, 0))[0] ?? $nowMs);

        return VoteQuota::denied($oldestAt + $this->windowMs - $nowMs);
    }
}
