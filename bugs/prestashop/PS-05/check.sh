#!/usr/bin/env bash
# Bug active if the override is installed and the probe finds EXPIRED accepted.
set -euo pipefail
. "$(dirname "$0")/../../lib/prestashop.sh"
ps_override_is "$(dirname "$0")/CartRule.php" classes/CartRule.php
ps_php "$(dirname "$0")/../probe.php" | tail -1 | python3 -c "
import json,sys; sys.exit(0 if json.load(sys.stdin)['expired_voucher_accepted'] else 1)"
