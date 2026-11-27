#!/usr/bin/env bash
# Bug active if the probe's mug cart shows 0.8% tax (total $17.00).
set -euo pipefail
. "$(dirname "$0")/../../lib/prestashop.sh"
ps_php "$(dirname "$0")/../probe.php" | tail -1 | python3 -c "
import json,sys; f=json.load(sys.stdin); m=f['mug_cart']
sys.exit(0 if f['tax_rate']==0.8 and m['tax']==0.1 and m['total']==17.0 else 1)"
