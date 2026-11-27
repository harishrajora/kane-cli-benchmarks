#!/usr/bin/env bash
set -euo pipefail
. "$(dirname "$0")/../../lib/bookstack.sh"
book=$(bs_id books slug roadmap)
viewer=$(bs_api GET /roles | python3 -c "import json,sys; print([r['id'] for r in json.load(sys.stdin)['data'] if r['display_name']=='Viewer'][0])")
bs_api PUT "/content-permissions/book/$book" \
  "{\"fallback_permissions\":{\"inheriting\":true},\"role_permissions\":[{\"role_id\":$viewer,\"view\":true,\"create\":false,\"update\":false,\"delete\":false}]}" >/dev/null
