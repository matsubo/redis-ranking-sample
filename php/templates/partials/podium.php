<?php

declare(strict_types=1);

use App\Domain\Standing;
use App\View\View;

use function App\View\e;

/**
 * 1st–3rd place card.
 *
 * @var View $view
 * @var Standing $member
 * @var float|int $share bar width in % of the leader's votes
 * @var int|null $delay fade-in delay in ms
 * @var string $csrfToken
 */
[$label, $tone] = match ($member->rank) {
    1 => ['CENTER', 'sm:col-span-2 border-gold/45 shadow-[0_30px_60px_-30px_rgb(244_194_79/0.4)] [--accent:var(--color-gold)]'],
    2 => ['2ND PLACE', 'border-silver/30 [--accent:var(--color-silver)]'],
    default => ['3RD PLACE', 'border-bronze/35 [--accent:var(--color-bronze)]'],
};
$first = $member->rank === 1;
$delayStyle = $delay === null ? '' : "animation-delay: {$delay}ms;";
?>
<li id="<?= e($member->anchor()) ?>" data-name="<?= e($member->name) ?>" data-votes="<?= $member->votes ?>"
    class="group relative scroll-mt-28 overflow-hidden rounded-3xl border bg-panel podium-bg p-6 sm:p-8 <?= $tone ?> <?= $delay === null ? '' : 'motion-safe:animate-rise' ?> target:border-pink motion-safe:target:animate-voted"
    style="<?= $delayStyle ?>">
  <div class="flex items-start justify-between gap-4">
    <p class="font-display leading-none text-(--accent) <?= $first ? 'text-7xl sm:text-8xl' : 'text-5xl sm:text-6xl' ?>"><?= $member->rank ?><small class="ml-1 font-sans text-base font-bold">位</small></p>
    <span class="rounded-full border border-(--accent)/40 px-3 py-1 text-[0.65rem] font-bold tracking-[0.2em] text-(--accent)"><?= $label ?></span>
  </div>
  <p class="mt-5 font-display break-words <?= $first ? 'text-4xl sm:text-6xl' : 'text-2xl sm:text-3xl' ?>"><?= e($member->name) ?></p>
  <span class="mt-4 block h-1.5 overflow-hidden rounded-full bg-white/5" aria-hidden="true">
    <span data-bar class="block h-full w-(--share) origin-left rounded-full bg-linear-to-r from-pink-deep to-(--accent) transition-[width] duration-700 motion-safe:animate-grow" style="--share: <?= $share ?>%; <?= $delayStyle ?>"></span>
  </span>
  <div class="mt-5 flex items-end justify-between gap-4">
    <p class="relative font-display leading-none tabular-nums <?= $first ? 'text-5xl sm:text-6xl' : 'text-4xl' ?>"><span data-count><?= number_format($member->votes) ?></span><small class="ml-1 font-sans text-sm font-bold text-muted">票</small></p>
    <?= $view->render('partials/vote-form', ['name' => $member->name, 'csrfToken' => $csrfToken, 'formClass' => '', 'buttonClass' => 'sm:py-3 sm:px-6']) ?>
  </div>
</li>
