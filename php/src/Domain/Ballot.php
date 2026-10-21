<?php

declare(strict_types=1);

namespace App\Domain;

use App\Infrastructure\RedisProvider;
use Matsubo\Redis\Ranking;

/**
 * The members and their votes, kept in one redis/ranking set.
 *
 * Scores are stored negated (a vote adds -1) so that Ranking::getRange() and
 * Ranking::getRank(), which order ascending, put the leader first.
 */
final readonly class Ballot
{
    public const string KEY = 'akb';

    public function __construct(private RedisProvider $redis) {}

    /** @return list<Standing> leader first */
    public function standings(): array
    {
        $standings = [];
        $rank = 0;
        foreach ($this->ranking()->getRangeWithScores() as $name => $score) {
            // Numeric member names come back as int keys.
            $standings[] = new Standing(++$rank, (string) $name, self::votes($score));
        }

        return $standings;
    }

    public function has(string $name): bool
    {
        return $this->ranking()->getScore($name) !== null;
    }

    /** Adds one vote for an existing member and returns the new standing. */
    public function vote(string $name): Standing
    {
        $ranking = $this->ranking();
        $score = $ranking->incrementScore($name, -1);
        $rank = $ranking->getRank($name) ?? throw new \RuntimeException("{$name} vanished while voting");

        return new Standing($rank + 1, $name, self::votes($score));
    }

    /**
     * Registers the given members with 0 votes; members that already exist keep
     * their votes.
     *
     * @param iterable<string> $names
     *
     * @return list<string> the names that were added
     */
    public function addMissing(iterable $names): array
    {
        $ranking = $this->ranking();
        $known = array_fill_keys($ranking->getRange(), true);
        $added = [];
        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '' || isset($known[$name])) {
                continue;
            }
            $ranking->setUserScore($name, 0);
            $known[$name] = true;
            $added[] = $name;
        }

        return $added;
    }

    private function ranking(): Ranking
    {
        return new Ranking(self::KEY, $this->redis->get());
    }

    private static function votes(float $score): int
    {
        return (int) abs($score);
    }
}
