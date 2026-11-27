#!/usr/bin/env bash
# Bug active if the theme file is installed AND Onboarding's Getting Started redirects to the API Guide's.
set -euo pipefail
. "$(dirname "$0")/../../lib/bookstack.sh"
bs_theme_is "$(dirname "$0")/functions.php"
bs_login "$ADMIN_EMAIL" "$ADMIN_PASSWORD"
[ "$(bs_get /books/onboarding/page/getting-started -o /dev/null -w '%{redirect_url}')" = "$BS_URL/books/api-guide/page/getting-started" ]
