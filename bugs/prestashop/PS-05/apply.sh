#!/usr/bin/env bash
set -euo pipefail
. "$(dirname "$0")/../../lib/prestashop.sh"
ps_override_install "$(dirname "$0")/CartRule.php" classes/CartRule.php
