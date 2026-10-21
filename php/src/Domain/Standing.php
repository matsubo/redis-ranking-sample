<?php

declare(strict_types=1);

namespace App\Domain;

/** One member's place in the ranking. */
final readonly class Standing
{
    public function __construct(
        public int $rank,
        public string $name,
        public int $votes,
    ) {}

    /** Stable element id for the member's row; the no-JS vote redirect lands on it. */
    public function anchor(): string
    {
        return 'm-' . hash('xxh3', $this->name);
    }
}
