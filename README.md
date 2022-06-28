Sample application for Redis-Ranking
===

Abstract
---
- Sample application for Redis Ranking
  - https://github.com/matsubo/redis-ranking

Requirements
---
- PHP
  - `>=5.5`
  - `>=7`
  - `>=8`
- php-redis
- Redis server


Setup
---

```
% git clone git://github.com/matsubo/redis-ranking-sample.git
% cd redis-ranking-sample
% composer install
```


Seed data
---

- Preview `member.txt` and execute import data.
- You can add members by updating the `member.txt` and execute following same command.

```
% php import.php
```


Demo
---
- https://matsu.teraren.com/ranking/


