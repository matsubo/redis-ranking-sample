<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Standing;

/** Result of a vote request, before it is turned into JSON, a redirect or a page. */
final readonly class VoteOutcome
{
    private function __construct(
        public int $status,
        public ?Standing $standing = null,
        public string $error = '',
        /** Seconds for the Retry-After header of a 429. */
        public ?int $retryAfter = null,
    ) {}

    public static function counted(Standing $standing): self
    {
        return new self(200, $standing);
    }

    public static function failed(int $status, string $error, ?int $retryAfter = null): self
    {
        return new self($status, null, $error, $retryAfter);
    }
}
