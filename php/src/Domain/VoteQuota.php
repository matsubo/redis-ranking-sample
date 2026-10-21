<?php

declare(strict_types=1);

namespace App\Domain;

/** Outcome of VoteLimiter::attempt(). */
final readonly class VoteQuota
{
    private function __construct(
        public bool $allowed,
        /** Milliseconds until the voter may vote again; 0 when allowed. */
        public int $retryAfterMs,
    ) {}

    public static function granted(): self
    {
        return new self(true, 0);
    }

    public static function denied(int $retryAfterMs): self
    {
        return new self(false, max(1, $retryAfterMs));
    }
}
