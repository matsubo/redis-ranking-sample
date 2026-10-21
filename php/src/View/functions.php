<?php

declare(strict_types=1);

namespace App\View;

/** HTML-escapes a value for text and quoted attributes. */
function e(string|int|float $value): string
{
    return htmlspecialchars((string) $value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
}
