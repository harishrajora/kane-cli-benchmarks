#!/usr/bin/env bash
# Bug active if the theme file is installed AND a real page update is answered with success but not saved.
set -euo pipefail
. "$(dirname "$0")/../../lib/bookstack.sh"
bs_theme_is "$(dirname "$0")/functions.php"
id=$(bs_id pages slug glossary)
bs_login "$ADMIN_EMAIL" "$ADMIN_PASSWORD"
t=$(bs_get /books/onboarding/page/glossary/edit | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
bs_get /books/onboarding/page/glossary -o /dev/null -X POST --data-urlencode "_token=$t" --data-urlencode "_method=PUT" \
  --data-urlencode "name=Glossary CHECK" --data-urlencode "markdown=check"
[ "$(bs_api GET "/pages/$id" | python3 -c 'import json,sys; print(json.load(sys.stdin)["name"])')" = "Glossary" ]
