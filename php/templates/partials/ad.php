<?php

declare(strict_types=1);

use function App\View\e;

/**
 * @var string $spacing margin classes
 *
 * adsbygoogle.js is loaded once, in the layout. The min-height reserves the
 * slot before the ad arrives, so it does not push the board down (CLS).
 */
?>
<aside class="<?= e($spacing) ?> rounded-3xl border border-dashed border-line p-3 before:mb-1 before:block before:text-[0.65rem] before:tracking-[0.25em] before:text-muted before:content-['SPONSORED']">
  <ins class="adsbygoogle block min-h-[100px] sm:min-h-[250px]"
       data-ad-client="ca-pub-3739212741143912"
       data-ad-slot="3405257928"
       data-ad-format="auto"
       data-full-width-responsive="true"></ins>
  <script>(window.adsbygoogle = window.adsbygoogle || []).push({});</script>
</aside>
