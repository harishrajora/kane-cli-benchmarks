#!/usr/bin/env bash
# Bug active if Vera Viewer can open the Roadmap book.
set -euo pipefail
. "$(dirname "$0")/../../lib/bookstack.sh"
bs_login vera.viewer@bench.local "$USER_PASSWORD"
[ "$(bs_get /books/roadmap -o /dev/null -w '%{http_code}')" = "200" ]
