<?php

require_once '/var/www/vendor/autoload.php';

use Matsubo\Redis\Ranking;

// Scores are stored negated (a vote adds -1) so that ZRANGE returns the
// leader first.

function ranking(): Ranking
{
    static $ranking = null;
    if ($ranking === null) {
        $redis = new \Redis();
        $redis->connect(getenv('REDIS_HOST'), (int)getenv('REDIS_PORT'));
        $redis->auth([getenv('REDIS_PASSWORD')]);
        $ranking = new Ranking('akb', $redis);
    }
    return $ranking;
}

function e(string|int|float $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Stable element id for a member; the no-JS vote redirect lands on it. */
function memberId(string $name): string
{
    return 'm-' . hash('xxh3', $name);
}

// CSRF token without server-side state (double-submit cookie): the token sits
// in a cookie and in the vote form, and a vote must carry both. Any pod can
// check it, so the replicas share no session and a vote still goes through
// when the pod that rendered the form has been replaced in the meantime.
// The __Host- prefix makes browsers refuse a copy planted by another
// *.teraren.com host (it requires Secure, Path=/ and no Domain), and
// SameSite=Lax keeps the cookie off cross-site POSTs.
$csrfCookie = '__Host-ranking_csrf';
$csrfToken = $_COOKIE[$csrfCookie] ?? '';
if (!is_string($csrfToken) || !preg_match('/\A[0-9a-f]{64}\z/', $csrfToken)) {
    $csrfToken = bin2hex(random_bytes(32));
    setcookie($csrfCookie, $csrfToken, [
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Vote (POST). The page's script sends Accept: application/json and gets the
// new count back; a plain form post is redirected back to the member's row.
$voteError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['mode'] ?? '') === 'increment') {
    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    $name = $_POST['text'] ?? '';
    $status = 200;
    $result = null;

    if (!is_string($name) || !is_string($_POST['csrf_token'] ?? null) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
        $status = 403;
        $voteError = '不正なリクエストです。ページを再読み込みしてからもう一度お試しください。';
    } else {
        try {
            if (ranking()->getScore($name) === false) {
                $status = 404;
                $voteError = 'そのメンバーは見つかりませんでした。';
            } else {
                $score = ranking()->incrementScore($name, -1);
                $result = [
                    'name' => $name,
                    'votes' => (int)abs($score),
                    'rank' => ranking()->getRank($name) + 1,
                ];
            }
        } catch (\Exception $e) {
            $status = 500;
            $voteError = '投票処理に失敗しました。';
        }
    }

    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($result ? ['ok' => true] + $result : ['ok' => false, 'error' => $voteError], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($result) {
        // Post-Redirect-Get
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '#' . memberId($name), true, 303);
        exit;
    }
    http_response_code($status);
}

// Ranking data
$members = [];
$fetchError = '';
try {
    $rank = 0;
    foreach (ranking()->getRange(0, -1, true) as $name => $score) {
        $members[] = ['rank' => ++$rank, 'name' => (string)$name, 'votes' => (int)abs($score)];
    }
} catch (\Exception $e) {
    $fetchError = 'ランキングデータの取得に失敗しました。';
}

$totalVotes = array_sum(array_column($members, 'votes'));
$topVotes = $members[0]['votes'] ?? 0;
$leader = $members[0]['name'] ?? '—';

$podium = [
    1 => ['label' => 'CENTER', 'class' => 'sm:col-span-2 border-gold/45 shadow-[0_30px_60px_-30px_rgb(244_194_79/0.4)] [--accent:var(--color-gold)]'],
    2 => ['label' => '2ND PLACE', 'class' => 'border-silver/30 [--accent:var(--color-silver)]'],
    3 => ['label' => '3RD PLACE', 'class' => 'border-bronze/35 [--accent:var(--color-bronze)]'],
];

$voteButton = 'inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-pink px-4 py-2 font-bold tracking-wider text-white shadow-[0_6px_18px_-6px_rgb(255_61_139/0.8)] transition hover:-translate-y-0.5 hover:bg-[#ff5a9d] active:translate-y-px active:scale-95 disabled:pointer-events-none disabled:opacity-60 sm:px-5';
$voteIcon = '<svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7.5-4.6-9.5-9.2C1 8.2 3.4 4.5 7 4.5c2.1 0 3.6 1.1 5 2.9 1.4-1.8 2.9-2.9 5-2.9 3.6 0 6 3.7 4.5 7.3C19.5 16.4 12 21 12 21z"/></svg>';

$cssVersion = @filemtime(__DIR__ . '/assets/css/app.css') ?: 0;
// The version query gives crawlers (X caches cards for days) a new URL whenever the image changes.
$ogImage = 'https://ranking.teraren.com/assets/og.jpg?v=' . (@filemtime(__DIR__ . '/assets/og.jpg') ?: 0);

?>
<!DOCTYPE html>
<html lang="ja" class="motion-safe:scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AKB勝手に総選挙 — 開票速報</title>
  <meta name="description" content="AKBグループのメンバーに誰でも何度でも投票できる、勝手に総選挙のリアルタイムランキング。">
  <meta name="author" content="Yuki Matsukura">
  <meta name="theme-color" content="#0d0a12">
  <meta property="og:title" content="AKB勝手に総選挙">
  <meta property="og:description" content="応援したいメンバーに投票しよう。リアルタイム開票中。">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://ranking.teraren.com/">
  <meta property="og:site_name" content="AKB勝手に総選挙">
  <meta property="og:locale" content="ja_JP">
  <meta property="og:image" content="<?= e($ogImage) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="AKB勝手に総選挙 — 開票速報">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:creator" content="@matsubokkuri">

  <link rel="icon" href="/favicon.ico" sizes="32x32">
  <link rel="icon" href="/assets/ico/icon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/assets/ico/apple-touch-icon.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Zen+Kaku+Gothic+New:wght@400;700&display=swap">
  <link rel="stylesheet" href="/assets/css/app.css?v=<?= $cssVersion ?>">

  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-WNH3R2');</script>
  <!-- End Google Tag Manager -->
</head>

<body class="min-h-screen font-sans leading-relaxed text-fg antialiased">
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNH3R2" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>

<header class="sticky top-0 z-50 border-b border-line bg-ink/70 backdrop-blur-lg backdrop-saturate-150">
  <div class="mx-auto flex h-16 w-[min(1080px,100%-2.5rem)] items-center justify-between gap-4">
    <a href="/" class="flex items-center gap-2.5 font-display text-lg tracking-wide text-fg no-underline">
      <img src="/assets/ico/icon.svg" alt="" class="size-8" width="32" height="32">
      <span>AKB<span class="text-pink">勝手に</span>総選挙</span>
    </a>
    <span class="inline-flex items-center gap-2 rounded-full border border-pink/50 px-3 py-1 text-xs font-bold tracking-[0.15em] text-pink">
      <span class="size-2 rounded-full bg-pink motion-safe:animate-live"></span>開票速報
    </span>
  </div>
</header>

<?php if ($members): ?>
<div class="overflow-hidden border-y border-black/20 bg-pink text-sm font-bold whitespace-nowrap text-white" aria-hidden="true">
  <div class="flex w-max motion-safe:animate-ticker">
    <?php for ($copy = 0; $copy < 2; $copy++): ?>
    <span class="py-2">
      <?php foreach (array_slice($members, 0, 5) as $m): ?>
      <?= $m['rank'] ?>位<b class="mx-1.5 font-display font-normal"><?= e($m['name']) ?></b><?= number_format($m['votes']) ?>票<i class="mx-6 not-italic opacity-60">●</i>
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

  <?php if ($voteError): ?>
  <div role="alert" class="my-6 rounded-xl border border-l-[6px] border-pink/50 bg-pink/10 px-5 py-4 font-bold"><?= e($voteError) ?></div>
  <?php endif; ?>

  <aside class="my-8 rounded-3xl border border-dashed border-line p-3 before:mb-1 before:block before:text-[0.65rem] before:tracking-[0.25em] before:text-muted before:content-['SPONSORED']">
    <?php include __DIR__ . '/ad.inc.php'; ?>
  </aside>

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

    <?php if ($fetchError): ?>
    <div role="alert" class="rounded-xl border border-l-[6px] border-pink/50 bg-pink/10 px-5 py-4 font-bold"><?= e($fetchError) ?></div>
    <?php else: ?>
    <ol id="board" class="grid grid-cols-1 gap-2.5 sm:grid-cols-2" style="--top: <?= $topVotes ?>">
      <?php foreach ($members as $i => $m): ?>
      <?php
        $share = $topVotes > 0 ? round($m['votes'] / $topVotes * 100, 2) : 0;
        $reveal = $i < 24 ? 'motion-safe:animate-rise' : '';
        $delay = $i < 24 ? 'animation-delay: ' . (300 + $i * 35) . 'ms;' : '';
        $form = '<form method="post" action="?mode=increment" class="%s">'
            . '<input type="hidden" name="csrf_token" value="' . e($csrfToken) . '">'
            . '<input type="hidden" name="mode" value="increment">'
            . '<input type="hidden" name="text" value="' . e($m['name']) . '">'
            . '<button type="submit" class="%s">' . $voteIcon . '投票</button></form>';
      ?>
      <?php if (isset($podium[$m['rank']])): $p = $podium[$m['rank']]; $first = $m['rank'] === 1; ?>
      <li id="<?= memberId($m['name']) ?>" data-name="<?= e($m['name']) ?>" data-votes="<?= $m['votes'] ?>"
          class="group relative scroll-mt-28 overflow-hidden rounded-3xl border bg-panel podium-bg p-6 sm:p-8 <?= $p['class'] ?> <?= $reveal ?> target:border-pink motion-safe:target:animate-voted"
          style="<?= $delay ?>">
        <div class="flex items-start justify-between gap-4">
          <p class="font-display leading-none text-(--accent) <?= $first ? 'text-7xl sm:text-8xl' : 'text-5xl sm:text-6xl' ?>"><?= $m['rank'] ?><small class="ml-1 font-sans text-base font-bold">位</small></p>
          <span class="rounded-full border border-(--accent)/40 px-3 py-1 text-[0.65rem] font-bold tracking-[0.2em] text-(--accent)"><?= $p['label'] ?></span>
        </div>
        <p class="mt-5 font-display break-words <?= $first ? 'text-4xl sm:text-6xl' : 'text-2xl sm:text-3xl' ?>"><?= e($m['name']) ?></p>
        <span class="mt-4 block h-1.5 overflow-hidden rounded-full bg-white/5" aria-hidden="true">
          <span data-bar class="block h-full w-(--share) origin-left rounded-full bg-linear-to-r from-pink-deep to-(--accent) transition-[width] duration-700 motion-safe:animate-grow" style="--share: <?= $share ?>%; <?= $delay ?>"></span>
        </span>
        <div class="mt-5 flex items-end justify-between gap-4">
          <p class="relative font-display leading-none tabular-nums <?= $first ? 'text-5xl sm:text-6xl' : 'text-4xl' ?>"><span data-count><?= number_format($m['votes']) ?></span><small class="ml-1 font-sans text-sm font-bold text-muted">票</small></p>
          <?= sprintf($form, '', $voteButton . ' sm:py-3 sm:px-6') ?>
        </div>
      </li>
      <?php else: ?>
      <li id="<?= memberId($m['name']) ?>" data-name="<?= e($m['name']) ?>" data-votes="<?= $m['votes'] ?>"
          class="group relative grid scroll-mt-28 grid-cols-[3.4rem_minmax(0,1fr)_auto] items-center gap-x-2.5 gap-y-0.5 rounded-2xl border border-line bg-panel py-3 pr-3 pl-4 [--accent:var(--color-pink)] transition-colors hover:border-white/15 hover:bg-panel-2 sm:col-span-2 sm:grid-cols-[4.5rem_minmax(0,1fr)_auto_auto] sm:gap-x-5 <?= $reveal ?> target:border-pink motion-safe:target:animate-voted"
          style="<?= $delay ?>">
        <p class="row-span-2 font-display text-lg leading-none whitespace-nowrap text-muted tabular-nums sm:row-span-1 sm:text-2xl"><?= $m['rank'] ?><small class="ml-0.5 font-sans text-[0.65rem] font-bold">位</small></p>
        <div class="col-start-2 min-w-0">
          <p class="truncate font-bold sm:text-[1.05rem]"><?= e($m['name']) ?></p>
          <span class="mt-1.5 block h-1 overflow-hidden rounded-full bg-white/5" aria-hidden="true">
            <span data-bar class="block h-full w-(--share) origin-left rounded-full bg-linear-to-r from-pink-deep to-(--accent) transition-[width] duration-700 motion-safe:animate-grow" style="--share: <?= $share ?>%; <?= $delay ?>"></span>
          </span>
        </div>
        <p class="relative col-start-2 row-start-2 font-display text-base tabular-nums whitespace-nowrap sm:col-start-3 sm:row-start-1 sm:text-right sm:text-xl"><span data-count><?= number_format($m['votes']) ?></span><small class="ml-1 font-sans text-[0.65rem] font-bold text-muted">票</small></p>
        <?= sprintf($form, 'col-start-3 row-span-2 row-start-1 sm:col-start-4 sm:row-span-1', $voteButton) ?>
      </li>
      <?php endif; ?>
      <?php endforeach; ?>
    </ol>
    <p id="no-hit" class="mt-4 rounded-3xl border border-dashed border-line p-8 text-center text-muted" hidden>該当するメンバーがいません。</p>
    <?php endif; ?>
  </section>

  <aside class="my-12 rounded-3xl border border-dashed border-line p-3 before:mb-1 before:block before:text-[0.65rem] before:tracking-[0.25em] before:text-muted before:content-['SPONSORED']">
    <?php include __DIR__ . '/ad.inc.php'; ?>
  </aside>

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

<footer class="border-t border-line py-8 text-sm text-muted">
  <div class="mx-auto flex w-[min(1080px,100%-2.5rem)] flex-wrap justify-between gap-4">
    <p>AKB勝手に総選挙 — 非公式のファンサイトです。</p>
    <a class="text-pink underline-offset-4 hover:underline" href="https://x.com/matsubokkuri">@matsubokkuri</a>
  </div>
</footer>

<div id="toast" role="status" aria-live="polite"
     class="pointer-events-none fixed inset-x-0 bottom-6 z-60 mx-auto w-fit max-w-[calc(100%-2rem)] translate-y-4 rounded-full border border-pink/40 bg-panel-2/95 px-5 py-3 text-sm font-bold opacity-0 shadow-2xl backdrop-blur transition duration-300 data-[show]:translate-y-0 data-[show]:opacity-100 data-[error]:border-pink data-[error]:bg-pink-deep"></div>

<script>
(() => {
  const board = document.getElementById('board');
  if (!board) return;
  const items = [...board.querySelectorAll('li[data-name]')];
  const toast = document.getElementById('toast');
  const total = document.querySelector('[data-total]');
  const fmt = new Intl.NumberFormat('ja-JP');
  let toastTimer;

  const say = (message, error = false) => {
    toast.textContent = message;
    toast.toggleAttribute('data-error', error);
    toast.setAttribute('data-show', '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.removeAttribute('data-show'), 2600);
  };

  // Member filter
  const norm = (s) => s.normalize('NFKC').toLowerCase();
  const keys = items.map((li) => norm(li.dataset.name));
  const search = document.getElementById('search');
  const noHit = document.getElementById('no-hit');
  document.getElementById('search-box').hidden = false;
  search.addEventListener('input', () => {
    const q = norm(search.value.trim());
    let shown = 0;
    items.forEach((li, i) => {
      const hit = keys[i].includes(q);
      li.hidden = !hit;
      shown += hit;
    });
    noHit.hidden = shown > 0;
  });

  // Vote in place
  const rescale = () => {
    const top = Math.max(...items.map((li) => +li.dataset.votes));
    for (const li of items) {
      li.querySelector('[data-bar]').style.setProperty('--share', top ? (li.dataset.votes / top) * 100 + '%' : '0%');
    }
  };

  board.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.target;
    const li = form.closest('li');
    const button = form.querySelector('button');
    button.disabled = true;
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json' },
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) throw new Error(data.error || '投票に失敗しました。');

      li.dataset.votes = data.votes;
      const count = li.querySelector('[data-count]');
      count.textContent = fmt.format(data.votes);
      total.textContent = fmt.format(Number(total.textContent.replace(/\D/g, '')) + 1);
      rescale();

      li.classList.remove('motion-safe:animate-voted');
      void li.offsetWidth;
      li.classList.add('motion-safe:animate-voted');
      const plus = document.createElement('span');
      plus.textContent = '+1';
      plus.className = 'pointer-events-none absolute -top-4 left-full ml-1 font-sans text-sm font-bold text-pink motion-safe:animate-plus';
      count.parentElement.append(plus);
      setTimeout(() => plus.remove(), 1200);

      say(`${data.name} に投票しました（現在 ${data.rank} 位）`);
    } catch (err) {
      say(err.message, true);
    } finally {
      button.disabled = false;
    }
  });
})();
</script>
</body>
</html>
