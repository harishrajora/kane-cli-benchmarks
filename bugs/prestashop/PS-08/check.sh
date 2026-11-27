#!/usr/bin/env bash
# Bug active if Casey's default group is Wholesale and her mug cart is 15% cheaper ($10.12 before tax).
set -euo pipefail
. "$(dirname "$0")/../../lib/prestashop.sh"
ps_php "$(dirname "$0")/../probe.php" | tail -1 | python3 -c "
import json,sys; f=json.load(sys.stdin)
sys.exit(0 if f['casey_default_group']=='Wholesale' and f['mug_cart']['products']==10.12 else 1)"
