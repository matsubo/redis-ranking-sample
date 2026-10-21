<?php

declare(strict_types=1);

namespace App\Http;

final readonly class Response
{
    /** @param list<array{string, string}> $headers name/value pairs; a name may repeat (Set-Cookie) */
    public function __construct(
        public string $body = '',
        public int $status = 200,
        public array $headers = [],
    ) {}

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, [['Content-Type', 'text/html; charset=utf-8']]);
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR),
            $status,
            [['Content-Type', 'application/json; charset=utf-8'], ['Cache-Control', 'no-store']],
        );
    }

    public static function redirect(string $location, int $status = 303): self
    {
        return new self('', $status, [['Location', $location]]);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->body, $this->status, [...$this->headers, [$name, $value]]);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as [$name, $value]) {
            header("{$name}: {$value}", false);
        }
        echo $this->body;
    }
}
