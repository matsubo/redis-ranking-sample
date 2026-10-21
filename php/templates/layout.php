<?php

declare(strict_types=1);

use App\View\Assets;

use function App\View\e;

/**
 * @var string $content
 * @var Assets $assets
 */
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
  <meta property="og:image" content="<?= e($assets->absoluteUrl('/assets/og.jpg')) ?>">
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
  <link rel="stylesheet" href="<?= e($assets->url('/assets/css/app.css')) ?>">
  <script src="<?= e($assets->url('/assets/js/vote.js')) ?>" defer></script>

  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-WNH3R2');</script>
  <!-- End Google Tag Manager -->
  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3739212741143912" crossorigin="anonymous"></script>
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

<?= $content ?>

<footer class="border-t border-line py-8 text-sm text-muted">
  <div class="mx-auto flex w-[min(1080px,100%-2.5rem)] flex-wrap justify-between gap-4">
    <p>AKB勝手に総選挙 — 非公式のファンサイトです。</p>
    <a class="text-pink underline-offset-4 hover:underline" href="https://x.com/matsubokkuri">@matsubokkuri</a>
  </div>
</footer>

<div id="toast" role="status" aria-live="polite"
     class="pointer-events-none fixed inset-x-0 bottom-6 z-60 mx-auto w-fit max-w-[calc(100%-2rem)] translate-y-4 rounded-full border border-pink/40 bg-panel-2/95 px-5 py-3 text-sm font-bold opacity-0 shadow-2xl backdrop-blur transition duration-300 data-[show]:translate-y-0 data-[show]:opacity-100 data-[error]:border-pink data-[error]:bg-pink-deep"></div>
</body>
</html>
