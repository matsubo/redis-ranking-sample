<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\Voter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class VoterTest extends TestCase
{
    /** @param array<string, mixed> $server */
    #[DataProvider('servers')]
    public function testIdentifiesTheVoter(array $server, string $expected): void
    {
        self::assertSame($expected, Voter::fromServer($server));
    }

    /** @return iterable<string, array{array<string, mixed>, string}> */
    public static function servers(): iterable
    {
        yield 'Cloudflare visitor address wins over the proxy' => [
            ['HTTP_CF_CONNECTING_IP' => '203.0.113.7', 'REMOTE_ADDR' => '10.42.0.5'],
            '203.0.113.7',
        ];
        yield 'X-Forwarded-For is ignored' => [
            ['HTTP_X_FORWARDED_FOR' => '198.51.100.1', 'REMOTE_ADDR' => '10.42.0.5'],
            '10.42.0.5',
        ];
        yield 'a malformed header falls back to REMOTE_ADDR' => [
            ['HTTP_CF_CONNECTING_IP' => '203.0.113.7, 1.1.1.1', 'REMOTE_ADDR' => '10.42.0.5'],
            '10.42.0.5',
        ];
        yield 'IPv6 addresses in one /64 share a bucket' => [
            ['HTTP_CF_CONNECTING_IP' => '2001:db8:1:2:aaaa:bbbb:cccc:dddd'],
            '2001:db8:1:2::/64',
        ];
        yield 'IPv6 /64 is normalised' => [
            ['HTTP_CF_CONNECTING_IP' => '2001:0DB8:0001:0002::1'],
            '2001:db8:1:2::/64',
        ];
        yield 'no address at all' => [[], 'unknown'];
    }
}
