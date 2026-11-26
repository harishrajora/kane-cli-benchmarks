#!/usr/bin/env bash
# Save the current BookStack state (database + uploaded files) as the reset point.
# Run once, right after seed/seed.py on a fresh instance. Output: apps/bookstack/snapshot/ (gitignored,
# because it holds password hashes for this machine's .env; anyone rerunning the benchmark makes their own).
set -euo pipefail
cd "$(dirname "$0")"
set -a; . ./.env; set +a

mkdir -p snapshot
docker compose exec -T -e MYSQL_PWD="$DB_PASSWORD" db \
  mariadb-dump --single-transaction --skip-dump-date -u bookstack bookstack > snapshot/db.sql
docker compose exec -T bookstack tar czf - -C /config/www uploads files > snapshot/files.tgz

echo "Snapshot saved: $(wc -c < snapshot/db.sql | tr -d ' ') bytes of SQL, $(wc -c < snapshot/files.tgz | tr -d ' ') bytes of files"
