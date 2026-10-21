<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Ballot;
use App\Domain\Standing;
use App\Domain\VoteLimiter;
use App\Http\CsrfToken;
use App\Http\Request;
use App\Http\Response;
use App\Http\Voter;
use App\View\Assets;
use App\View\View;

/**
 * GET / shows the ranking; POST / with mode=increment records a vote. The
 * page's script posts with Accept: application/json and gets the new count
 * back; a plain form post is redirected back to the member's row
 * (Post-Redirect-Get) or shown the page with the error.
 */
final readonly class RankingController
{
    public function __construct(
        private Ballot $ballot,
        private VoteLimiter $limiter,
        private View $view,
        private Assets $assets,
        /** Canonical origin without a trailing slash, e.g. https://ranking.teraren.com */
        private string $siteUrl,
    ) {}

    public function handle(Request $request): Response
    {
        // Apache also serves this script as /index.php; keep one canonical URL.
        if ($request->path === '/index.php') {
            return Response::redirect('/', 301);
        }

        $csrf = CsrfToken::fromRequest($request);

        $response = match (true) {
            $request->method === 'POST' && $request->input('mode') === 'increment' => $this->vote($request, $csrf),
            \in_array($request->method, ['GET', 'HEAD'], true) => $this->show($csrf),
            default => Response::html('Method Not Allowed', 405)->withHeader('Allow', 'GET, HEAD, POST'),
        };

        return $csrf->attachTo($response);
    }

    private function show(CsrfToken $csrf, string $error = '', int $status = 200): Response
    {
        $fetchError = '';
        try {
            $members = $this->ballot->standings();
        } catch (\RedisException|\RuntimeException) {
            $members = [];
            $fetchError = 'ランキングデータの取得に失敗しました。';
        }

        return Response::html($this->view->page('ranking', [
            'members' => $members,
            'totalVotes' => array_sum(array_map(static fn(Standing $m): int => $m->votes, $members)),
            'csrfToken' => $csrf->value,
            'error' => $error,
            'fetchError' => $fetchError,
            'assets' => $this->assets,
            'siteUrl' => $this->siteUrl,
            'voteLimit' => $this->limiter->limit(),
        ]), $status);
    }

    private function vote(Request $request, CsrfToken $csrf): Response
    {
        $outcome = $this->castVote($request, $csrf);
        $standing = $outcome->standing;

        if ($request->wantsJson()) {
            $response = Response::json(
                $standing === null
                    ? ['ok' => false, 'error' => $outcome->error]
                    : ['ok' => true, 'name' => $standing->name, 'votes' => $standing->votes, 'rank' => $standing->rank],
                $outcome->status,
            );
        } elseif ($standing !== null) {
            $response = Response::redirect('/#' . $standing->anchor());
        } else {
            $response = $this->show($csrf, $outcome->error, $outcome->status);
        }

        return $outcome->retryAfter === null
            ? $response
            : $response->withHeader('Retry-After', (string) $outcome->retryAfter);
    }

    private function castVote(Request $request, CsrfToken $csrf): VoteOutcome
    {
        $name = $request->input('text');
        if ($name === null || !$csrf->matches($request->input('csrf_token'))) {
            return VoteOutcome::failed(403, '不正なリクエストです。ページを再読み込みしてからもう一度お試しください。');
        }

        try {
            if (!$this->ballot->has($name)) {
                return VoteOutcome::failed(404, 'そのメンバーは見つかりませんでした。');
            }

            $quota = $this->limiter->attempt(Voter::fromServer($request->server), (int) (microtime(true) * 1000));
            if (!$quota->allowed) {
                return VoteOutcome::failed(
                    429,
                    \sprintf(
                        '投票は1つのIPアドレスにつき24時間で%d票までです。あと%sで投票できます。',
                        $this->limiter->limit(),
                        self::duration($quota->retryAfterMs),
                    ),
                    (int) ceil($quota->retryAfterMs / 1000),
                );
            }

            return VoteOutcome::counted($this->ballot->vote($name));
        } catch (\RedisException|\RuntimeException) {
            return VoteOutcome::failed(500, '投票処理に失敗しました。');
        }
    }

    /** "3時間5分", "12分"; rounded up to the minute. */
    private static function duration(int $ms): string
    {
        $minutes = (int) ceil($ms / 60_000);
        $hours = intdiv($minutes, 60);
        $minutes %= 60;

        return ($hours > 0 ? "{$hours}時間" : '') . ($minutes > 0 || $hours === 0 ? "{$minutes}分" : '');
    }
}
