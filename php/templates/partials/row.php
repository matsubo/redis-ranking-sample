<?php

declare(strict_types=1);

use App\Domain\Standing;

use function App\View\e;

/**
 * 4th place and below; styles are the .board-row component in src/app.css.
 * The vote button submits the board's one form with text=<member>.
 *
 * @var Standing $member
 * @var float|int $share bar width in % of the leader's votes
 * @var int|null $delay fade-in delay in ms
 */
$style = $delay === null ? '' : " style=\"animation-delay:{$delay}ms\"";
?>
<li id="<?= e($member->anchor()) ?>" data-name="<?= e($member->name) ?>" data-votes="<?= $member->votes ?>" class="board-row<?= $delay === null ? '' : ' motion-safe:animate-rise' ?>"<?= $style ?>>
  <p class="rank"><?= $member->rank ?><small>位</small></p>
  <div class="name"><p><?= e($member->name) ?></p><span class="vote-bar" aria-hidden="true"><span data-bar style="--share:<?= $share ?>%"></span></span></div>
  <p class="count"><span data-count><?= number_format($member->votes) ?></span><small>票</small></p>
  <button class="vote-button" name="text" value="<?= e($member->name) ?>" aria-label="<?= e($member->name) ?>に投票">投票</button>
</li>
