<?php

declare(strict_types=1);

use App\Domain\Standing;
use App\View\View;

use function App\View\e;

/**
 * @var View $view
 * @var list<Standing> $members
 * @var int $totalVotes
 * @var string $csrfToken
 * @var string $error
 * @var string $fetchError
 */
$topVotes = $members[0]->votes ?? 0;
$leader = $members[0]->name ?? '—';
?>
<?php if ($members !== []): ?>
<div class="overflow-hidden border-y border-black/20 bg-pink text-sm font-bold whitespace-nowrap text-white" aria-hidden="true">
  <div class="flex w-max motion-safe:animate-ticker">
    <?php for ($copy = 0; $copy < 2; $copy++): ?>
    <span class="py-2">
      <?php foreach (array_slice($members, 0, 5) as $m): ?>
      <?= $m->rank ?>位<b class="mx-1.5 font-display font-normal"><?= e($m->name) ?></b><?= number_format($m->votes) ?>票<i class="mx-6 not-italic opacity-60">●</i>
      <?php endforeach; ?>
      総得票数<b class="mx-1.5 font-display font-normal"><?= number_format($totalVotes) ?></b>票<i class="mx-6 not-italic opacity-60">●</i>
      エントリー<b class="mx-1.5 font-display font-normal"><?= count($members) ?></b>名<i class="mx-6 not-italic opacity-60">●</i>
    </span>
    <?php endfor; ?>
  </div>
</div>
<?php endif; ?>

<main class="mx-auto w-[min(1080px,100%-2.5rem)]">

  <section class="pt-12 pb-8 sm:pt-24 sm:pb-12">
    <p class="mb-4 text-xs font-bold tracking-[0.3em] text-gold motion-safe:animate-rise">UNOFFICIAL GENERAL ELECTION</p>
    <h1 class="font-display text-[clamp(2.6rem,9vw,6.2rem)] leading-[1.05] font-normal tracking-tight motion-safe:animate-rise [animation-delay:80ms]">
      AKB勝手に<span class="block text-pink drop-shadow-[0_0_28px_rgb(255_61_139/0.45)]">総選挙</span>
    </h1>
    <p class="mt-6 max-w-[34em] text-lg text-muted motion-safe:animate-rise [animation-delay:160ms]">
      応援したいメンバーに投票しよう。ワンタップで1票、何度でも。順位はリアルタイムで更新されます。
    </p>

    <dl class="mt-10 grid grid-cols-2 gap-px overflow-hidden rounded-3xl border border-line bg-line sm:grid-cols-3 motion-safe:animate-rise [animation-delay:240ms]">
      <div class="bg-panel/85 px-5 py-4">
        <dt class="text-xs tracking-[0.15em] text-muted">エントリー</dt>
        <dd class="mt-1 font-display text-2xl tabular-nums sm:text-3xl"><?= number_format(count($members)) ?><small class="ml-1 font-sans text-xs font-bold text-muted">名</small></dd>
      </div>
      <div class="bg-panel/85 px-5 py-4">
        <dt class="text-xs tracking-[0.15em] text-muted">総得票数</dt>
        <dd class="mt-1 font-display text-2xl tabular-nums sm:text-3xl"><span data-total><?= number_format($totalVotes) ?></span><small class="ml-1 font-sans text-xs font-bold text-muted">票</small></dd>
      </div>
      <div class="col-span-2 bg-panel/85 px-5 py-4 sm:col-span-1">
        <dt class="text-xs tracking-[0.15em] text-muted">現在のセンター</dt>
        <dd class="mt-1 font-display text-2xl break-words text-gold sm:text-3xl"><?= e($leader) ?></dd>
      </div>
    </dl>
  </section>

  <?php if ($error !== ''): ?>
  <div role="alert" class="my-6 rounded-xl border border-l-[6px] border-pink/50 bg-pink/10 px-5 py-4 font-bold"><?= e($error) ?></div>
  <?php endif; ?>

  <?= $view->render('partials/ad', ['spacing' => 'my-8']) ?>

  <section aria-labelledby="board-title">
    <div class="mt-12 mb-5 flex flex-wrap items-end justify-between gap-4">
      <h2 id="board-title" class="font-display text-3xl font-normal sm:text-4xl">全順位</h2>
      <label id="search-box" class="relative w-full sm:w-80" hidden>
        <span class="sr-only">メンバーを検索</span>
        <svg aria-hidden="true" class="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input id="search" type="search" placeholder="メンバーを検索" autocomplete="off"
               class="w-full rounded-full border border-line bg-panel py-3 pr-4 pl-11 text-fg placeholder:text-muted focus:border-pink focus:ring-4 focus:ring-pink/20 focus:outline-none">
      </label>
    </div>

    <?php if ($fetchError !== ''): ?>
    <div role="alert" class="rounded-xl border border-l-[6px] border-pink/50 bg-pink/10 px-5 py-4 font-bold"><?= e($fetchError) ?></div>
    <?php else: ?>
    <ol id="board" class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
      <?php foreach ($members as $i => $m): ?>
      <?= $view->render($m->rank <= 3 ? 'partials/podium' : 'partials/row', [
          'member' => $m,
          'share' => $topVotes > 0 ? round($m->votes / $topVotes * 100, 2) : 0,
          // The first 24 entries fade in one after another.
          'delay' => $i < 24 ? 300 + $i * 35 : null,
          'csrfToken' => $csrfToken,
      ]) ?>
      <?php endforeach; ?>
    </ol>
    <p id="no-hit" class="mt-4 rounded-3xl border border-dashed border-line p-8 text-center text-muted" hidden>該当するメンバーがいません。</p>
    <?php endif; ?>
  </section>

  <?= $view->render('partials/ad', ['spacing' => 'my-12']) ?>

  <div class="mt-16 mb-12 grid gap-4 sm:grid-cols-2">
    <section class="rounded-3xl border border-line bg-panel p-6">
      <h2 class="mb-2 font-display text-lg font-normal">メンバーを追加したい</h2>
      <p class="text-muted">こちらのファイルに pull request を送ってください。<br>
        <a class="break-all text-pink underline-offset-4 hover:underline" href="https://github.com/matsubo/redis-ranking-sample/blob/master/php/member.txt">github.com/matsubo/redis-ranking-sample/php/member.txt</a></p>
    </section>
    <section class="rounded-3xl border border-line bg-panel p-6">
      <h2 class="mb-2 font-display text-lg font-normal">このサイトについて</h2>
      <p class="text-muted"><a class="text-pink underline-offset-4 hover:underline" href="https://matsu.teraren.com/blog/2013/04/28/redis-ranking-api/">Redis-Ranking</a> を使って構築しています。</p>
    </section>
  </div>
</main>
