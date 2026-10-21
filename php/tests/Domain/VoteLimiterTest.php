<?php

declare(strict_types=1);

namespace App\Tests\Domain;

use App\Domain\VoteLimiter;
use App\Infrastructure\RedisProvider;
use PHPUnit\Framework\TestCase;

/** Runs against the compose Redis (docker compose run --rm php test). */
final class VoteLimiterTest extends TestCase
{
    private const int WINDOW = 60_000;

    private RedisProvider $redis;

    private string $voter;

    protected function setUp(): void
    {
        $this->redis = RedisProvider::fromEnvironment();
        $this->voter = 'test-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->redis->get()->del(VoteLimiter::KEY_PREFIX . $this->voter);
    }

    public function testRefusesTheVoteAfterTheLimitUntilTheOldestExpires(): void
    {
        $limiter = new VoteLimiter($this->redis, 3, self::WINDOW);

        self::assertTrue($limiter->attempt($this->voter, 1_000)->allowed);
        self::assertTrue($limiter->attempt($this->voter, 2_000)->allowed);
        self::assertTrue($limiter->attempt($this->voter, 3_000)->allowed);

        $refused = $limiter->attempt($this->voter, 10_000);
        self::assertFalse($refused->allowed);
        // The vote at 1 000 leaves the window at 61 000.
        self::assertSame(51_000, $refused->retryAfterMs);

        self::assertFalse($limiter->attempt($this->voter, 60_999)->allowed);
        self::assertTrue($limiter->attempt($this->voter, 61_000)->allowed);
        self::assertFalse($limiter->attempt($this->voter, 61_500)->allowed);
    }

    public function testRefusedAttemptsDoNotUseUpTheQuota(): void
    {
        $limiter = new VoteLimiter($this->redis, 2, self::WINDOW);
        $limiter->attempt($this->voter, 1_000);
        $limiter->attempt($this->voter, 2_000);

        for ($i = 0; $i < 5; ++$i) {
            self::assertFalse($limiter->attempt($this->voter, 3_000 + $i)->allowed);
        }

        // Only the two counted votes are in the window, so one slot frees at 61 000.
        self::assertTrue($limiter->attempt($this->voter, 61_000)->allowed);
        self::assertSame(2, $this->redis->get()->zCard(VoteLimiter::KEY_PREFIX . $this->voter));
    }

    public function testAVoteThatArrivesLateWithAnEarlierTimestampIsStillRefused(): void
    {
        // A request stamped before the others (slow replica, clock skew) sorts
        // ahead of them in the set; it must still count as the 4th vote.
        $limiter = new VoteLimiter($this->redis, 3, self::WINDOW);
        $limiter->attempt($this->voter, 20_000);
        $limiter->attempt($this->voter, 20_001);
        $limiter->attempt($this->voter, 20_002);

        self::assertFalse($limiter->attempt($this->voter, 10_000)->allowed);
        self::assertSame(3, $this->redis->get()->zCard(VoteLimiter::KEY_PREFIX . $this->voter));
    }

    public function testRefusedAttemptsDoNotExtendTheKeyLifetime(): void
    {
        $limiter = new VoteLimiter($this->redis, 1, self::WINDOW);
        $key = VoteLimiter::KEY_PREFIX . $this->voter;
        $limiter->attempt($this->voter, 1_000);
        $this->redis->get()->pExpire($key, 5_000);

        self::assertFalse($limiter->attempt($this->voter, 2_000)->allowed);
        self::assertLessThanOrEqual(5_000, $this->redis->get()->pttl($key));
    }

    public function testVotersAreCountedSeparately(): void
    {
        $limiter = new VoteLimiter($this->redis, 1, self::WINDOW);
        $other = $this->voter . '-other';

        try {
            self::assertTrue($limiter->attempt($this->voter, 1_000)->allowed);
            self::assertFalse($limiter->attempt($this->voter, 1_001)->allowed);
            self::assertTrue($limiter->attempt($other, 1_002)->allowed);
        } finally {
            $this->redis->get()->del(VoteLimiter::KEY_PREFIX . $other);
        }
    }

    public function testTheKeyExpiresWithTheWindow(): void
    {
        $limiter = new VoteLimiter($this->redis, 3, self::WINDOW);
        $limiter->attempt($this->voter, 1_000);

        $ttl = $this->redis->get()->pttl(VoteLimiter::KEY_PREFIX . $this->voter);
        self::assertGreaterThan(0, $ttl);
        self::assertLessThanOrEqual(self::WINDOW, $ttl);
    }
}
