#!/usr/bin/env bash
set -euo pipefail
. "$(dirname "$0")/../../lib/prestashop.sh"
ps_php "$(dirname "$0")/apply.php"
