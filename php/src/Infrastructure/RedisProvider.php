<?php

declare(strict_types=1);

namespace App\Infrastructure;

/**
 * Opens the Redis connection on first use, so a Redis outage surfaces as a
 * \RedisException inside the code that handles it (the page still renders
 * with an error message) instead of failing before routing.
 */
final class RedisProvider
{
    private ?\Redis $redis = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $password,
    ) {}

    public static function fromEnvironment(): self
    {
        return new self(
            self::env('REDIS_HOST'),
            (int) self::env('REDIS_PORT'),
            self::env('REDIS_PASSWORD'),
        );
    }

    public function get(): \Redis
    {
        if ($this->redis === null) {
            $redis = new \Redis();
            $redis->connect($this->host, $this->port, 2.0);
            $redis->auth([$this->password]);
            $this->redis = $redis;
        }

        return $this->redis;
    }

    private static function env(string $name): string
    {
        $value = getenv($name);
        if (!\is_string($value) || $value === '') {
            throw new \RuntimeException("Environment variable {$name} is not set");
        }

        return $value;
    }
}
