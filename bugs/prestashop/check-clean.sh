#!/usr/bin/env bash
# Confirm PrestaShop is in its clean state: no overrides, and the shop facts the bugs change are as seeded.
set -euo pipefail
. "$(dirname "$0")/../lib/prestashop.sh"

extra=$(cd "$PS_DIR" && docker compose exec -T prestashop sh -c 'cd /var/www/html/override && find . -mindepth 1 ! -path ./index.php | wc -l' | tr -d ' ')
[ "$extra" = "0" ] || { echo "override files present" >&2; exit 1; }

facts=$(ps_php "$(dirname "$0")/probe.php")
python3 - "$facts" <<'EOF'
import json, sys
f = json.loads(sys.argv[1].strip().splitlines()[-1])
want = {
    "tax_rate": 8.0,
    "casey_default_group": "Customer",
    "mug_cart": {"products": 11.9, "tax": 0.95, "shipping": 5.0, "total": 17.85},
    "expired_voucher_accepted": False,
}
bad = {k: (f.get(k), v) for k, v in want.items() if f.get(k) != v}
if bad:
    sys.exit(f"not clean: {bad}")
EOF
