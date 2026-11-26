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

# Disable outside-service modules with PrestaShop's own module manager (list in structure.json)
modules=$(docker compose exec -T prestashop php -r \
  'echo implode(" ", json_decode(file_get_contents("/tmp/bench-seed/structure.json"), true)["disable_modules"]["names"]);')
for m in $modules; do
  docker compose exec -T -u www-data prestashop php bin/console prestashop:module disable "$m" --env=prod -n -q \
    && echo "module   disabled $m" || { echo "ERROR: could not disable $m" >&2; exit 1; }
done
docker compose exec -T prestashop sh -c 'rm -rf /var/www/html/var/cache/*'
docker compose exec -T prestashop rm -rf /tmp/bench-seed
