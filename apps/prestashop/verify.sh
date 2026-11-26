#!/usr/bin/env bash
# Check the seeded shop's prices, tax, shipping, vouchers and stock rules to the cent,
# then reset the shop (the checks create throwaway carts).
set -uo pipefail
cd "$(dirname "$0")"

docker compose exec -T prestashop rm -rf /tmp/bench-seed
docker compose cp seed prestashop:/tmp/bench-seed 2>/dev/null
docker compose exec -T -u www-data prestashop php -d memory_limit=-1 /tmp/bench-seed/verify.php 2>&1 \
  | grep -v 'Cannot modify header information'
status=${PIPESTATUS[0]}
docker compose exec -T prestashop rm -rf /tmp/bench-seed
./reset.sh
exit "$status"
