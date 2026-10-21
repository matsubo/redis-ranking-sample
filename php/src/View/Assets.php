<?php

declare(strict_types=1);

namespace App\View;

/**
 * URLs of files under public/ with the file's mtime as ?v=, so browsers and
 * crawlers (X caches cards for days) fetch a changed file instead of a cached
 * copy.
 */
final readonly class Assets
{
    public function __construct(
        private string $publicDir,
        private string $baseUrl,
    ) {}

    public function url(string $path): string
    {
        $mtime = @filemtime($this->publicDir . $path);

        return $path . '?v=' . ($mtime === false ? 0 : $mtime);
    }

    public function absoluteUrl(string $path): string
    {
        return $this->baseUrl . $this->url($path);
    }
}
