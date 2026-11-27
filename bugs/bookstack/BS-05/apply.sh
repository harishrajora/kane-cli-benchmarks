#!/usr/bin/env bash
set -euo pipefail
. "$(dirname "$0")/../../lib/bookstack.sh"
bs_theme_install "$(dirname "$0")/functions.php"
