#!/usr/bin/env bash
# Save the current PrestaShop database as the reset point. Run once, right after seed.sh.
# The seed changes only the database (no uploaded files), so the database dump is the whole state.
# Output: apps/prestashop/snapshot/ (gitignored: it holds password hashes tied to this machine's .env).
set -euo pipefail
cd "$(dirname "$0")"
set -a; . ./.env; set +a

mkdir -p snapshot
docker compose exec -T -e MYSQL_PWD="$DB_ROOT_PASSWORD" db \
  mysqldump -u root --single-transaction --skip-dump-date --routines --triggers prestashop > snapshot/db.sql
echo "Snapshot saved: $(wc -c < snapshot/db.sql | tr -d ' ') bytes of SQL"
