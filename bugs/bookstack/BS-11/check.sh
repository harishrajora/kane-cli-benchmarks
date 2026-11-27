#!/usr/bin/env bash
# Bug active if the theme is active with exactly this functions.php.
# (A save-based check would create a page right before the test, so the behaviour was verified once,
# by hand, on 2026-11-26: see the report.)
set -euo pipefail
. "$(dirname "$0")/../../lib/bookstack.sh"
bs_theme_is "$(dirname "$0")/functions.php"
