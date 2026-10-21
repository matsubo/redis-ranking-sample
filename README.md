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


Local preview
---

```
% docker compose up --build
```

Open http://localhost:8080 in Chrome or Firefox. The `seed` service imports `php/member.txt`
into Redis on startup. `public/` and `src/` are bind-mounted and the `css` service rebuilds
Tailwind on change, so edits show on reload.

Without Docker, CSS only:

```
% npm ci
% npm run build    # or: npm run watch
```


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
