#!/usr/bin/env bash
# Bug active if the override is installed and the probe's mug cart totals $17.86 with unchanged parts.
set -euo pipefail
. "$(dirname "$0")/../../lib/prestashop.sh"
ps_override_is "$(dirname "$0")/Cart.php" classes/Cart.php
ps_php "$(dirname "$0")/../probe.php" | tail -1 | python3 -c "
import json,sys; m=json.load(sys.stdin)['mug_cart']
sys.exit(0 if m['products']==11.9 and m['shipping']==5.0 and m['total']==17.86 else 1)"
