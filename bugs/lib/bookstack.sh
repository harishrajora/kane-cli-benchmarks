# Helpers for BookStack bug scripts. Sourced by bugs/bookstack/*/apply.sh and check.sh.
# Provides: bs_api, bs_id, bs_login, bs_get, bs_theme_install, bs_theme_is

BS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../apps/bookstack" && pwd)"
set -a; . "$BS_DIR/.env"; set +a
BS_URL="http://localhost:6875"
BS_JAR="$(mktemp -d)/cookies"

# bs_api METHOD PATH [JSON]  - call the BookStack REST API as the benchmark admin
bs_api() {
  curl -sf -X "$1" -H "Authorization: Token $API_TOKEN_ID:$API_TOKEN_SECRET" \
    -H 'Content-Type: application/json' ${3:+-d "$3"} "$BS_URL/api$2"
}

# bs_id KIND FILTER_FIELD VALUE  - id of the first API item whose field equals value
bs_id() {
  bs_api GET "/$1?count=500" | python3 -c "
import json,sys
for x in json.load(sys.stdin)['data']:
    if str(x.get('$2')) == '$3': print(x['id']); break"
}

# bs_login EMAIL PASSWORD  - web login; later bs_get calls use this session
bs_login() {
  rm -f "$BS_JAR"
  local t
  t=$(curl -s -c "$BS_JAR" "$BS_URL/login" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  curl -s -b "$BS_JAR" -c "$BS_JAR" -o /dev/null --data-urlencode "_token=$t" \
    --data-urlencode "email=$1" --data-urlencode "password=$2" "$BS_URL/login"
}

# bs_get PATH [curl options...]  - GET as the logged-in user
bs_get() { local p="$1"; shift; curl -s -b "$BS_JAR" -c "$BS_JAR" "$@" "$BS_URL$p"; }

# bs_theme_install FILE  - install FILE as the bench theme's functions.php
bs_theme_install() {
  (cd "$BS_DIR" && docker compose exec -T bookstack sh -c \
    'cat > /config/www/themes/bench/functions.php && chown abc:users /config/www/themes/bench/functions.php') < "$1"
}

# bs_theme_is FILE  - true if the bench theme is active and its functions.php is exactly FILE
bs_theme_is() {
  local want have theme
  want=$(shasum -a 256 < "$1" | cut -d' ' -f1)
  theme=$(cd "$BS_DIR" && docker compose exec -T bookstack printenv APP_THEME)
  have=$(cd "$BS_DIR" && docker compose exec -T bookstack sh -c 'sha256sum /config/www/themes/bench/functions.php 2>/dev/null' | cut -d' ' -f1)
  [ "$theme" = "bench" ] && [ "$want" = "$have" ]
}
