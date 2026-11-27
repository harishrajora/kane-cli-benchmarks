#!/usr/bin/env bash
# Restore BookStack to the snapshot taken after seeding. The harness runs this before every test.
# Restores the database and uploaded files, clears caches and logs everyone out.
set -euo pipefail
cd "$(dirname "$0")"
set -a; . ./.env; set +a
[ -f snapshot/db.sql ] && [ -f snapshot/files.tgz ] || { echo "No snapshot. Run ./snapshot.sh after seeding." >&2; exit 1; }

start=$(date +%s)
docker compose exec -T -e MYSQL_PWD="$DB_ROOT_PASSWORD" db \
  mariadb -u root -e "DROP DATABASE bookstack; CREATE DATABASE bookstack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db mariadb -u bookstack bookstack < snapshot/db.sql
docker compose exec -T bookstack sh -c 'rm -rf /config/www/uploads /config/www/files && tar xzf - -C /config/www && chown -R abc:users /config/www/uploads /config/www/files' < snapshot/files.tgz
docker compose exec -T bookstack sh -c 'rm -f /config/www/framework/sessions/* && php /app/www/artisan cache:clear -q'
# Remove any seeded-bug theme files: the clean build has an empty theme folder
docker compose exec -T bookstack sh -c 'rm -rf /config/www/themes/bench && mkdir -p /config/www/themes/bench && chown abc:users /config/www/themes/bench'

echo "BookStack reset in $(( $(date +%s) - start ))s"
