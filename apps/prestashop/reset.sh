#!/usr/bin/env bash
# Restore PrestaShop to the snapshot taken after seeding. The harness runs this before every test.
# Restores the database and clears PrestaShop's cache. Every test also starts with a fresh browser,
# so no login cookie carries over between tests.
set -euo pipefail
cd "$(dirname "$0")"
set -a; . ./.env; set +a
[ -f snapshot/db.sql ] || { echo "No snapshot. Run ./snapshot.sh after seeding." >&2; exit 1; }

start=$(date +%s)
docker compose exec -T -e MYSQL_PWD="$DB_ROOT_PASSWORD" db mysql -u root -e \
  "DROP DATABASE prestashop; CREATE DATABASE prestashop CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci; GRANT ALL ON prestashop.* TO 'prestashop'@'%';"
docker compose exec -T -e MYSQL_PWD="$DB_ROOT_PASSWORD" db mysql -u root prestashop < snapshot/db.sql
docker compose exec -T prestashop sh -c 'rm -rf /var/www/html/var/cache/*'

echo "PrestaShop reset in $(( $(date +%s) - start ))s"
