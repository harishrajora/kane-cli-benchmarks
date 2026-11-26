#!/usr/bin/env bash
# Copy seed/ into the PrestaShop container and run seed.php there as the web-server user.
# Run once, on a freshly installed shop, after post-install.sh.
set -euo pipefail
cd "$(dirname "$0")"
set -a; . ./.env; set +a
: "${USER_PASSWORD:?USER_PASSWORD missing in .env}"

docker compose exec -T prestashop rm -rf /tmp/bench-seed
docker compose cp seed prestashop:/tmp/bench-seed
docker compose exec -T -u www-data -e USER_PASSWORD="$USER_PASSWORD" prestashop \
  php -d memory_limit=-1 /tmp/bench-seed/seed.php
docker compose exec -T prestashop rm -rf /tmp/bench-seed
