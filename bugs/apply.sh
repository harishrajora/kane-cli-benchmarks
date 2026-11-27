#!/usr/bin/env bash
# Put an app into a known state for one benchmark run:
#   bugs/apply.sh <app> clean      reset the app; confirm no bug is active
#   bugs/apply.sh <app> <BUG-ID>   reset the app, switch the bug on, confirm it is active
# Exits non-zero if the state cannot be confirmed, so a run never starts on the wrong build.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
app="${1:?usage: bugs/apply.sh <bookstack|prestashop> <clean|BUG-ID>}"
bug="${2:?usage: bugs/apply.sh <bookstack|prestashop> <clean|BUG-ID>}"

"$ROOT/apps/$app/reset.sh" >/dev/null

if [ "$bug" = "clean" ]; then
  "$ROOT/bugs/$app/check-clean.sh" && echo "$app: clean build confirmed" && exit 0
  echo "$app: clean build NOT confirmed" >&2; exit 1
fi

dir="$ROOT/bugs/$app/$bug"
[ -d "$dir" ] || { echo "no such bug: $app/$bug" >&2; exit 1; }
"$dir/apply.sh"
if "$dir/check.sh"; then
  echo "$app: $bug active (confirmed)"
else
  echo "$app: $bug did NOT activate; resetting" >&2
  "$ROOT/apps/$app/reset.sh" >/dev/null
  exit 1
fi
