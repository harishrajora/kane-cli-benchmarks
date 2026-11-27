#!/usr/bin/env bash
# Bug active if the override is installed. (Its behaviour through the storefront was verified once by hand
# with a clean control on 2026-11-26: see the report.)
set -euo pipefail
. "$(dirname "$0")/../../lib/prestashop.sh"
ps_override_is "$(dirname "$0")/CartController.php" controllers/front/CartController.php
