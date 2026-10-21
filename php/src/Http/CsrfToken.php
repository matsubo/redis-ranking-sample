<?php

declare(strict_types=1);

namespace App\Http;

/**
 * CSRF token without server-side state (double-submit cookie): the token sits
 * in a cookie and in the vote form, and a vote must carry both. Any pod can
 * check it, so the replicas share no session and a vote still goes through
 * when the pod that rendered the form has been replaced in the meantime.
 *
 * The __Host- prefix makes browsers refuse a copy planted by another
 * *.teraren.com host (it requires Secure, Path=/ and no Domain), and
 * SameSite=Lax keeps the cookie off cross-site POSTs.
 */
final readonly class CsrfToken
{
    public const string COOKIE = '__Host-ranking_csrf';

    private function __construct(
        public string $value,
        /** True when the request had no valid cookie and one must be set. */
        public bool $fresh,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $cookie = $request->cookie(self::COOKIE);
        if ($cookie !== null && preg_match('/\A[0-9a-f]{64}\z/', $cookie) === 1) {
            return new self($cookie, false);
        }

        return new self(bin2hex(random_bytes(32)), true);
    }

    public function matches(?string $submitted): bool
    {
        return !$this->fresh && $submitted !== null && hash_equals($this->value, $submitted);
    }

    /** Adds the Set-Cookie header when the token is new. */
    public function attachTo(Response $response): Response
    {
        if (!$this->fresh) {
            return $response;
        }

        return $response->withHeader(
            'Set-Cookie',
            self::COOKIE . '=' . $this->value . '; Path=/; Secure; HttpOnly; SameSite=Lax',
        );
    }
}
