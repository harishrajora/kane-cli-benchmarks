#!/usr/bin/env bash
# Run once after the first `docker compose up` has finished installing PrestaShop.
# Renames the randomly named back-office folder to the fixed name admin-bench,
# so the back office is always at http://localhost:8080/admin-bench/.
set -euo pipefail
cd "$(dirname "$0")"

docker compose exec -T prestashop sh -c '
  set -e
  cd /var/www/html
  if [ -d admin-bench ]; then echo "Already at admin-bench"; exit 0; fi
  current=$(ls -d admin[0-9]*/ 2>/dev/null | head -1 | tr -d /)
  [ -n "$current" ] || { echo "No installer-named admin folder found" >&2; exit 1; }
  mv "$current" admin-bench
  rm -rf var/cache/*
  echo "Renamed $current -> admin-bench"
'
