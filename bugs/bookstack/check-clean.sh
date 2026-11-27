#!/usr/bin/env bash
# Confirm BookStack is in its clean state: no bug theme file, and the seeded permissions and attachments as seeded.
set -euo pipefail
. "$(dirname "$0")/../lib/bookstack.sh"

files=$(cd "$BS_DIR" && docker compose exec -T bookstack sh -c 'ls -A /config/www/themes/bench | wc -l' | tr -d ' ')
[ "$files" = "0" ] || { echo "bug theme files present" >&2; exit 1; }

bs_login vera.viewer@bench.local "$USER_PASSWORD"
[ "$(bs_get /books/roadmap -o /dev/null -w '%{http_code}')" = "404" ] || { echo "Viewer can see Roadmap" >&2; exit 1; }

rollback=$(bs_id pages slug rollback-procedure)
att=$(bs_api GET '/attachments?count=500' | python3 -c "import json,sys; print([a['uploaded_to'] for a in json.load(sys.stdin)['data'] if a['name']=='rollback.sh'][0])")
[ "$att" = "$rollback" ] || { echo "rollback.sh is not on Rollback Procedure" >&2; exit 1; }
