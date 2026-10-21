<?php

declare(strict_types=1);

use App\Domain\Standing;
use App\View\View;

use function App\View\e;

/**
 * 4th place and below.
 *
 * @var View $view
 * @var Standing $member
 * @var float|int $share bar width in % of the leader's votes
 * @var int|null $delay fade-in delay in ms
 * @var string $csrfToken
 */
$delayStyle = $delay === null ? '' : "animation-delay: {$delay}ms;";
?>
<li id="<?= e($member->anchor()) ?>" data-name="<?= e($member->name) ?>" data-votes="<?= $member->votes ?>"
    class="group relative grid scroll-mt-28 grid-cols-[3.4rem_minmax(0,1fr)_auto] items-center gap-x-2.5 gap-y-0.5 rounded-2xl border border-line bg-panel py-3 pr-3 pl-4 [--accent:var(--color-pink)] transition-colors hover:border-white/15 hover:bg-panel-2 sm:col-span-2 sm:grid-cols-[4.5rem_minmax(0,1fr)_auto_auto] sm:gap-x-5 <?= $delay === null ? '' : 'motion-safe:animate-rise' ?> target:border-pink motion-safe:target:animate-voted"
    style="<?= $delayStyle ?>">
  <p class="row-span-2 font-display text-lg leading-none whitespace-nowrap text-muted tabular-nums sm:row-span-1 sm:text-2xl"><?= $member->rank ?><small class="ml-0.5 font-sans text-[0.65rem] font-bold">位</small></p>
  <div class="col-start-2 min-w-0">
    <p class="truncate font-bold sm:text-[1.05rem]"><?= e($member->name) ?></p>
    <span class="mt-1.5 block h-1 overflow-hidden rounded-full bg-white/5" aria-hidden="true">
      <span data-bar class="block h-full w-(--share) origin-left rounded-full bg-linear-to-r from-pink-deep to-(--accent) transition-[width] duration-700 motion-safe:animate-grow" style="--share: <?= $share ?>%; <?= $delayStyle ?>"></span>
    </span>
  </div>
  <p class="relative col-start-2 row-start-2 font-display text-base tabular-nums whitespace-nowrap sm:col-start-3 sm:row-start-1 sm:text-right sm:text-xl"><span data-count><?= number_format($member->votes) ?></span><small class="ml-1 font-sans text-[0.65rem] font-bold text-muted">票</small></p>
  <?= $view->render('partials/vote-form', ['name' => $member->name, 'csrfToken' => $csrfToken, 'formClass' => 'col-start-3 row-span-2 row-start-1 sm:col-start-4 sm:row-span-1', 'buttonClass' => '']) ?>
</li>
