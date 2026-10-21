<?php

declare(strict_types=1);

namespace App\Http;

/**
 * The address a vote is counted against.
 *
 * Public traffic reaches the pods only through the Cloudflare tunnel, and
 * Cloudflare overwrites Cf-Connecting-Ip with the visitor's address
 * (X-Forwarded-For can be extended by the client). REMOTE_ADDR is the
 * in-cluster proxy in production and the client only in the local preview.
 * An IPv6 host can use any address in its /64, so IPv6 is counted per /64.
 */
final class Voter
{
    /** @param array<string, mixed> $server */
    public static function fromServer(array $server): string
    {
        $ip = self::validIp($server['HTTP_CF_CONNECTING_IP'] ?? null)
            ?? self::validIp($server['REMOTE_ADDR'] ?? null)
            ?? 'unknown';

        if (filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6) !== false) {
            $packed = (string) inet_pton($ip);

            return (string) inet_ntop(substr($packed, 0, 8) . str_repeat("\0", 8)) . '/64';
        }

        return $ip;
    }

    private static function validIp(mixed $value): ?string
    {
        return \is_string($value) && filter_var($value, \FILTER_VALIDATE_IP) !== false ? $value : null;
    }
}
