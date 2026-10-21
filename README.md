Sample application for Redis-Ranking
===

Abstract
---
- Sample application for Redis Ranking
  - https://github.com/matsubo/redis-ranking
- Frontend: Tailwind CSS v4 (`src/app.css` → `public/assets/css/app.css`, generated, not committed)

Requirements
---
- PHP 8.5 + phpredis
- Redis server
- Node.js (only for building the CSS; the Docker image builds it in its own stage)

Docker covers all of these; nothing has to be installed on the host.


Layout
---

```
public/index.php          front controller: wires the objects, handles the request
public/assets/js/vote.js  member filter and in-place voting (the board is one form)
public/robots.txt, sitemap.xml
php/src/Domain/           Ballot (members and votes), VoteLimiter (per-IP quota), both on redis/ranking
php/src/Http/             Request, Response, CsrfToken, Voter (client IP)
php/src/Controller/       RankingController: GET / renders, POST / votes, /index.php → 301 /
php/src/View/             View (plain PHP templates), Assets (?v=mtime URLs), e()
php/templates/            layout, page (with JSON-LD ItemList) and partials
php/import.php            member.txt → Redis
php/stubs/Ranking.stub    accurate types of redis/ranking for PHPStan
deploy/apache.conf        Cache-Control for /assets/ (1 year, URLs are versioned), ServerTokens Prod
```


Vote limit
---

One IP address may cast at most 100 votes in any 24 hours (sliding window). IPv6 is
counted per /64. The address is Cloudflare's `Cf-Connecting-Ip`; the only public path is
Cloudflare → tunnel → Traefik, and Cloudflare overwrites that header. Each address has a
redis/ranking set `vote-limit:<ip>` of its votes scored by time. One MULTI/EXEC adds the
vote and counts the set, so the two replicas cannot both take the last slot; the key
expires 24 hours after the last counted vote. Over the limit, a vote gets 429 with
`Retry-After`.


Development tools
---

PHP-CS-Fixer (PER-CS 3.0), PHPStan (level max) and PHPUnit run in the image's `dev` stage:

```
% docker compose run --rm php install   # composer install into php/vendor
% docker compose run --rm php check     # lint + PHPStan + PHPUnit (needs the compose Redis)
% docker compose run --rm php fix       # apply the formatter
```


Local preview
---

```
% docker compose up --build
```

Open http://localhost:8080 in Chrome or Firefox. The `seed` service imports `php/member.txt`
into Redis on startup. `public/`, `php/src/` and `php/templates/` are bind-mounted, and
`docker compose up css` rebuilds Tailwind on change, so edits show on reload.

Without Docker, CSS only:

```
% npm ci
% npm run build    # or: npm run watch
```


OG image
---

`public/assets/og.jpg` (1200×630) is a screenshot of `src/og.html`; the command to
regenerate it is at the top of that file. `og:image` carries the file's mtime as `?v=`,
so X and other crawlers fetch the new image instead of a cached one.


Seed data
---

`php/member.txt` lists the members. `php/import.php` adds the ones missing from Redis
(existing scores are kept), so it is safe to re-run after editing the file.

### Local (compose)
```
% docker compose run --rm seed
```

### Production
```
% ssh matsu@t1 'KUBECONFIG=$HOME/.kube/config kubectl -n ranking exec deploy/ranking -- sh -c "cd /var/www/php && php import.php"'
```


Deploy
---

https://ranking.teraren.com/ runs on the home k3s cluster (namespace `ranking`). The
namespace, RBAC and secrets are in matsubo/network-setting (`k8s/ranking.yaml`); this repo
holds the Deployment / Service / Ingress (`deploy/k8s.yaml`).

Commit, then:

```
% deploy/deploy.sh
```

It builds `ghcr.io/matsubo/redis-ranking-sample:<commit sha>` for linux/amd64, pushes it to
GHCR, imports it into containerd on t1/t2/t3/gmk, and applies `deploy/k8s.yaml` through
kubectl on t1. Needs `docker login ghcr.io` (write:packages), ssh to the nodes over the
tailnet, and their sudo password (prompted, or `SUDO_PASSWORD`).
