<?php

declare(strict_types=1);

namespace App\Http;

/** The parts of the incoming request the app reads. */
final readonly class Request
{
    /**
     * @param array<string, mixed> $post
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $server
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $post = [],
        public array $cookies = [],
        public array $server = [],
    ) {}

    public static function fromGlobals(): self
    {
        /** @var array<string, mixed> $server */
        $server = $_SERVER;
        $uri = \is_string($server['REQUEST_URI'] ?? null) ? $server['REQUEST_URI'] : '/';
        $method = \is_string($server['REQUEST_METHOD'] ?? null) ? $server['REQUEST_METHOD'] : 'GET';

        /** @var array<string, mixed> $post */
        $post = $_POST;
        /** @var array<string, mixed> $cookies */
        $cookies = $_COOKIE;

        return new self(strtoupper($method), (string) parse_url($uri, \PHP_URL_PATH), $post, $cookies, $server);
    }

    /** A form field as a string, or null when it is missing or an array. */
    public function input(string $name): ?string
    {
        $value = $this->post[$name] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function cookie(string $name): ?string
    {
        $value = $this->cookies[$name] ?? null;

        return \is_string($value) ? $value : null;
    }

    public function header(string $name): string
    {
        $value = $this->server['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? '';

        return \is_string($value) ? $value : '';
    }

    /** The page's script asks for JSON; a plain form post wants HTML. */
    public function wantsJson(): bool
    {
        return str_contains($this->header('Accept'), 'application/json');
    }
}
