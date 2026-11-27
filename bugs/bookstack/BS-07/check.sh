#!/usr/bin/env bash
# Bug active if the Rollback Procedure page, as seen in the browser, does not list rollback.sh.
set -euo pipefail
. "$(dirname "$0")/../../lib/bookstack.sh"
bs_login "$ADMIN_EMAIL" "$ADMIN_PASSWORD"
! bs_get /books/deployment-runbook/page/rollback-procedure | grep -q '<div class="label">rollback\.sh</div>'
