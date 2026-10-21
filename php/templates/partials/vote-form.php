<?php

declare(strict_types=1);

use function App\View\e;

/**
 * @var string $name
 * @var string $csrfToken
 * @var string $formClass
 * @var string $buttonClass extra classes for the button
 */
?>
<form method="post" action="/" class="<?= e($formClass) ?>">
  <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
  <input type="hidden" name="mode" value="increment">
  <input type="hidden" name="text" value="<?= e($name) ?>">
  <button type="submit" class="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-pink px-4 py-2 font-bold tracking-wider text-white shadow-[0_6px_18px_-6px_rgb(255_61_139/0.8)] transition hover:-translate-y-0.5 hover:bg-[#ff5a9d] active:translate-y-px active:scale-95 disabled:pointer-events-none disabled:opacity-60 sm:px-5 <?= e($buttonClass) ?>">
    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7.5-4.6-9.5-9.2C1 8.2 3.4 4.5 7 4.5c2.1 0 3.6 1.1 5 2.9 1.4-1.8 2.9-2.9 5-2.9 3.6 0 6 3.7 4.5 7.3C19.5 16.4 12 21 12 21z"/></svg>投票
  </button>
</form>
